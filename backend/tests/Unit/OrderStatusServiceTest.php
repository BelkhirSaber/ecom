<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\Order\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderStatusService();
        Event::fake();
    }

    public function test_can_transition_returns_true_for_valid_transitions(): void
    {
        $this->assertTrue($this->service->canTransition('pending', 'processing'));
        $this->assertTrue($this->service->canTransition('pending', 'cancelled'));
        $this->assertTrue($this->service->canTransition('processing', 'shipped'));
        $this->assertTrue($this->service->canTransition('shipped', 'delivered'));
    }

    public function test_can_transition_returns_false_for_invalid_transitions(): void
    {
        $this->assertFalse($this->service->canTransition('pending', 'delivered'));
        $this->assertFalse($this->service->canTransition('cancelled', 'processing'));
        $this->assertFalse($this->service->canTransition('delivered', 'pending'));
        $this->assertFalse($this->service->canTransition('pending', 'pending'));
    }

    public function test_get_allowed_transitions_returns_correct_statuses(): void
    {
        $allowed = $this->service->getAllowedTransitions('pending');
        $this->assertContains('processing', $allowed);
        $this->assertContains('cancelled', $allowed);
        $this->assertContains('pending_cod', $allowed);

        $allowed = $this->service->getAllowedTransitions('processing');
        $this->assertContains('shipped', $allowed);
        $this->assertContains('cancelled', $allowed);
    }

    public function test_transition_updates_order_status(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $updatedOrder = $this->service->transition($order, 'processing', 1, 'Test transition');

        $this->assertEquals('processing', $updatedOrder->status);
        $order->refresh();
        $this->assertEquals('processing', $order->status);
    }

    public function test_transition_throws_exception_for_invalid_transition(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $this->expectException(ValidationException::class);
        $this->service->transition($order, 'delivered', 1);
    }

    public function test_transition_dispatches_event(): void
    {
        Event::fake();
        $order = Order::factory()->create(['status' => 'pending']);

        $this->service->transition($order, 'processing', 1);

        Event::assertDispatched(\App\Events\OrderStatusChanged::class, function ($event) use ($order) {
            return $event->order->id === $order->id
                && $event->oldStatus === 'pending'
                && $event->newStatus === 'processing';
        });
    }

    public function test_get_all_statuses_returns_all_available_statuses(): void
    {
        $statuses = $this->service->getAllStatuses();

        $this->assertContains('pending', $statuses);
        $this->assertContains('processing', $statuses);
        $this->assertContains('shipped', $statuses);
        $this->assertContains('delivered', $statuses);
        $this->assertContains('cancelled', $statuses);
    }
}
