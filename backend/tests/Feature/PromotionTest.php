<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_active_promotions(): void
    {
        Promotion::factory()->count(3)->create([
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        Promotion::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/promotions');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_can_create_promotion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/v1/admin/promotions', [
            'name' => 'Black Friday',
            'type' => 'product',
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'applicable_products' => [1, 2, 3],
            'priority' => 100,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Black Friday')
            ->assertJsonPath('data.discount_value', '50.00');

        $this->assertDatabaseHas('promotions', [
            'name' => 'Black Friday',
            'type' => 'product',
        ]);
    }

    public function test_customer_cannot_create_promotion(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/admin/promotions', [
            'name' => 'Test Promo',
            'type' => 'cart',
            'discount_type' => 'fixed',
            'discount_value' => 10,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_promotion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $promotion = Promotion::factory()->create([
            'name' => 'Summer Sale',
            'discount_value' => 20,
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->patchJson("/api/v1/admin/promotions/{$promotion->id}", [
            'discount_value' => 30,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.discount_value', '30.00');
    }

    public function test_admin_can_delete_promotion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $promotion = Promotion::factory()->create();

        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson("/api/v1/admin/promotions/{$promotion->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
    }

    public function test_promotion_with_dates_is_filtered_correctly(): void
    {
        Promotion::factory()->create([
            'is_active' => true,
            'starts_at' => now()->addDay(),
            'expires_at' => now()->addMonth(),
        ]);

        Promotion::factory()->create([
            'is_active' => true,
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->subDay(),
        ]);

        Promotion::factory()->create([
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/promotions');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_promotions_are_ordered_by_priority(): void
    {
        Promotion::factory()->create([
            'name' => 'Low Priority',
            'priority' => 1,
            'is_active' => true,
        ]);

        Promotion::factory()->create([
            'name' => 'High Priority',
            'priority' => 100,
            'is_active' => true,
        ]);

        Promotion::factory()->create([
            'name' => 'Medium Priority',
            'priority' => 50,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/promotions');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEquals('High Priority', $data[0]['name']);
        $this->assertEquals('Medium Priority', $data[1]['name']);
        $this->assertEquals('Low Priority', $data[2]['name']);
    }
}
