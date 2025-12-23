<?php

namespace App\Services\Cart;

use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const TOKEN_HEADER = 'X-Cart-Token';

    public function __construct(private DatabaseManager $db)
    {
    }

    public function getOrCreateCart(Request $request): Cart
    {
        $user = $request->user('sanctum');
        $guestToken = $this->getGuestTokenFromRequest($request);

        if ($user) {
            $cart = Cart::query()->where('user_id', $user->id)->where('status', 'active')->first();
            if (! $cart) {
                $cart = Cart::create([
                    'user_id' => $user->id,
                    'currency' => 'USD',
                    'status' => 'active',
                ]);
            }

            if ($guestToken) {
                $this->mergeGuestCartIntoUserCart($guestToken, $user);
                $cart = Cart::query()->where('user_id', $user->id)->where('status', 'active')->firstOrFail();
            }

            return $cart->load('items.purchasable');
        }

        if ($guestToken) {
            $cart = Cart::query()->where('guest_token', $guestToken)->where('status', 'active')->first();
            if ($cart) {
                return $cart->load('items.purchasable');
            }
        }

        $token = (string) Str::uuid();

        $cart = Cart::create([
            'guest_token' => $token,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        return $cart->load('items.purchasable');
    }

    public function addItem(Cart $cart, string $purchasableType, int $purchasableId, int $quantity): Cart
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => ['Quantity must be at least 1.'],
            ]);
        }

        return $this->db->transaction(function () use ($cart, $purchasableType, $purchasableId, $quantity) {
            $purchasable = $this->resolvePurchasable($purchasableType, $purchasableId);

            $existing = $cart->items()
                ->where('purchasable_type', $purchasable::class)
                ->where('purchasable_id', $purchasable->getKey())
                ->first();

            $newQty = ($existing?->quantity ?? 0) + $quantity;

            $currency = (string) ($purchasable->currency ?? 'USD');
            $this->assertCartCurrencyCompatible($cart, $currency);
            $this->assertPurchasableCanBeAdded($purchasable);
            $this->assertStockAvailable($purchasable, $newQty);

            $unitPrice = (float) ($purchasable->price ?? 0);

            if ($existing) {
                $existing->update([
                    'quantity' => $newQty,
                    'unit_price' => $unitPrice,
                    'currency' => $currency,
                ]);
            } else {
                $cart->items()->create([
                    'purchasable_type' => $purchasable::class,
                    'purchasable_id' => $purchasable->getKey(),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'currency' => $currency,
                ]);
            }

            Log::channel('catalogue')->info('cart.item.added', [
                'cart_id' => $cart->id,
                'purchasable_type' => $purchasable::class,
                'purchasable_id' => $purchasable->getKey(),
                'quantity' => $quantity,
            ]);

            return $cart->fresh()->load('items.purchasable');
        });
    }

    public function updateItemQuantity(Cart $cart, CartItem $item, int $quantity): Cart
    {
        if ($quantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => ['Quantity cannot be negative.'],
            ]);
        }

        if ($item->cart_id !== $cart->id) {
            abort(404, 'Item not found for this cart.');
        }

        return $this->db->transaction(function () use ($cart, $item, $quantity) {
            if ($quantity === 0) {
                $item->delete();
                return $cart->fresh()->load('items.purchasable');
            }

            $purchasable = $item->purchasable;
            if (! $purchasable) {
                abort(422, 'Invalid cart item.');
            }

            $this->assertPurchasableCanBeAdded($purchasable);
            $this->assertStockAvailable($purchasable, $quantity);

            $currency = (string) ($purchasable->currency ?? 'USD');
            $this->assertCartCurrencyCompatible($cart, $currency);

            $unitPrice = (float) ($purchasable->price ?? 0);
            $item->update([
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'currency' => $currency,
            ]);

            Log::channel('catalogue')->info('cart.item.updated', [
                'cart_id' => $cart->id,
                'item_id' => $item->id,
                'quantity' => $quantity,
            ]);

            return $cart->fresh()->load('items.purchasable');
        });
    }

    public function removeItem(Cart $cart, CartItem $item): Cart
    {
        if ($item->cart_id !== $cart->id) {
            abort(404, 'Item not found for this cart.');
        }

        return $this->db->transaction(function () use ($cart, $item) {
            $itemId = $item->id;
            $item->delete();

            Log::channel('catalogue')->info('cart.item.removed', [
                'cart_id' => $cart->id,
                'item_id' => $itemId,
            ]);

            return $cart->fresh()->load('items.purchasable');
        });
    }

    public function mergeGuestCartIntoUserCart(string $guestToken, User $user): Cart
    {
        return $this->db->transaction(function () use ($guestToken, $user) {
            $guestCart = Cart::query()->where('guest_token', $guestToken)->where('status', 'active')->lockForUpdate()->first();
            if (! $guestCart) {
                return $this->getOrCreateUserCart($user)->load('items.purchasable');
            }

            $userCart = $this->getOrCreateUserCart($user);

            foreach ($guestCart->items()->with('purchasable')->get() as $guestItem) {
                $purchasable = $guestItem->purchasable;
                if (! $purchasable) {
                    continue;
                }

                $this->assertPurchasableCanBeAdded($purchasable);

                $existing = $userCart->items()
                    ->where('purchasable_type', $guestItem->purchasable_type)
                    ->where('purchasable_id', $guestItem->purchasable_id)
                    ->first();

                $newQty = ($existing?->quantity ?? 0) + (int) $guestItem->quantity;

                $currency = (string) ($purchasable->currency ?? 'USD');
                $this->assertCartCurrencyCompatible($userCart, $currency);
                $this->assertStockAvailable($purchasable, $newQty);

                $unitPrice = (float) ($purchasable->price ?? 0);

                if ($existing) {
                    $existing->update([
                        'quantity' => $newQty,
                        'unit_price' => $unitPrice,
                        'currency' => $currency,
                    ]);
                } else {
                    $userCart->items()->create([
                        'purchasable_type' => $guestItem->purchasable_type,
                        'purchasable_id' => $guestItem->purchasable_id,
                        'quantity' => (int) $guestItem->quantity,
                        'unit_price' => $unitPrice,
                        'currency' => $currency,
                    ]);
                }
            }

            $guestCart->delete();

            Log::channel('catalogue')->info('cart.merged', [
                'user_id' => $user->id,
                'guest_token' => $guestToken,
                'cart_id' => $userCart->id,
            ]);

            return $userCart->fresh()->load('items.purchasable');
        });
    }

    protected function getOrCreateUserCart(User $user): Cart
    {
        $cart = Cart::query()->where('user_id', $user->id)->where('status', 'active')->lockForUpdate()->first();
        if ($cart) {
            return $cart;
        }

        return Cart::create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'status' => 'active',
        ]);
    }

    protected function resolvePurchasable(string $type, int $id)
    {
        if ($type === 'product') {
            return Product::query()->findOrFail($id);
        }

        if ($type === 'variant') {
            return ProductVariant::query()->findOrFail($id);
        }

        throw ValidationException::withMessages([
            'purchasable_type' => ['Invalid purchasable_type. Use product or variant.'],
        ]);
    }

    protected function assertPurchasableCanBeAdded($purchasable): void
    {
        if ($purchasable instanceof Product && $purchasable->type === 'variable') {
            throw ValidationException::withMessages([
                'purchasable_type' => ['Variable products must be added via a variant.'],
            ]);
        }

        if (($purchasable instanceof Product || $purchasable instanceof ProductVariant) && ! (bool) $purchasable->is_active) {
            throw ValidationException::withMessages([
                'purchasable_id' => ['This item is not available.'],
            ]);
        }
    }

    protected function assertCartCurrencyCompatible(Cart $cart, string $currency): void
    {
        if ($cart->currency && strtoupper($cart->currency) !== strtoupper($currency)) {
            throw ValidationException::withMessages([
                'currency' => ['Cart currency mismatch.'],
            ]);
        }

        if (! $cart->currency) {
            $cart->update(['currency' => $currency]);
        }
    }

    protected function assertStockAvailable($purchasable, int $quantity): void
    {
        $status = (string) ($purchasable->stock_status ?? 'out_of_stock');
        if ($status === 'preorder') {
            return;
        }

        $available = (int) ($purchasable->stock_quantity ?? 0);
        if ($available < $quantity) {
            throw new InsufficientStockException('Insufficient stock available.');
        }
    }

    protected function getGuestTokenFromRequest(Request $request): ?string
    {
        $token = $request->header(self::TOKEN_HEADER);
        if (! $token) {
            $token = $request->input('guest_token');
        }

        $token = is_string($token) ? trim($token) : null;

        return $token !== '' ? $token : null;
    }
}
