<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_tracking_to_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'processing']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->patchJson("/api/v1/orders/{$order->id}/tracking", [
            'tracking_number' => 'TRACK123456',
            'tracking_carrier' => 'DHL',
            'tracking_url' => 'https://dhl.com/track/TRACK123456',
        ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals('TRACK123456', $order->tracking_number);
        $this->assertEquals('DHL', $order->tracking_carrier);
        $this->assertEquals('shipped', $order->status);
        $this->assertNotNull($order->shipped_at);
    }

    public function test_customer_cannot_add_tracking(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->patchJson("/api/v1/orders/{$order->id}/tracking", [
            'tracking_number' => 'TRACK123456',
        ]);

        $response->assertStatus(403);
    }

    public function test_customer_can_view_tracking_of_own_order(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'shipped',
            'tracking_number' => 'TRACK123456',
            'tracking_carrier' => 'DHL',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/v1/orders/{$order->id}/tracking");

        $response->assertStatus(200)
            ->assertJsonPath('data.tracking_number', 'TRACK123456')
            ->assertJsonPath('data.tracking_carrier', 'DHL');
    }

    public function test_admin_can_mark_order_as_delivered(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'shipped']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson("/api/v1/orders/{$order->id}/mark-delivered");

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals('delivered', $order->status);
        $this->assertNotNull($order->delivered_at);
    }

    public function test_cannot_mark_non_shipped_order_as_delivered(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'processing']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson("/api/v1/orders/{$order->id}/mark-delivered");

        $response->assertStatus(422);
    }
}
