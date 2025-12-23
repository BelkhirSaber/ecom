<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Cart\CartService;
use App\Services\Cart\CartTotalsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct(private CartService $carts, private CartTotalsService $totals)
    {
    }

    public function show(Request $request)
    {
        $cart = $this->carts->getOrCreateCart($request);

        return $this->respond($cart);
    }

    public function addItem(Request $request)
    {
        $data = $request->validate([
            'purchasable_type' => ['required', 'string'],
            'purchasable_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->carts->getOrCreateCart($request);

        try {
            $cart = $this->carts->addItem(
                $cart,
                $data['purchasable_type'],
                (int) $data['purchasable_id'],
                (int) $data['quantity']
            );
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages([
                'quantity' => [$e->getMessage()],
            ]);
        }

        return $this->respond($cart);
    }

    public function updateItem(Request $request, CartItem $item)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $cart = $this->carts->getOrCreateCart($request);

        try {
            $cart = $this->carts->updateItemQuantity($cart, $item, (int) $data['quantity']);
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages([
                'quantity' => [$e->getMessage()],
            ]);
        }

        return $this->respond($cart);
    }

    public function removeItem(Request $request, CartItem $item)
    {
        $cart = $this->carts->getOrCreateCart($request);
        $cart = $this->carts->removeItem($cart, $item);

        return $this->respond($cart);
    }

    public function merge(Request $request)
    {
        $data = $request->validate([
            'guest_token' => ['nullable', 'string'],
        ]);

        $user = $request->user('sanctum');
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $guestToken = $data['guest_token'] ?? $request->header(CartService::TOKEN_HEADER);
        $guestToken = is_string($guestToken) ? trim($guestToken) : '';

        if ($guestToken === '') {
            throw ValidationException::withMessages([
                'guest_token' => ['guest_token is required.'],
            ]);
        }

        $cart = $this->carts->mergeGuestCartIntoUserCart($guestToken, $user);

        return $this->respond($cart);
    }

    protected function respond(Cart $cart): CartResource
    {
        $cart->loadMissing('items.purchasable');
        $cart->setAttribute('totals', $this->totals->forCart($cart));

        return new CartResource($cart);
    }
}
