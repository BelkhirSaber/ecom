<?php

namespace App\Repositories\Contracts;

use App\Models\Cart;

interface CartRepositoryInterface
{
    public function lockForOrderCreation(int $cartId): Cart;
}
