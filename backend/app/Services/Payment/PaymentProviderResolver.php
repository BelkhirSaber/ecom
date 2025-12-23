<?php

namespace App\Services\Payment;

use App\Services\Payment\Providers\CodPaymentProvider;
use App\Services\Payment\Providers\FakePaymentProvider;
use App\Services\Payment\Providers\PayPalPaymentProvider;
use App\Services\Payment\Providers\PaymentProviderInterface;
use App\Services\Payment\Providers\StripePaymentProvider;
use Illuminate\Contracts\Foundation\Application;

class PaymentProviderResolver
{
    public function __construct(private Application $app)
    {
    }

    /**
     * Résout et instancie le provider de paiement approprié.
     * Normalise le nom du provider et retourne l'instance correspondante.
     * 
     * @param string|null $provider Nom du provider (stripe, paypal, cod, fake) ou null pour utiliser la config
     * @return PaymentProviderInterface Instance du provider de paiement
     */
    public function resolve(?string $provider = null): PaymentProviderInterface
    {
        $name = $this->normalize($provider);

        return match ($name) {
            'stripe' => $this->app->make(StripePaymentProvider::class),
            'paypal' => $this->app->make(PayPalPaymentProvider::class),
            'cod' => $this->app->make(CodPaymentProvider::class),
            default => $this->app->make(FakePaymentProvider::class),
        };
    }

    /**
     * Normalise le nom d'un provider de paiement.
     * Vérifie que le provider est autorisé, sinon retourne le premier provider autorisé.
     * 
     * @param string|null $provider Nom du provider à normaliser
     * @return string Nom du provider normalisé (lowercase, validé)
     */
    public function normalize(?string $provider): string
    {
        $name = $provider ?? (string) config('services.payments.provider', 'fake');
        $name = strtolower(trim($name));

        $allowed = $this->allowed();
        if (! in_array($name, $allowed, true)) {
            return $allowed[0] ?? 'fake';
        }

        return $name;
    }

    /**
     * Retourne la liste des providers de paiement autorisés.
     * Lit la configuration depuis services.payments.allowed.
     * 
     * @return array Liste des providers autorisés (fake, stripe, paypal, cod)
     */
    public function allowed(): array
    {
        $configured = config('services.payments.allowed', []);
        if (is_string($configured)) {
            $configured = array_filter(array_map('trim', explode(',', $configured)));
        }
        $configured = array_map(
            fn ($value) => strtolower(trim((string) $value)),
            (array) $configured
        );

        if ($configured === []) {
            $configured = ['fake', 'stripe', 'paypal', 'cod'];
        }

        return array_values(array_unique($configured));
    }
}
