<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_active_blocks(): void
    {
        Block::factory()->count(3)->create(['is_active' => true]);
        Block::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/blocks');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_view_active_block_by_key(): void
    {
        $block = Block::factory()->create([
            'key' => 'home-slider',
            'type' => 'slider',
            'title' => 'Main Slider',
            'content' => ['slides' => []],
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/blocks/home-slider');

        $response->assertStatus(200)
            ->assertJsonPath('data.key', 'home-slider')
            ->assertJsonPath('data.type', 'slider');
    }

    public function test_cannot_view_inactive_block(): void
    {
        $block = Block::factory()->create([
            'key' => 'inactive-block',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/blocks/inactive-block');

        $response->assertStatus(404);
    }

    public function test_admin_can_create_slider_block(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/v1/admin/blocks', [
            'key' => 'home-slider',
            'type' => 'slider',
            'title' => 'Homepage Slider',
            'content' => [
                'slides' => [
                    [
                        'image' => '/images/slide1.jpg',
                        'title' => 'New Collection',
                        'link' => '/products',
                    ],
                ],
                'autoplay' => true,
                'interval' => 5000,
            ],
            'order' => 1,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.key', 'home-slider')
            ->assertJsonPath('data.type', 'slider');

        $this->assertDatabaseHas('blocks', [
            'key' => 'home-slider',
            'type' => 'slider',
        ]);
    }

    public function test_admin_can_create_featured_products_block(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/v1/admin/blocks', [
            'key' => 'featured-products',
            'type' => 'featured_products',
            'title' => 'Our Favorites',
            'content' => [
                'product_ids' => [1, 2, 3, 4],
                'display_mode' => 'grid',
                'columns' => 4,
            ],
            'order' => 2,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'featured_products');
    }

    public function test_customer_cannot_create_block(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/admin/blocks', [
            'key' => 'test-block',
            'type' => 'text',
            'content' => ['text' => 'Test'],
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_block(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $block = Block::factory()->create([
            'key' => 'banner',
            'content' => ['text' => 'Old text'],
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->patchJson("/api/v1/admin/blocks/{$block->id}", [
            'content' => ['text' => 'New text'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.content.text', 'New text');
    }

    public function test_admin_can_delete_block(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $block = Block::factory()->create();

        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson("/api/v1/admin/blocks/{$block->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('blocks', ['id' => $block->id]);
    }

    public function test_blocks_are_ordered_correctly(): void
    {
        Block::factory()->create([
            'key' => 'third',
            'order' => 3,
            'is_active' => true,
        ]);

        Block::factory()->create([
            'key' => 'first',
            'order' => 1,
            'is_active' => true,
        ]);

        Block::factory()->create([
            'key' => 'second',
            'order' => 2,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/blocks');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEquals('first', $data[0]['key']);
        $this->assertEquals('second', $data[1]['key']);
        $this->assertEquals('third', $data[2]['key']);
    }

    public function test_block_type_validation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/v1/admin/blocks', [
            'key' => 'invalid-block',
            'type' => 'invalid_type',
            'content' => [],
        ]);

        $response->assertStatus(422);
    }
}
