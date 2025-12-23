<?php

namespace App\Services\Payment\Providers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

class FakePaymentProvider implements PaymentProviderInterface
{
    public function createIntent(Order $order, Payment $payment): array
    {
        $providerReference = 'fake_pi_' . Str::uuid();
        $clientSecret = 'fake_secret_' . Str::uuid();

        return [
            'provider_reference' => $providerReference,
            'client_secret' => $clientSecret,
            'checkout_url' => 'https://example.test/pay/' . $payment->id,
            'status' => 'requires_action',
            'metadata' => [
                'mode' => 'fake',
            ],
        ];
    }
}
