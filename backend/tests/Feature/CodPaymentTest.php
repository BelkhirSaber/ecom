<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cod.enabled' => true]);
        config(['services.payments.provider' => 'cod']);
        config(['services.payments.allowed' => ['fake', 'stripe', 'paypal', 'cod']]);
    }

    public function test_cod_payment_succeeds_for_eligible_zone(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $address = Address::factory()->create([
            'user_id' => $user->id,
            'country_code' => 'FR',
            'postal_code' => '75001',
            'state' => '',
        ]);

        $product = Product::factory()->create(['price' => 100.00, 'stock_quantity' => 10]);
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => Product::class,
            'purchasable_id' => $product->id,
            'quantity' => 1,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'cart_id' => $cart->id,
            'status' => 'pending',
            'grand_total' => 100.00,
            'shipping_address_id' => $address->id,
            'shipping_address' => [
                'country_code' => 'FR',
                'postal_code' => '75001',
                'state' => '',
            ],
        ]);

        $response = $this->postJson("/api/v1/orders/{$order->id}/payments", [
            'provider' => 'cod',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.provider', 'cod')
            ->assertJsonPath('data.status', 'pending_cod')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'provider',
                    'provider_reference',
                    'status',
                    'metadata',
                ],
            ]);

        $this->assertStringStartsWith('cod_', $response->json('data.provider_reference'));
        $this->assertArrayHasKey('zone_key', $response->json('data.metadata'));

        $order->refresh();
        $this->assertEquals('pending_cod', $order->status);
    }

    public function test_cod_payment_fails_for_ineligible_zone(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $address = Address::factory()->create([
            'user_id' => $user->id,
            'country_code' => 'US',
            'postal_code' => '10001',
            'state' => 'NY',
        ]);

        $product = Product::factory()->create(['price' => 100.00, 'stock_quantity' => 10]);
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => Product::class,
            'purchasable_id' => $product->id,
            'quantity' => 1,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'cart_id' => $cart->id,
            'status' => 'pending',
            'grand_total' => 100.00,
            'shipping_address_id' => $address->id,
            'shipping_address' => [
                'country_code' => 'US',
                'postal_code' => '10001',
                'state' => 'NY',
            ],
        ]);

        $response = $this->postJson("/api/v1/orders/{$order->id}/payments", [
            'provider' => 'cod',
        ]);

        $response->assertStatus(422);
    }

    public function test_cod_disabled_when_config_false(): void
    {
        config(['cod.enabled' => false]);

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $address = Address::factory()->create([
            'user_id' => $user->id,
            'country_code' => 'FR',
            'postal_code' => '75001',
        ]);

        $product = Product::factory()->create(['price' => 100.00, 'stock_quantity' => 10]);
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => Product::class,
            'purchasable_id' => $product->id,
            'quantity' => 1,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'cart_id' => $cart->id,
            'status' => 'pending',
            'grand_total' => 100.00,
            'shipping_address_id' => $address->id,
            'shipping_address' => [
                'country_code' => 'FR',
                'postal_code' => '75001',
                'state' => '',
            ],
        ]);

        $response = $this->postJson("/api/v1/orders/{$order->id}/payments", [
            'provider' => 'cod',
        ]);

        $response->assertStatus(422);
    }
}
