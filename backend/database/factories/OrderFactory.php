<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 500);
        $discountTotal = fake()->randomFloat(2, 0, $subtotal * 0.2);
        $shippingTotal = fake()->randomFloat(2, 0, 50);
        $taxTotal = fake()->randomFloat(2, 0, $subtotal * 0.2);
        $grandTotal = $subtotal - $discountTotal + $shippingTotal + $taxTotal;

        return [
            'user_id' => User::factory(),
            'cart_id' => Cart::factory(),
            'status' => 'pending',
            'currency' => 'USD',
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'shipping_total' => $shippingTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'shipping_address_id' => null,
            'billing_address_id' => null,
            'shipping_address' => null,
            'billing_address' => null,
            'placed_at' => null,
        ];
    }
}
