<?php

namespace App\Services\Payment\Providers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\CodEligibilityService;

class CodPaymentProvider implements PaymentProviderInterface
{
    public function __construct(private CodEligibilityService $eligibility)
    {
    }

    /**
     * Crée une intention de paiement COD pour une commande.
     * Vérifie l'éligibilité de la zone et retourne les informations de paiement.
     * 
     * @param Order $order La commande pour laquelle créer le paiement
     * @param Payment $payment L'enregistrement de paiement à configurer
     * @return array Données du paiement (provider_reference, status, order_status, metadata)
     * @throws \RuntimeException Si l'adresse n'est pas éligible au COD
     */
    public function createIntent(Order $order, Payment $payment): array
    {
        $zone = $this->eligibility->resolveZoneForOrder($order);
        if (! $zone) {
            throw new \RuntimeException('COD is not available for this address.');
        }

        $paymentStatus = (string) config('cod.default_payment_status', 'pending_cod');
        if ($paymentStatus === '') {
            $paymentStatus = 'pending_cod';
        }

        $orderStatus = (string) config('cod.default_order_status', 'pending_cod');
        if ($orderStatus === '') {
            $orderStatus = 'pending_cod';
        }

        return [
            'provider_reference' => 'cod_'.$order->id,
            'status' => $paymentStatus,
            'order_status' => $orderStatus,
            'metadata' => [
                'zone_key' => $zone['key'],
                'zone_label' => $zone['label'],
            ],
        ];
    }
}
