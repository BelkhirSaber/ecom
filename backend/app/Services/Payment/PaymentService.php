<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private DatabaseManager $db,
        private PaymentProviderResolver $resolver
    ) {
    }

    public function allowedProviders(): array
    {
        return $this->resolver->allowed();
    }

    public function createForOrder(Order $order, ?int $userId = null, ?string $provider = null): Payment
    {
        if ($order->status !== 'pending') {
            throw ValidationException::withMessages([
                'order' => ['Order is not payable in its current status.'],
            ]);
        }
        $providerName = $this->resolver->normalize($provider);
        $providerInstance = $this->resolver->resolve($providerName);

        return $this->db->transaction(function () use ($order, $userId, $providerName, $providerInstance) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $userId,
                'provider' => $providerName,
                'status' => 'requires_action',
                'currency' => (string) ($order->currency ?? 'USD'),
                'amount' => $order->grand_total,
                'metadata' => [],
            ]);

            try {
                $providerResult = $providerInstance->createIntent($order, $payment);
            } catch (\Throwable $e) {
                $payment->forceFill([
                    'status' => 'failed',
                    'error_code' => $e::class,
                    'error_message' => $e->getMessage(),
                ])->save();

                Log::channel('catalogue')->error('payment.provider_error', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'provider' => $payment->provider,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);

                throw ValidationException::withMessages([
                    'payment' => ['Unable to create payment at this time.'],
                ]);
            }

            $payment->forceFill([
                'provider_reference' => $providerResult['provider_reference'] ?? null,
                'client_secret' => $providerResult['client_secret'] ?? null,
                'checkout_url' => $providerResult['checkout_url'] ?? null,
                'status' => $providerResult['status'] ?? $payment->status,
                'metadata' => $providerResult['metadata'] ?? $payment->metadata,
            ])->save();

            if (! empty($providerResult['order_status'])) {
                $order->forceFill([
                    'status' => (string) $providerResult['order_status'],
                ])->save();
            }

            Log::channel('catalogue')->info('payment.created', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'user_id' => $userId,
                'provider' => $payment->provider,
                'status' => $payment->status,
            ]);

            return $payment;
        });
    }
}
