<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_published_pages(): void
    {
        Page::factory()->count(3)->create(['is_published' => true]);
        Page::factory()->count(2)->create(['is_published' => false]);

        $response = $this->getJson('/api/v1/pages');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_view_published_page_by_slug(): void
    {
        $page = Page::factory()->create([
            'title' => 'Terms of Service',
            'slug' => 'terms',
            'content' => '<h1>Terms</h1><p>Content...</p>',
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/v1/pages/terms');

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Terms of Service')
            ->assertJsonPath('data.slug', 'terms');
    }

    public function test_cannot_view_unpublished_page(): void
    {
        $page = Page::factory()->create([
            'slug' => 'draft',
            'is_published' => false,
        ]);

        $response = $this->getJson('/api/v1/pages/draft');

        $response->assertStatus(404);
    }

    public function test_admin_can_create_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/v1/admin/pages', [
            'title' => 'Privacy Policy',
            'content' => '<h1>Privacy</h1><p>Our privacy policy...</p>',
            'meta_description' => 'Our privacy policy',
            'is_published' => true,
            'order' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Privacy Policy')
            ->assertJsonPath('data.slug', 'privacy-policy');

        $this->assertDatabaseHas('pages', [
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
        ]);
    }

    public function test_slug_is_auto_generated_from_title(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/v1/admin/pages', [
            'title' => 'Conditions Générales de Vente',
            'content' => '<p>Content</p>',
            'is_published' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'conditions-generales-de-vente');
    }

    public function test_customer_cannot_create_page(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/admin/pages', [
            'title' => 'Test Page',
            'content' => '<p>Content</p>',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $page = Page::factory()->create([
            'title' => 'Old Title',
            'content' => '<p>Old content</p>',
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->patchJson("/api/v1/admin/pages/{$page->id}", [
            'title' => 'New Title',
            'content' => '<p>New content</p>',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'New Title');

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'title' => 'New Title',
        ]);
    }

    public function test_admin_can_delete_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $page = Page::factory()->create();

        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson("/api/v1/admin/pages/{$page->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_pages_are_ordered_correctly(): void
    {
        Page::factory()->create([
            'title' => 'Third',
            'order' => 3,
            'is_published' => true,
        ]);

        Page::factory()->create([
            'title' => 'First',
            'order' => 1,
            'is_published' => true,
        ]);

        Page::factory()->create([
            'title' => 'Second',
            'order' => 2,
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/v1/pages');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEquals('First', $data[0]['title']);
        $this->assertEquals('Second', $data[1]['title']);
        $this->assertEquals('Third', $data[2]['title']);
    }
}
