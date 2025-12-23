<?php

namespace App\Repositories\Eloquent;

use App\Models\Cart;
use App\Repositories\Contracts\CartRepositoryInterface;

class EloquentCartRepository implements CartRepositoryInterface
{
    public function lockForOrderCreation(int $cartId): Cart
    {
        $lockedCart = Cart::query()
            ->whereKey($cartId)
            ->lockForUpdate()
            ->firstOrFail();

        $items = $lockedCart->items()->with('purchasable')->lockForUpdate()->get();
        $lockedCart->setRelation('items', $items);

        return $lockedCart;
    }
}
