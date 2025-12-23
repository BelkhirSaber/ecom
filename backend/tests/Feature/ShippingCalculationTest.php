<?php

namespace Tests\Feature;

use Tests\TestCase;

class ShippingCalculationTest extends TestCase
{
    public function test_get_shipping_methods(): void
    {
        $response = $this->getJson('/api/v1/shipping/methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'key',
                        'label',
                        'description',
                        'calculation_type',
                    ],
                ],
            ]);

        $methods = $response->json('data');
        $this->assertNotEmpty($methods);
    }

    public function test_calculate_shipping_for_france(): void
    {
        $response = $this->postJson('/api/v1/shipping/calculate', [
            'country_code' => 'FR',
            'postal_code' => '75001',
            'cart_total' => 30.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'method_key',
                        'zone_key',
                        'label',
                        'description',
                        'zone_label',
                        'price',
                        'currency',
                        'calculation_type',
                        'is_free',
                    ],
                ],
                'meta' => [
                    'count',
                    'address',
                ],
            ]);

        $options = $response->json('data');
        $this->assertNotEmpty($options);
    }

    public function test_calculate_shipping_with_free_threshold(): void
    {
        $response = $this->postJson('/api/v1/shipping/calculate', [
            'country_code' => 'FR',
            'postal_code' => '75001',
            'cart_total' => 60.00, // Au-dessus de 50€
        ]);

        $response->assertStatus(200);

        $options = $response->json('data');
        $standard = collect($options)->firstWhere('method_key', 'standard');

        $this->assertNotNull($standard);
        $this->assertEquals(0.0, $standard['price']);
        $this->assertTrue($standard['is_free']);
    }

    public function test_calculate_shipping_for_corse(): void
    {
        $response = $this->postJson('/api/v1/shipping/calculate', [
            'country_code' => 'FR',
            'postal_code' => '20000',
            'cart_total' => 30.00,
        ]);

        $response->assertStatus(200);

        $options = $response->json('data');
        $standard = collect($options)->firstWhere('method_key', 'standard');

        $this->assertNotNull($standard);
        $this->assertEquals(12.90, $standard['price']);
        $this->assertEquals('Corse', $standard['zone_label']);
    }

    public function test_calculate_shipping_for_europe(): void
    {
        $response = $this->postJson('/api/v1/shipping/calculate', [
            'country_code' => 'BE',
            'postal_code' => '1000',
            'cart_total' => 30.00,
        ]);

        $response->assertStatus(200);

        $options = $response->json('data');
        $standard = collect($options)->firstWhere('method_key', 'standard');

        $this->assertNotNull($standard);
        $this->assertEquals(15.90, $standard['price']);
        $this->assertEquals('Europe', $standard['zone_label']);
    }

    public function test_calculate_specific_method(): void
    {
        $response = $this->postJson('/api/v1/shipping/calculate-method', [
            'method_key' => 'standard',
            'zone_key' => 'france_metro',
            'cart_total' => 30.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'method_key',
                    'zone_key',
                    'cost',
                    'currency',
                ],
            ]);

        $this->assertEquals(5.90, $response->json('data.cost'));
    }

    public function test_calculate_method_returns_404_for_invalid_method(): void
    {
        $response = $this->postJson('/api/v1/shipping/calculate-method', [
            'method_key' => 'invalid',
            'zone_key' => 'invalid',
            'cart_total' => 30.00,
        ]);

        $response->assertStatus(404);
    }

    public function test_calculate_shipping_requires_country_code(): void
    {
        $response = $this->postJson('/api/v1/shipping/calculate', [
            'postal_code' => '75001',
            'cart_total' => 30.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country_code']);
    }

    public function test_calculate_shipping_requires_cart_total(): void
    {
        $response = $this->postJson('/api/v1/shipping/calculate', [
            'country_code' => 'FR',
            'postal_code' => '75001',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cart_total']);
    }

    public function test_calculate_shipping_with_weight(): void
    {
        config(['shipping.methods.weight_based.enabled' => true]);

        $response = $this->postJson('/api/v1/shipping/calculate', [
            'country_code' => 'FR',
            'postal_code' => '75001',
            'cart_total' => 30.00,
            'cart_weight' => 2.5,
        ]);

        $response->assertStatus(200);

        $options = $response->json('data');
        $this->assertNotEmpty($options);
    }
}
