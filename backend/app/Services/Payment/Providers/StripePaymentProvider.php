<?php

namespace App\Services\Payment\Providers;

use App\Models\Order;
use App\Models\Payment;

class StripePaymentProvider implements PaymentProviderInterface
{
    public function createIntent(Order $order, Payment $payment): array
    {
        $secretKey = (string) config('services.stripe.secret');
        if ($secretKey === '') {
            throw new \RuntimeException('Stripe is not configured (missing STRIPE_SECRET_KEY).');
        }

        $currency = strtolower((string) ($order->currency ?? 'USD'));
        $amountCents = $this->toCents((string) $order->grand_total);

        if (class_exists(\Stripe\StripeClient::class)) {
            $client = new \Stripe\StripeClient($secretKey);

            $intent = $client->paymentIntents->create([
                'amount' => $amountCents,
                'currency' => $currency,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'payment_id' => (string) $payment->id,
                ],
            ]);

            $intentId = $intent->id ?? null;
            $clientSecret = $intent->client_secret ?? null;
            $status = $intent->status ?? 'requires_action';
        } else {
            $intent = $this->createPaymentIntentViaHttp($secretKey, [
                'amount' => $amountCents,
                'currency' => $currency,
                'automatic_payment_methods' => ['enabled' => 'true'],
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'payment_id' => (string) $payment->id,
                ],
            ]);

            $intentId = $intent['id'] ?? null;
            $clientSecret = $intent['client_secret'] ?? null;
            $status = $intent['status'] ?? 'requires_action';
        }

        return [
            'provider_reference' => $intentId,
            'client_secret' => $clientSecret,
            'status' => $status,
            'metadata' => [
                'amount_cents' => $amountCents,
                'currency' => $currency,
            ],
        ];
    }

    protected function createPaymentIntentViaHttp(string $secretKey, array $payload): array
    {
        if (! function_exists('curl_init')) {
            throw new \RuntimeException('cURL is not available; install stripe/stripe-php or enable ext-curl.');
        }

        $data = $this->flattenPayload($payload);
        $body = http_build_query($data, '', '&');

        $ch = curl_init('https://api.stripe.com/v1/payment_intents');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$secretKey,
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $errno) {
            throw new \RuntimeException('Stripe HTTP request failed: '.$error);
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Stripe HTTP response is not valid JSON.');
        }

        if ($status >= 400) {
            $msg = $decoded['error']['message'] ?? 'Stripe error.';
            throw new \RuntimeException($msg);
        }

        return $decoded;
    }

    protected function flattenPayload(array $payload, string $prefix = ''): array
    {
        $result = [];

        foreach ($payload as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : $prefix.'['.$key.']';

            if (is_array($value)) {
                $result += $this->flattenPayload($value, $fullKey);
                continue;
            }

            if ($value === null) {
                continue;
            }

            $result[$fullKey] = (string) $value;
        }

        return $result;
    }

    protected function toCents(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        if (! preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            return (int) round(((float) $value) * 100);
        }

        $negative = str_starts_with($value, '-');
        if ($negative) {
            $value = ltrim($value, '-');
        }

        [$whole, $decimals] = array_pad(explode('.', $value, 2), 2, '');
        $decimals = substr(str_pad($decimals, 2, '0'), 0, 2);

        $cents = ((int) $whole * 100) + (int) $decimals;

        return $negative ? -$cents : $cents;
    }
}
