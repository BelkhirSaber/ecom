<?php

namespace Tests\Unit;

use App\Services\Shipping\ShippingService;
use Tests\TestCase;

class ShippingServiceTest extends TestCase
{
    protected ShippingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShippingService();
        config(['shipping.enabled' => true]);
    }

    public function test_calculate_shipping_options_returns_empty_when_disabled(): void
    {
        config(['shipping.enabled' => false]);

        $options = $this->service->calculateShippingOptions(
            ['country_code' => 'FR', 'postal_code' => '75001'],
            100.0
        );

        $this->assertEmpty($options);
    }

    public function test_calculate_shipping_options_for_france_metro(): void
    {
        $options = $this->service->calculateShippingOptions(
            ['country_code' => 'FR', 'postal_code' => '75001', 'state' => ''],
            30.0
        );

        $this->assertNotEmpty($options);
        
        $standard = collect($options)->firstWhere('method_key', 'standard');
        $this->assertNotNull($standard);
        $this->assertEquals(5.90, $standard['price']);
        $this->assertEquals('Livraison Standard', $standard['label']);
    }

    public function test_free_shipping_above_threshold(): void
    {
        $options = $this->service->calculateShippingOptions(
            ['country_code' => 'FR', 'postal_code' => '75001', 'state' => ''],
            60.0 // Au-dessus de 50€
        );

        $standard = collect($options)->firstWhere('method_key', 'standard');
        $this->assertNotNull($standard);
        $this->assertEquals(0.0, $standard['price']);
        $this->assertTrue($standard['is_free']);
    }

    public function test_calculate_shipping_for_corse(): void
    {
        $options = $this->service->calculateShippingOptions(
            ['country_code' => 'FR', 'postal_code' => '20000', 'state' => ''],
            30.0
        );

        $standard = collect($options)->firstWhere('method_key', 'standard');
        $this->assertNotNull($standard);
        $this->assertEquals(12.90, $standard['price']);
        $this->assertEquals('Corse', $standard['zone_label']);
    }

    public function test_calculate_shipping_for_europe(): void
    {
        $options = $this->service->calculateShippingOptions(
            ['country_code' => 'BE', 'postal_code' => '1000', 'state' => ''],
            30.0
        );

        $standard = collect($options)->firstWhere('method_key', 'standard');
        $this->assertNotNull($standard);
        $this->assertEquals(15.90, $standard['price']);
        $this->assertEquals('Europe', $standard['zone_label']);
    }

    public function test_express_shipping_for_idf(): void
    {
        $options = $this->service->calculateShippingOptions(
            ['country_code' => 'FR', 'postal_code' => '92000', 'state' => ''],
            30.0
        );

        $express = collect($options)->firstWhere('method_key', 'express');
        $this->assertNotNull($express);
        $this->assertEquals(9.90, $express['price']);
        $this->assertEquals('Île-de-France', $express['zone_label']);
    }

    public function test_store_pickup_is_free(): void
    {
        config(['shipping.methods.store_pickup.enabled' => true]);

        $options = $this->service->calculateShippingOptions(
            ['country_code' => 'FR', 'postal_code' => '75001', 'state' => ''],
            30.0
        );

        $pickup = collect($options)->firstWhere('method_key', 'store_pickup');
        $this->assertNotNull($pickup);
        $this->assertEquals(0.0, $pickup['price']);
        $this->assertTrue($pickup['is_free']);
    }

    public function test_calculate_shipping_cost_for_specific_method(): void
    {
        $cost = $this->service->calculateShippingCost('standard', 'france_metro', 30.0);

        $this->assertEquals(5.90, $cost);
    }

    public function test_calculate_shipping_cost_returns_null_for_invalid_method(): void
    {
        $cost = $this->service->calculateShippingCost('invalid_method', 'invalid_zone', 30.0);

        $this->assertNull($cost);
    }

    public function test_get_all_methods_returns_enabled_methods(): void
    {
        $methods = $this->service->getAllMethods();

        $this->assertNotEmpty($methods);
        
        $methodKeys = collect($methods)->pluck('key')->toArray();
        $this->assertContains('standard', $methodKeys);
        $this->assertContains('express', $methodKeys);
    }

    public function test_postal_code_wildcard_matching(): void
    {
        // Test Corse (20*)
        $optionsCorse = $this->service->calculateShippingOptions(
            ['country_code' => 'FR', 'postal_code' => '20100', 'state' => ''],
            30.0
        );

        $standard = collect($optionsCorse)->firstWhere('method_key', 'standard');
        $this->assertEquals('Corse', $standard['zone_label']);

        // Test IDF (75*)
        $optionsIdf = $this->service->calculateShippingOptions(
            ['country_code' => 'FR', 'postal_code' => '75008', 'state' => ''],
            30.0
        );

        $express = collect($optionsIdf)->firstWhere('method_key', 'express');
        $this->assertEquals('Île-de-France', $express['zone_label']);
    }

    public function test_excluded_postal_codes(): void
    {
        // Corse (20*) doit être exclue de france_metro
        $options = $this->service->calculateShippingOptions(
            ['country_code' => 'FR', 'postal_code' => '20000', 'state' => ''],
            30.0
        );

        $standard = collect($options)->firstWhere('method_key', 'standard');
        // Doit matcher france_corse, pas france_metro
        $this->assertEquals('Corse', $standard['zone_label']);
        $this->assertEquals(12.90, $standard['price']);
    }
}
