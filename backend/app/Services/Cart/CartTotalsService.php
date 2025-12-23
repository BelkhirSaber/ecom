<?php

namespace App\Services\Cart;

use App\Models\Cart;

class CartTotalsService
{
    public function forCart(Cart $cart): array
    {
        $cart->loadMissing('items');

        $currency = (string) ($cart->currency ?? 'USD');

        $subtotalCents = 0;
        foreach ($cart->items as $item) {
            $unitPriceCents = $this->toCents($item->unit_price);
            $qty = (int) ($item->quantity ?? 0);
            $subtotalCents += ($unitPriceCents * $qty);
        }

        $discountCents = 0;
        $shippingCents = 0;
        $taxCents = 0;

        $grandTotalCents = $subtotalCents - $discountCents + $shippingCents + $taxCents;

        return [
            'currency' => $currency,
            'subtotal' => $this->formatCents($subtotalCents),
            'discount_total' => $this->formatCents($discountCents),
            'shipping_total' => $this->formatCents($shippingCents),
            'tax_total' => $this->formatCents($taxCents),
            'grand_total' => $this->formatCents($grandTotalCents),
        ];
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
}
