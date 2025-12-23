<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\WebhookEvent;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(private DatabaseManager $db)
    {
    }

    public function __invoke(Request $request)
    {
        $secret = (string) config('services.stripe.webhook_secret', '');
        $payload = $request->getContent();
        $sigHeader = (string) $request->headers->get('Stripe-Signature', '');

        $event = $this->verifyAndDecodeEvent($payload, $sigHeader, $secret);

        $eventId = (string) ($event['id'] ?? '');
        $eventType = (string) ($event['type'] ?? '');

        if ($eventId === '' || $eventType === '') {
            Log::channel('catalogue')->warning('stripe.webhook.invalid_event', [
                'event_id' => $eventId,
                'event_type' => $eventType,
            ]);

            return response()->json(['received' => true])->setStatusCode(Response::HTTP_OK);
        }

        $processed = $this->db->transaction(function () use ($eventId, $eventType, $event) {
            $webhookEvent = WebhookEvent::query()
                ->where('provider', 'stripe')
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->first();

            if (! $webhookEvent) {
                try {
                    $webhookEvent = WebhookEvent::create([
                        'provider' => 'stripe',
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'payload' => $event,
                    ]);
                } catch (QueryException) {
                    $webhookEvent = WebhookEvent::query()
                        ->where('provider', 'stripe')
                        ->where('event_id', $eventId)
                        ->lockForUpdate()
                        ->first();
                }
            }

            if ($webhookEvent && $webhookEvent->processed_at) {
                return true;
            }

            $this->applyStripeEvent($eventType, $event);

            if ($webhookEvent) {
                $webhookEvent->forceFill([
                    'processed_at' => now(),
                ])->save();
            }

            return true;
        });

        return response()->json(['received' => (bool) $processed])->setStatusCode(Response::HTTP_OK);
    }

    private function verifyAndDecodeEvent(string $payload, string $sigHeader, string $secret): array
    {
        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid payload.');
        }

        if ($secret === '') {
            return $decoded;
        }

        if ($sigHeader === '') {
            abort(Response::HTTP_BAD_REQUEST, 'Missing signature header.');
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $sigHeader) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            [$k, $v] = array_pad(explode('=', $part, 2), 2, null);
            if ($k === 't') {
                $timestamp = $v;
            }
            if ($k === 'v1') {
                $signatures[] = $v;
            }
        }

        if (! $timestamp || count($signatures) === 0) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid signature header.');
        }

        $signedPayload = $timestamp . '.' . $payload;
        $computed = hash_hmac('sha256', $signedPayload, $secret);

        $valid = false;
        foreach ($signatures as $sig) {
            if (is_string($sig) && hash_equals($computed, $sig)) {
                $valid = true;
                break;
            }
        }

        if (! $valid) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid signature.');
        }

        return $decoded;
    }

    private function applyStripeEvent(string $eventType, array $event): void
    {
        $dataObject = $event['data']['object'] ?? null;
        if (! is_array($dataObject)) {
            return;
        }

        if ($eventType === 'payment_intent.succeeded') {
            $this->handlePaymentIntentSucceeded($dataObject);
        }

        if ($eventType === 'payment_intent.payment_failed') {
            $this->handlePaymentIntentFailed($dataObject);
        }
    }

    private function handlePaymentIntentSucceeded(array $intent): void
    {
        $intentId = (string) ($intent['id'] ?? '');
        if ($intentId === '') {
            return;
        }

        $payment = Payment::query()
            ->where('provider', 'stripe')
            ->where('provider_reference', $intentId)
            ->first();

        if (! $payment) {
            return;
        }

        $payment->forceFill([
            'status' => 'succeeded',
            'error_code' => null,
            'error_message' => null,
        ])->save();

        if ($payment->order && $payment->order->status === 'pending') {
            $payment->order->forceFill([
                'status' => 'paid',
            ])->save();
        }

        Log::channel('catalogue')->info('stripe.webhook.payment_succeeded', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'intent_id' => $intentId,
        ]);
    }

    private function handlePaymentIntentFailed(array $intent): void
    {
        $intentId = (string) ($intent['id'] ?? '');
        if ($intentId === '') {
            return;
        }

        $payment = Payment::query()
            ->where('provider', 'stripe')
            ->where('provider_reference', $intentId)
            ->first();

        if (! $payment) {
            return;
        }

        $error = $intent['last_payment_error'] ?? null;

        $payment->forceFill([
            'status' => 'failed',
            'error_code' => is_array($error) ? ($error['code'] ?? null) : null,
            'error_message' => is_array($error) ? ($error['message'] ?? null) : null,
        ])->save();

        Log::channel('catalogue')->info('stripe.webhook.payment_failed', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'intent_id' => $intentId,
        ]);
    }
}
