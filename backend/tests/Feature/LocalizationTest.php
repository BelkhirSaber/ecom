<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_i18n_config_endpoint_returns_available_locales(): void
    {
        $response = $this->getJson('/api/v1/config/i18n');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current',
                'default',
                'fallback',
                'available' => [
                    '*' => ['code', 'label', 'native_label', 'dir']
                ]
            ]);
    }

    public function test_locale_middleware_sets_locale_from_query_param(): void
    {
        $response = $this->getJson('/api/v1/config/i18n?lang=en');

        $response->assertStatus(200)
            ->assertJson(['current' => 'en']);
    }

    public function test_locale_middleware_sets_locale_from_header(): void
    {
        $response = $this->getJson('/api/v1/config/i18n', ['X-Locale' => 'ar']);

        $response->assertStatus(200)
            ->assertJson(['current' => 'ar']);
    }

    public function test_locale_middleware_falls_back_to_default_for_invalid_locale(): void
    {
        $response = $this->getJson('/api/v1/config/i18n?lang=invalid');

        $response->assertStatus(200);
        $default = $response->json('default');
        $this->assertEquals($default, $response->json('current'));
    }

    public function test_page_model_returns_translated_title(): void
    {
        $page = Page::create([
            'title' => 'Titre français',
            'title_translations' => [
                'fr' => 'Titre français',
                'en' => 'English title',
                'ar' => 'عنوان عربي'
            ],
            'content' => 'Contenu',
            'slug' => 'test-page',
            'is_published' => true
        ]);

        app()->setLocale('en');
        $page = $page->fresh();
        $this->assertEquals('English title', $page->title);

        app()->setLocale('ar');
        $page = $page->fresh();
        $this->assertEquals('عنوان عربي', $page->title);

        app()->setLocale('fr');
        $page = $page->fresh();
        $this->assertEquals('Titre français', $page->title);
    }

    public function test_product_model_returns_translated_name(): void
    {
        $product = Product::create([
            'sku' => 'TEST-SKU',
            'type' => 'simple',
            'name' => 'Nom français',
            'slug' => 'nom-francais',
            'name_translations' => [
                'fr' => 'Nom français',
                'en' => 'English name',
                'ar' => 'اسم عربي'
            ],
            'price' => 100,
            'currency' => 'USD',
            'is_active' => true
        ]);

        app()->setLocale('en');
        $product = $product->fresh();
        $this->assertEquals('English name', $product->name);

        app()->setLocale('ar');
        $product = $product->fresh();
        $this->assertEquals('اسم عربي', $product->name);

        app()->setLocale('fr');
        $product = $product->fresh();
        $this->assertEquals('Nom français', $product->name);
    }

    public function test_promotion_model_returns_translated_name(): void
    {
        $promotion = Promotion::create([
            'name' => 'Promotion française',
            'name_translations' => [
                'fr' => 'Promotion française',
                'en' => 'English promotion',
                'ar' => 'عرض عربي'
            ],
            'type' => 'cart',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true
        ]);

        app()->setLocale('en');
        $this->assertEquals('English promotion', $promotion->fresh()->name);

        app()->setLocale('ar');
        $this->assertEquals('عرض عربي', $promotion->fresh()->name);

        app()->setLocale('fr');
        $this->assertEquals('Promotion française', $promotion->fresh()->name);
    }

    public function test_admin_can_create_page_with_translations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/v1/admin/pages?lang=en', [
            'title' => 'Test Page EN',
            'content' => 'Test content EN',
            'slug' => 'test-page-localized',
            'is_published' => true
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title_translations.en', 'Test Page EN');

        $pageId = $response->json('data.id');

        $updateResponse = $this->patchJson("/api/v1/admin/pages/{$pageId}?lang=ar", [
            'title' => 'صفحة اختبار',
            'content' => 'محتوى الاختبار'
        ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.title_translations.ar', 'صفحة اختبار');
    }

    public function test_admin_can_create_product_with_translations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/v1/products?lang=en', [
            'sku' => 'LOCALIZED-PRODUCT',
            'type' => 'simple',
            'name' => 'Test product',
            'price' => 50,
            'currency' => 'USD'
        ]);

        $response->assertStatus(201);
        $productId = $response->json('data.id');
        $this->assertNotNull($productId);

        $product = Product::find($productId);
        $this->assertNotNull($product->name_translations);
        $this->assertEquals('Test product', $product->name_translations['en']);

        $updateResponse = $this->patchJson("/api/v1/products/{$productId}?lang=ar", [
            'name' => 'منتج اختبار'
        ]);

        $updateResponse->assertStatus(200);
        $product = Product::find($productId);
        $this->assertEquals('منتج اختبار', $product->name_translations['ar']);
    }

    public function test_pages_endpoint_returns_localized_content(): void
    {
        Page::create([
            'title' => 'Page FR',
            'title_translations' => [
                'fr' => 'Page FR',
                'en' => 'Page EN'
            ],
            'content' => 'Contenu FR',
            'content_translations' => [
                'fr' => 'Contenu FR',
                'en' => 'Content EN'
            ],
            'slug' => 'localized-page',
            'is_published' => true
        ]);

        $response = $this->getJson('/api/v1/pages?lang=en');

        $response->assertStatus(200);
        $page = Page::first();
        app()->setLocale('en');
        $this->assertEquals('Page EN', $page->fresh()->title);
    }

    public function test_products_endpoint_returns_localized_content(): void
    {
        Product::create([
            'sku' => 'PROD-001',
            'type' => 'simple',
            'name' => 'Produit FR',
            'slug' => 'produit-fr',
            'name_translations' => [
                'fr' => 'Produit FR',
                'en' => 'Product EN'
            ],
            'price' => 100,
            'currency' => 'USD',
            'is_active' => true
        ]);

        app()->setLocale('en');
        $response = $this->getJson('/api/v1/products?lang=en');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Product EN');
    }
}
