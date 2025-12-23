<?php

namespace App\Services\Order;

use App\Exceptions\InsufficientStockException;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Services\Cart\CartTotalsService;
use App\Services\Inventory\InventoryService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private DatabaseManager $db,
        private CartRepositoryInterface $carts,
        private InventoryService $inventory,
        private CartTotalsService $totals
    ) {
    }

    public function createFromCart(Cart $cart, Address $shippingAddress, Address $billingAddress, ?int $userId = null): Order
    {
        $cart->loadMissing('items.purchasable');

        if ($cart->status !== 'active') {
            throw ValidationException::withMessages([
                'cart' => ['Cart is not active.'],
            ]);
        }

        if ($cart->items->count() === 0) {
            throw ValidationException::withMessages([
                'cart' => ['Cart is empty.'],
            ]);
        }

        return $this->db->transaction(function () use ($cart, $shippingAddress, $billingAddress, $userId) {
            $lockedCart = $this->carts->lockForOrderCreation((int) $cart->getKey());

            if ($lockedCart->status !== 'active') {
                throw ValidationException::withMessages([
                    'cart' => ['Cart is not active.'],
                ]);
            }

            $items = $lockedCart->items;
            if ($items->count() === 0) {
                throw ValidationException::withMessages([
                    'cart' => ['Cart is empty.'],
                ]);
            }
            $totals = $this->totals->forCart($lockedCart);

            $order = Order::create([
                'user_id' => $lockedCart->user_id,
                'cart_id' => $lockedCart->id,
                'status' => 'pending',
                'currency' => (string) ($lockedCart->currency ?? 'USD'),
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'shipping_total' => $totals['shipping_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'shipping_address_id' => $shippingAddress->id,
                'billing_address_id' => $billingAddress->id,
                'shipping_address' => $this->snapshotAddress($shippingAddress),
                'billing_address' => $this->snapshotAddress($billingAddress),
                'placed_at' => now(),
            ]);

            foreach ($items as $item) {
                $purchasable = $item->purchasable;

                if (! $purchasable) {
                    throw ValidationException::withMessages([
                        'cart' => ['Cart contains an invalid item.'],
                    ]);
                }

                try {
                    $this->inventory->decrementStock(
                        $purchasable,
                        (int) $item->quantity,
                        'order_created',
                        [
                            'order_id' => $order->id,
                            'cart_id' => $lockedCart->id,
                            'purchasable_type' => $purchasable::class,
                            'purchasable_id' => $purchasable->getKey(),
                        ],
                        $userId,
                        'Stock decremented due to order creation.'
                    );
                } catch (InsufficientStockException $e) {
                    throw ValidationException::withMessages([
                        'cart' => [$e->getMessage()],
                    ]);
                }

                $qty = (int) ($item->quantity ?? 0);
                $unitPriceCents = $this->toCents($item->unit_price);
                $lineTotalCents = $unitPriceCents * $qty;

                $unitPrice = $this->formatCents($unitPriceCents);
                $lineTotal = $this->formatCents($lineTotalCents);

                $order->items()->create([
                    'purchasable_type' => $item->purchasable_type,
                    'purchasable_id' => $item->purchasable_id,
                    'sku' => $purchasable->sku ?? null,
                    'name' => (string) ($purchasable->name ?? 'Item'),
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'currency' => (string) ($item->currency ?? $lockedCart->currency ?? 'USD'),
                    'line_total' => $lineTotal,
                    'metadata' => [
                        'cart_item_id' => $item->id,
                    ],
                ]);
            }

            $lockedCart->forceFill(['status' => 'ordered'])->save();

            Log::channel('catalogue')->info('order.created', [
                'order_id' => $order->id,
                'cart_id' => $lockedCart->id,
                'user_id' => $lockedCart->user_id,
            ]);

            return $order->fresh()->load('items');
        });
    }

    protected function toCents($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) round(((float) $value) * 100);
    }

    protected function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    protected function snapshotAddress(Address $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'first_name' => $address->first_name,
            'last_name' => $address->last_name,
            'company' => $address->company,
            'phone' => $address->phone,
            'line1' => $address->line1,
            'line2' => $address->line2,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'country_code' => $address->country_code,
        ];
    }
}
