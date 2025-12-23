<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_coupon(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/v1/admin/coupons', [
            'code' => 'SAVE10',
            'type' => 'fixed',
            'value' => 10.00,
            'min_order_amount' => 50.00,
            'usage_limit' => 100,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'SAVE10')
            ->assertJsonPath('data.type', 'fixed');

        $this->assertDatabaseHas('coupons', [
            'code' => 'SAVE10',
            'value' => '10.00',
        ]);
    }

    public function test_customer_cannot_create_coupon(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/admin/coupons', [
            'code' => 'SAVE10',
            'type' => 'fixed',
            'value' => 10.00,
        ]);

        $response->assertStatus(403);
    }

    public function test_can_validate_fixed_coupon(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create(['price' => 100.00]);
        
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'subtotal' => 100.00,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_id' => $product->id,
            'purchasable_type' => Product::class,
            'quantity' => 1,
            'price' => 100.00,
        ]);

        $coupon = Coupon::factory()->create([
            'code' => 'SAVE10',
            'type' => 'fixed',
            'value' => 10.00,
            'min_order_amount' => 50.00,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'SAVE10',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('valid', true)
            ->assertJsonPath('discount_amount', 10.00);
    }

    public function test_can_validate_percentage_coupon(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create(['price' => 100.00]);
        
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'subtotal' => 100.00,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_id' => $product->id,
            'purchasable_type' => Product::class,
            'quantity' => 1,
            'price' => 100.00,
        ]);

        $coupon = Coupon::factory()->create([
            'code' => 'SAVE20',
            'type' => 'percentage',
            'value' => 20,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'SAVE20',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('valid', true)
            ->assertJsonPath('discount_amount', 20.00);
    }

    public function test_cannot_use_expired_coupon(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'subtotal' => 100.00,
        ]);

        $coupon = Coupon::factory()->create([
            'code' => 'EXPIRED',
            'type' => 'fixed',
            'value' => 10.00,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'EXPIRED',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('valid', false);
    }

    public function test_cannot_use_coupon_below_minimum_amount(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'subtotal' => 30.00,
        ]);

        $coupon = Coupon::factory()->create([
            'code' => 'SAVE10',
            'type' => 'fixed',
            'value' => 10.00,
            'min_order_amount' => 50.00,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'SAVE10',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('valid', false);
    }

    public function test_admin_can_update_coupon(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $coupon = Coupon::factory()->create([
            'code' => 'SAVE10',
            'value' => 10.00,
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->patchJson("/api/v1/admin/coupons/{$coupon->id}", [
            'value' => 15.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.value', '15.00');
    }

    public function test_admin_can_delete_coupon(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $coupon = Coupon::factory()->create();

        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson("/api/v1/admin/coupons/{$coupon->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }
}
