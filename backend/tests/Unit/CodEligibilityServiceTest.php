<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\Payment\CodEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CodEligibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CodEligibilityService();
    }

    public function test_is_enabled_returns_false_when_disabled(): void
    {
        config(['cod.enabled' => false]);
        $this->assertFalse($this->service->isEnabled());
    }

    public function test_is_enabled_returns_true_when_enabled(): void
    {
        config(['cod.enabled' => true]);
        $this->assertTrue($this->service->isEnabled());
    }

    public function test_resolve_zone_returns_null_when_disabled(): void
    {
        config(['cod.enabled' => false]);
        $order = Order::factory()->make([
            'shipping_address' => [
                'country_code' => 'FR',
                'postal_code' => '75001',
            ],
        ]);

        $zone = $this->service->resolveZoneForOrder($order);
        $this->assertNull($zone);
    }

    public function test_resolve_zone_matches_france_idf_postal_code(): void
    {
        config(['cod.enabled' => true]);
        config(['cod.zones' => [
            'france_idf' => [
                'label' => 'France - Île-de-France',
                'countries' => ['FR'],
                'postal_codes' => ['75*', '92*', '93*', '94*'],
            ],
        ]]);

        $order = Order::factory()->make([
            'shipping_address' => [
                'country_code' => 'FR',
                'postal_code' => '75008',
                'state' => '',
            ],
        ]);

        $zone = $this->service->resolveZoneForOrder($order);
        $this->assertNotNull($zone);
        $this->assertEquals('france_idf', $zone['key']);
        $this->assertEquals('France - Île-de-France', $zone['label']);
    }

    public function test_resolve_zone_returns_null_for_non_matching_postal_code(): void
    {
        config(['cod.enabled' => true]);
        config(['cod.zones' => [
            'france_idf' => [
                'label' => 'France - Île-de-France',
                'countries' => ['FR'],
                'postal_codes' => ['75*', '92*', '93*', '94*'],
            ],
        ]]);

        $order = Order::factory()->make([
            'shipping_address' => [
                'country_code' => 'FR',
                'postal_code' => '13001',
                'state' => '',
            ],
        ]);

        $zone = $this->service->resolveZoneForOrder($order);
        $this->assertNull($zone);
    }

    public function test_resolve_zone_returns_null_for_non_matching_country(): void
    {
        config(['cod.enabled' => true]);
        config(['cod.zones' => [
            'france_idf' => [
                'label' => 'France - Île-de-France',
                'countries' => ['FR'],
                'postal_codes' => ['75*'],
            ],
        ]]);

        $order = Order::factory()->make([
            'shipping_address' => [
                'country_code' => 'US',
                'postal_code' => '75001',
                'state' => 'TX',
            ],
        ]);

        $zone = $this->service->resolveZoneForOrder($order);
        $this->assertNull($zone);
    }

    public function test_resolve_zone_handles_missing_address(): void
    {
        config(['cod.enabled' => true]);
        $order = Order::factory()->make([
            'shipping_address' => null,
        ]);

        $zone = $this->service->resolveZoneForOrder($order);
        $this->assertNull($zone);
    }

    public function test_resolve_zone_handles_missing_country_code(): void
    {
        config(['cod.enabled' => true]);
        $order = Order::factory()->make([
            'shipping_address' => [
                'postal_code' => '75001',
            ],
        ]);

        $zone = $this->service->resolveZoneForOrder($order);
        $this->assertNull($zone);
    }

    public function test_postal_code_wildcard_matching(): void
    {
        config(['cod.enabled' => true]);
        config(['cod.zones' => [
            'test_zone' => [
                'label' => 'Test Zone',
                'countries' => ['FR'],
                'postal_codes' => ['75*', '920*'],
            ],
        ]]);

        $testCases = [
            ['75001', true],
            ['75999', true],
            ['92000', true],
            ['92099', true],
            ['92100', false],
            ['76001', false],
        ];

        foreach ($testCases as [$postalCode, $shouldMatch]) {
            $order = Order::factory()->make([
                'shipping_address' => [
                    'country_code' => 'FR',
                    'postal_code' => $postalCode,
                    'state' => '',
                ],
            ]);

            $zone = $this->service->resolveZoneForOrder($order);
            if ($shouldMatch) {
                $this->assertNotNull($zone, "Postal code {$postalCode} should match");
            } else {
                $this->assertNull($zone, "Postal code {$postalCode} should not match");
            }
        }
    }
}
