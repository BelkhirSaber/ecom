<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_request_return_for_delivered_order(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'delivered',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/v1/orders/{$order->id}/returns", [
            'reason' => 'defective',
            'description' => 'Product is broken',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'requested')
            ->assertJsonPath('data.reason', 'defective');

        $order->refresh();
        $this->assertEquals('returned', $order->status);
    }

    public function test_cannot_request_return_for_non_delivered_order(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/v1/orders/{$order->id}/returns", [
            'reason' => 'defective',
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_create_duplicate_return_request(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'delivered',
        ]);

        OrderReturn::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'status' => 'requested',
            'reason' => 'defective',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/v1/orders/{$order->id}/returns", [
            'reason' => 'wrong_item',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_approve_return(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $orderReturn = OrderReturn::factory()->create(['status' => 'requested']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson("/api/v1/returns/{$orderReturn->id}/approve", [
            'refund_amount' => 50.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $orderReturn->refresh();
        $this->assertEquals('approved', $orderReturn->status);
        $this->assertEquals('50.00', $orderReturn->refund_amount);
        $this->assertNotNull($orderReturn->approved_at);
    }

    public function test_customer_cannot_approve_return(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $orderReturn = OrderReturn::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/v1/returns/{$orderReturn->id}/approve");

        $response->assertStatus(403);
    }

    public function test_admin_can_reject_return(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $orderReturn = OrderReturn::factory()->create(['status' => 'requested']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson("/api/v1/returns/{$orderReturn->id}/reject");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected');
    }

    public function test_customer_can_add_tracking_to_approved_return(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $orderReturn = OrderReturn::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->patchJson("/api/v1/returns/{$orderReturn->id}/tracking", [
            'return_tracking_number' => 'RETURN123',
            'return_tracking_carrier' => 'UPS',
        ]);

        $response->assertStatus(200);

        $orderReturn->refresh();
        $this->assertEquals('RETURN123', $orderReturn->return_tracking_number);
    }

    public function test_admin_can_mark_return_as_received(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $orderReturn = OrderReturn::factory()->create(['status' => 'approved']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson("/api/v1/returns/{$orderReturn->id}/mark-received");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'received');

        $orderReturn->refresh();
        $this->assertNotNull($orderReturn->received_at);
    }

    public function test_admin_can_mark_return_as_refunded(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $orderReturn = OrderReturn::factory()->create(['status' => 'received']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson("/api/v1/returns/{$orderReturn->id}/mark-refunded");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'refunded');

        $orderReturn->refresh();
        $this->assertNotNull($orderReturn->refunded_at);
    }

    public function test_customer_can_list_own_returns(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        OrderReturn::factory()->count(3)->create(['user_id' => $user->id]);
        OrderReturn::factory()->count(2)->create(); // Other user's returns

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/v1/returns');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_can_list_all_returns(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        OrderReturn::factory()->count(5)->create();

        $this->actingAs($admin, 'sanctum');

        $response = $this->getJson('/api/v1/returns');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }
}
