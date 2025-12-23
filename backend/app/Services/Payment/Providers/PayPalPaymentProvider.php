<?php

namespace App\Services\Payment\Providers;

use App\Models\Order;
use App\Models\Payment;

class PayPalPaymentProvider implements PaymentProviderInterface
{
    public function createIntent(Order $order, Payment $payment): array
    {
        if (! function_exists('curl_init')) {
            throw new \RuntimeException('cURL is not available; enable ext-curl.');
        }

        $clientId = (string) config('services.paypal.client_id');
        $clientSecret = (string) config('services.paypal.client_secret');

        if ($clientId === '' || $clientSecret === '') {
            throw new \RuntimeException('PayPal is not configured (missing PAYPAL_CLIENT_ID/PAYPAL_CLIENT_SECRET).');
        }

        $baseUrl = $this->getBaseUrl();
        $token = $this->getAccessToken($baseUrl, $clientId, $clientSecret);

        $currency = strtoupper((string) ($order->currency ?? 'USD'));
        $amount = $this->formatAmount((string) $order->grand_total);

        $returnUrl = (string) config('services.paypal.return_url');
        $cancelUrl = (string) config('services.paypal.cancel_url');

        if ($returnUrl === '') {
            $returnUrl = url('/paypal/return');
        }
        if ($cancelUrl === '') {
            $cancelUrl = url('/paypal/cancel');
        }

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => (string) $order->id,
                    'custom_id' => (string) $order->id,
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $amount,
                    ],
                ],
            ],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'user_action' => 'PAY_NOW',
            ],
        ];

        $created = $this->requestJson(
            method: 'POST',
            url: rtrim($baseUrl, '/').'/v2/checkout/orders',
            headers: [
                'Authorization: Bearer '.$token,
                'Content-Type: application/json',
            ],
            body: json_encode($payload, JSON_UNESCAPED_SLASHES)
        );

        $approvalUrl = $this->findLink($created, 'approve');

        return [
            'provider_reference' => $created['id'] ?? null,
            'checkout_url' => $approvalUrl,
            'status' => 'requires_action',
            'metadata' => [
                'mode' => (string) config('services.paypal.mode', 'sandbox'),
                'paypal_status' => $created['status'] ?? null,
            ],
        ];
    }

    protected function getBaseUrl(): string
    {
        $explicit = (string) config('services.paypal.base_url');
        if ($explicit !== '') {
            return $explicit;
        }

        $mode = strtolower((string) config('services.paypal.mode', 'sandbox'));

        return $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    protected function getAccessToken(string $baseUrl, string $clientId, string $clientSecret): string
    {
        $ch = curl_init(rtrim($baseUrl, '/').'/v1/oauth2/token');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: en_US',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_USERPWD => $clientId.':'.$clientSecret,
        ]);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $errno) {
            throw new \RuntimeException('PayPal OAuth request failed: '.$error);
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('PayPal OAuth response is not valid JSON.');
        }

        if ($status >= 400) {
            $msg = $decoded['error_description'] ?? $decoded['error'] ?? 'PayPal OAuth error.';
            throw new \RuntimeException($msg);
        }

        $token = $decoded['access_token'] ?? '';
        if ($token === '') {
            throw new \RuntimeException('PayPal OAuth did not return access_token.');
        }

        return (string) $token;
    }

    protected function requestJson(string $method, string $url, array $headers, ?string $body = null): array
    {
        $ch = curl_init($url);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $options);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $errno) {
            throw new \RuntimeException('PayPal HTTP request failed: '.$error);
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('PayPal HTTP response is not valid JSON.');
        }

        if ($status >= 400) {
            $msg = $decoded['message'] ?? $decoded['name'] ?? 'PayPal error.';
            throw new \RuntimeException($msg);
        }

        return $decoded;
    }

    protected function findLink(array $response, string $rel): ?string
    {
        $links = $response['links'] ?? null;
        if (! is_array($links)) {
            return null;
        }

        foreach ($links as $link) {
            if (! is_array($link)) {
                continue;
            }

            if (($link['rel'] ?? null) === $rel) {
                return $link['href'] ?? null;
            }
        }

        return null;
    }

    protected function formatAmount(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === null) {
            return '0.00';
        }

        if (! is_numeric($value)) {
            $value = (string) ((float) $value);
        }

        return number_format((float) $value, 2, '.', '');
    }
}
