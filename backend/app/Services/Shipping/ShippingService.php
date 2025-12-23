<?php

namespace App\Services\Shipping;

use App\Models\Order;

class ShippingService
{
    /**
     * Calcule les options de livraison disponibles pour une adresse donnée.
     * Retourne toutes les méthodes de livraison éligibles avec leurs tarifs.
     * 
     * @param array $address Adresse de livraison ['country_code', 'postal_code', 'state']
     * @param float $cartTotal Montant total du panier
     * @param float $cartWeight Poids total du panier (optionnel)
     * @return array Liste des méthodes disponibles avec tarifs
     */
    public function calculateShippingOptions(array $address, float $cartTotal, float $cartWeight = 0.0): array
    {
        if (!config('shipping.enabled', true)) {
            return [];
        }

        $country = strtoupper($address['country_code'] ?? '');
        $postalCode = strtoupper($address['postal_code'] ?? '');
        $state = strtoupper($address['state'] ?? '');

        if ($country === '') {
            return [];
        }

        $methods = config('shipping.methods', []);
        $availableOptions = [];

        foreach ($methods as $methodKey => $method) {
            if (!($method['enabled'] ?? true)) {
                continue;
            }

            // Trouver la zone la plus spécifique (avec postal_codes définis en premier)
            $matchedZone = null;
            $matchedZoneKey = null;

            foreach ($method['zones'] ?? [] as $zoneKey => $zone) {
                if ($this->addressMatchesZone($country, $postalCode, $state, $zone)) {
                    // Prioriser les zones avec postal_codes spécifiques
                    if (isset($zone['postal_codes']) && !empty($zone['postal_codes'])) {
                        $matchedZone = $zone;
                        $matchedZoneKey = $zoneKey;
                        break; // Zone spécifique trouvée, arrêter
                    } elseif ($matchedZone === null) {
                        // Garder comme fallback si aucune zone spécifique trouvée
                        $matchedZone = $zone;
                        $matchedZoneKey = $zoneKey;
                    }
                }
            }

            if ($matchedZone !== null) {
                $price = $this->calculatePrice($method, $matchedZone, $cartTotal, $cartWeight);
                
                $availableOptions[] = [
                    'method_key' => $methodKey,
                    'zone_key' => $matchedZoneKey,
                    'label' => $method['label'],
                    'description' => $method['description'] ?? null,
                    'zone_label' => $matchedZone['label'] ?? $matchedZoneKey,
                    'price' => $price,
                    'currency' => config('shipping.default_currency', 'EUR'),
                    'calculation_type' => $method['calculation_type'] ?? 'flat',
                    'is_free' => $price === 0.0,
                ];
            }
        }

        return $availableOptions;
    }

    /**
     * Calcule le coût de livraison pour une méthode spécifique.
     * 
     * @param string $methodKey Clé de la méthode de livraison
     * @param string $zoneKey Clé de la zone
     * @param float $cartTotal Montant total du panier
     * @param float $cartWeight Poids total du panier
     * @return float|null Coût de livraison ou null si non disponible
     */
    public function calculateShippingCost(string $methodKey, string $zoneKey, float $cartTotal, float $cartWeight = 0.0): ?float
    {
        $method = config("shipping.methods.{$methodKey}");
        if (!$method || !($method['enabled'] ?? true)) {
            return null;
        }

        $zone = $method['zones'][$zoneKey] ?? null;
        if (!$zone) {
            return null;
        }

        return $this->calculatePrice($method, $zone, $cartTotal, $cartWeight);
    }

    /**
     * Vérifie si une adresse correspond aux critères d'une zone.
     * 
     * @param string $country Code pays
     * @param string $postalCode Code postal
     * @param string $state État/région
     * @param array $zone Configuration de la zone
     * @return bool True si l'adresse correspond
     */
    protected function addressMatchesZone(string $country, string $postalCode, string $state, array $zone): bool
    {
        // Vérifier le pays
        $countries = array_map('strtoupper', $zone['countries'] ?? []);
        if ($countries !== [] && !in_array($country, $countries, true)) {
            return false;
        }

        // Vérifier les états (optionnel)
        $states = array_map('strtoupper', $zone['states'] ?? []);
        if ($states !== [] && ($state === '' || !in_array($state, $states, true))) {
            return false;
        }

        // Vérifier les codes postaux exclus
        $excludedPostalCodes = $zone['excluded_postal_codes'] ?? [];
        if ($excludedPostalCodes !== [] && $postalCode !== '') {
            foreach ($excludedPostalCodes as $pattern) {
                if ($this->postalCodeMatches($postalCode, $pattern)) {
                    return false;
                }
            }
        }

        // Vérifier les codes postaux inclus
        $postalCodes = $zone['postal_codes'] ?? [];
        if ($postalCodes !== []) {
            if ($postalCode === '') {
                return false;
            }
            
            $matched = false;
            foreach ($postalCodes as $pattern) {
                if ($this->postalCodeMatches($postalCode, $pattern)) {
                    $matched = true;
                    break;
                }
            }
            
            return $matched;
        }

        return true;
    }

    /**
     * Vérifie si un code postal correspond à un pattern.
     * Support des wildcards (*).
     * 
     * @param string $postalCode Code postal à vérifier
     * @param string $pattern Pattern de matching
     * @return bool True si correspond
     */
    protected function postalCodeMatches(string $postalCode, string $pattern): bool
    {
        $pattern = strtoupper(trim($pattern));
        if ($pattern === '') {
            return false;
        }

        if (str_contains($pattern, '*')) {
            $escaped = preg_quote($pattern, '/');
            $regex = '/^' . str_replace('\\*', '.*', $escaped) . '$/';
            return (bool) preg_match($regex, $postalCode);
        }

        return $postalCode === $pattern;
    }

    /**
     * Calcule le prix de livraison selon le type de calcul.
     * 
     * @param array $method Configuration de la méthode
     * @param array $zone Configuration de la zone
     * @param float $cartTotal Montant du panier
     * @param float $cartWeight Poids du panier
     * @return float Prix de livraison
     */
    protected function calculatePrice(array $method, array $zone, float $cartTotal, float $cartWeight): float
    {
        $calculationType = $method['calculation_type'] ?? 'flat';

        // Livraison gratuite
        if ($calculationType === 'free') {
            return 0.0;
        }

        // Vérifier si gratuit au-dessus d'un montant
        $freeAbove = $zone['free_above'] ?? null;
        if ($freeAbove !== null && $cartTotal >= $freeAbove) {
            return 0.0;
        }

        // Calcul selon le type
        switch ($calculationType) {
            case 'flat':
                return (float) ($zone['price'] ?? 0.0);

            case 'weight_based':
                return $this->calculateWeightBasedPrice($zone, $cartWeight);

            case 'price_based':
                return $this->calculatePriceBasedPrice($zone, $cartTotal);

            default:
                return (float) ($zone['price'] ?? 0.0);
        }
    }

    /**
     * Calcule le prix basé sur le poids.
     * 
     * @param array $zone Configuration de la zone
     * @param float $weight Poids total
     * @return float Prix de livraison
     */
    protected function calculateWeightBasedPrice(array $zone, float $weight): float
    {
        $tiers = $zone['weight_tiers'] ?? [];
        
        foreach ($tiers as $tier) {
            $maxWeight = $tier['max_weight'] ?? null;
            if ($maxWeight === null || $weight <= $maxWeight) {
                return (float) ($tier['price'] ?? 0.0);
            }
        }

        // Si aucun tier ne correspond, retourner le dernier
        if (!empty($tiers)) {
            $lastTier = end($tiers);
            return (float) ($lastTier['price'] ?? 0.0);
        }

        return 0.0;
    }

    /**
     * Calcule le prix basé sur le montant du panier.
     * 
     * @param array $zone Configuration de la zone
     * @param float $cartTotal Montant total
     * @return float Prix de livraison
     */
    protected function calculatePriceBasedPrice(array $zone, float $cartTotal): float
    {
        $tiers = $zone['price_tiers'] ?? [];
        
        foreach ($tiers as $tier) {
            $maxPrice = $tier['max_price'] ?? null;
            if ($maxPrice === null || $cartTotal <= $maxPrice) {
                return (float) ($tier['price'] ?? 0.0);
            }
        }

        if (!empty($tiers)) {
            $lastTier = end($tiers);
            return (float) ($lastTier['price'] ?? 0.0);
        }

        return 0.0;
    }

    /**
     * Retourne toutes les méthodes de livraison configurées.
     * 
     * @return array Liste des méthodes
     */
    public function getAllMethods(): array
    {
        $methods = config('shipping.methods', []);
        $result = [];

        foreach ($methods as $key => $method) {
            if ($method['enabled'] ?? true) {
                $result[] = [
                    'key' => $key,
                    'label' => $method['label'],
                    'description' => $method['description'] ?? null,
                    'calculation_type' => $method['calculation_type'] ?? 'flat',
                ];
            }
        }

        return $result;
    }
}
