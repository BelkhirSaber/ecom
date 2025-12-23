<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderStatusWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_order_status(): void
    {
        Event::fake();
        
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'pending']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'processing',
            'reason' => 'Payment confirmed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'processing');

        $order->refresh();
        $this->assertEquals('processing', $order->status);
    }

    public function test_non_admin_cannot_update_order_status(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'processing',
        ]);

        $response->assertStatus(403);
    }

    public function test_customer_can_cancel_own_pending_order(): void
    {
        Event::fake();
        
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/v1/orders/{$order->id}/cancel", [
            'reason' => 'Changed my mind',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_customer_cannot_cancel_processing_order(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_admin_can_cancel_any_order(): void
    {
        Event::fake();
        
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'processing']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson("/api/v1/orders/{$order->id}/cancel", [
            'reason' => 'Admin cancellation',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_invalid_status_transition_returns_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'pending']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'delivered',
        ]);

        $response->assertStatus(422);
    }

    public function test_get_allowed_transitions_returns_valid_statuses(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/v1/orders/{$order->id}/allowed-transitions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_status',
                'allowed_transitions',
            ])
            ->assertJsonPath('current_status', 'pending')
            ->assertJsonFragment(['allowed_transitions' => ['pending_cod', 'processing', 'cancelled']]);
    }

    public function test_customer_cannot_view_other_customer_order(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user1->id]);

        $this->actingAs($user2, 'sanctum');

        $response = $this->getJson("/api/v1/orders/{$order->id}/allowed-transitions");

        $response->assertStatus(403);
    }
}
