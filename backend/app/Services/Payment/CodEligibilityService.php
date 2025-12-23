<?php

namespace App\Services\Payment;

use App\Models\Order;

class CodEligibilityService
{
    /**
     * Vérifie si le paiement COD est activé globalement.
     * 
     * @return bool True si COD est activé, false sinon
     */
    public function isEnabled(): bool
    {
        return (bool) config('cod.enabled', false);
    }

    /**
     * Détermine la zone COD éligible pour une commande donnée.
     * Vérifie l'adresse de livraison contre les zones configurées.
     * 
     * @param Order $order La commande à vérifier
     * @return array|null ['key' => string, 'label' => string] si éligible, null sinon
     */
    public function resolveZoneForOrder(Order $order): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $address = $order->shipping_address;
        if (! is_array($address)) {
            return null;
        }

        $country = strtoupper((string) ($address['country_code'] ?? ''));
        if ($country === '') {
            return null;
        }

        $postalCode = strtoupper((string) ($address['postal_code'] ?? ''));
        $state = strtoupper((string) ($address['state'] ?? ''));

        $zones = config('cod.zones', []);
        foreach ($zones as $key => $zone) {
            if ($this->addressMatchesZone($country, $state, $postalCode, $zone)) {
                return [
                    'key' => $key,
                    'label' => $zone['label'] ?? $key,
                ];
            }
        }

        return null;
    }

    /**
     * Vérifie si une adresse correspond aux critères d'une zone COD.
     * Valide le pays, l'état (optionnel) et le code postal avec support wildcards.
     * 
     * @param string $country Code pays ISO (ex: FR, BE)
     * @param string $state État/région (optionnel)
     * @param string $postalCode Code postal
     * @param array $zone Configuration de la zone à vérifier
     * @return bool True si l'adresse correspond à la zone
     */
    protected function addressMatchesZone(string $country, string $state, string $postalCode, array $zone): bool
    {
        $countries = array_map('strtoupper', $zone['countries'] ?? []);
        if ($countries !== [] && ! in_array($country, $countries, true)) {
            return false;
        }

        $states = array_map('strtoupper', $zone['states'] ?? []);
        if ($states !== [] && ($state === '' || ! in_array($state, $states, true))) {
            return false;
        }

        $postalRules = $zone['postal_codes'] ?? [];
        if ($postalRules === [] || $postalCode === '') {
            return $postalRules === [];
        }

        foreach ($postalRules as $rule) {
            if ($this->postalCodeMatchesRule($postalCode, $rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si un code postal correspond à une règle de pattern.
     * Supporte les wildcards (*) pour matching flexible (ex: 75* pour 75001-75999).
     * 
     * @param string $postalCode Le code postal à vérifier
     * @param string $rule Le pattern de règle (peut contenir *)
     * @return bool True si le code postal correspond au pattern
     */
    protected function postalCodeMatchesRule(string $postalCode, string $rule): bool
    {
        $rule = strtoupper(trim($rule));
        if ($rule === '') {
            return false;
        }

        if (str_contains($rule, '*')) {
            $escaped = preg_quote($rule, '/');
            $pattern = '/^' . str_replace('\\*', '.*', $escaped) . '$/';
            return (bool) preg_match($pattern, $postalCode);
        }

        return $postalCode === $rule;
    }
}
