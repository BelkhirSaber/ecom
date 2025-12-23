# Guide du système de livraison

## Vue d'ensemble

Le système de livraison permet de calculer automatiquement les frais de port selon:
- La zone géographique (pays, région, code postal)
- Le montant du panier
- Le poids des articles (optionnel)
- La méthode de livraison choisie

## Configuration

### Fichier de configuration: `config/shipping.php`

```php
'methods' => [
    'standard' => [
        'label' => 'Livraison Standard',
        'description' => 'Livraison en 3-5 jours ouvrés',
        'calculation_type' => 'flat', // flat, weight_based, price_based, free
        'enabled' => true,
        'zones' => [
            'france_metro' => [
                'label' => 'France Métropolitaine',
                'countries' => ['FR'],
                'excluded_postal_codes' => ['20*'], // Exclure la Corse
                'price' => 5.90,
                'free_above' => 50.00, // Gratuit au-dessus de 50€
            ],
        ],
    ],
]
```

### Variables d'environnement

```env
SHIPPING_ENABLED=true
SHIPPING_CURRENCY=EUR
SHIPPING_HANDLING_FEE=0.00
```

## Types de calcul

### 1. Flat (Tarif fixe)
```php
'calculation_type' => 'flat',
'price' => 5.90,
```

### 2. Weight-based (Basé sur le poids)
```php
'calculation_type' => 'weight_based',
'weight_tiers' => [
    ['max_weight' => 1.0, 'price' => 5.90],   // 0-1kg
    ['max_weight' => 5.0, 'price' => 9.90],   // 1-5kg
    ['max_weight' => 10.0, 'price' => 15.90], // 5-10kg
    ['max_weight' => null, 'price' => 25.90], // 10kg+
],
```

### 3. Price-based (Basé sur le montant)
```php
'calculation_type' => 'price_based',
'price_tiers' => [
    ['max_price' => 50.0, 'price' => 5.90],
    ['max_price' => 100.0, 'price' => 3.90],
    ['max_price' => null, 'price' => 0.00], // Gratuit au-dessus de 100€
],
```

### 4. Free (Gratuit)
```php
'calculation_type' => 'free',
'price' => 0.00,
```

## Zones géographiques

### Définition d'une zone

```php
'france_idf' => [
    'label' => 'Île-de-France',
    'countries' => ['FR'],                    // Pays requis
    'states' => ['IDF'],                      // États/régions (optionnel)
    'postal_codes' => ['75*', '92*', '93*'],  // Codes postaux avec wildcards
    'excluded_postal_codes' => ['75116'],     // Codes postaux exclus
    'price' => 9.90,
    'free_above' => 100.00,                   // Seuil de gratuité
],
```

### Matching des codes postaux

- **Exact**: `75001` → Match uniquement 75001
- **Wildcard**: `75*` → Match 75000-75999
- **Multiple wildcards**: `920*` → Match 92000-92099

### Priorité des zones

Les zones **avec `postal_codes` spécifiques** sont prioritaires sur les zones génériques.

**Exemple:**
```php
'france_metro' => [
    'countries' => ['FR'],
    'excluded_postal_codes' => ['20*'],
    'price' => 5.90,
],
'france_corse' => [
    'countries' => ['FR'],
    'postal_codes' => ['20*'], // Plus spécifique, prioritaire
    'price' => 12.90,
],
```

Pour le code postal `20000`, la zone `france_corse` sera sélectionnée.

## API Endpoints

### 1. Lister les méthodes disponibles

```http
GET /api/v1/shipping/methods
```

**Réponse:**
```json
{
  "data": [
    {
      "key": "standard",
      "label": "Livraison Standard",
      "description": "Livraison en 3-5 jours ouvrés",
      "calculation_type": "flat"
    },
    {
      "key": "express",
      "label": "Livraison Express",
      "description": "Livraison en 24-48h",
      "calculation_type": "flat"
    }
  ]
}
```

### 2. Calculer les options de livraison

```http
POST /api/v1/shipping/calculate
Content-Type: application/json

{
  "country_code": "FR",
  "postal_code": "75001",
  "state": "",
  "cart_total": 45.00,
  "cart_weight": 2.5
}
```

**Réponse:**
```json
{
  "data": [
    {
      "method_key": "standard",
      "zone_key": "france_metro",
      "label": "Livraison Standard",
      "description": "Livraison en 3-5 jours ouvrés",
      "zone_label": "France Métropolitaine",
      "price": 5.90,
      "currency": "EUR",
      "calculation_type": "flat",
      "is_free": false
    },
    {
      "method_key": "express",
      "zone_key": "france_idf",
      "label": "Livraison Express",
      "description": "Livraison en 24-48h",
      "zone_label": "Île-de-France",
      "price": 9.90,
      "currency": "EUR",
      "calculation_type": "flat",
      "is_free": false
    }
  ],
  "meta": {
    "count": 2,
    "address": {
      "country_code": "FR",
      "postal_code": "75001",
      "state": ""
    }
  }
}
```

### 3. Calculer le coût d'une méthode spécifique

```http
POST /api/v1/shipping/calculate-method
Content-Type: application/json

{
  "method_key": "standard",
  "zone_key": "france_metro",
  "cart_total": 45.00,
  "cart_weight": 2.5
}
```

**Réponse:**
```json
{
  "data": {
    "method_key": "standard",
    "zone_key": "france_metro",
    "cost": 5.90,
    "currency": "EUR"
  }
}
```

## Intégration dans le tunnel de commande

### Étape 1: Récupérer les options au panier

```javascript
const response = await fetch('/api/v1/shipping/calculate', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    country_code: shippingAddress.country_code,
    postal_code: shippingAddress.postal_code,
    cart_total: cart.grand_total,
    cart_weight: cart.total_weight
  })
});

const { data: shippingOptions } = await response.json();
```

### Étape 2: Afficher les options à l'utilisateur

```vue
<div v-for="option in shippingOptions" :key="option.method_key">
  <label>
    <input type="radio" :value="option" v-model="selectedShipping">
    {{ option.label }} - {{ option.zone_label }}
    <span v-if="option.is_free">GRATUIT</span>
    <span v-else>{{ option.price }}€</span>
  </label>
  <p>{{ option.description }}</p>
</div>
```

### Étape 3: Stocker la sélection dans la commande

```php
$order->update([
    'shipping_method' => $selectedOption['method_key'],
    'shipping_zone' => $selectedOption['zone_key'],
    'shipping_total' => $selectedOption['price'],
]);
```

## Exemples de configuration

### Configuration France uniquement

```php
'methods' => [
    'colissimo' => [
        'label' => 'Colissimo',
        'calculation_type' => 'flat',
        'zones' => [
            'france' => [
                'label' => 'France',
                'countries' => ['FR'],
                'price' => 5.90,
                'free_above' => 50.00,
            ],
        ],
    ],
    'chronopost' => [
        'label' => 'Chronopost Express',
        'calculation_type' => 'flat',
        'zones' => [
            'france' => [
                'label' => 'France',
                'countries' => ['FR'],
                'price' => 12.90,
                'free_above' => null,
            ],
        ],
    ],
],
```

### Configuration multi-pays

```php
'methods' => [
    'standard' => [
        'label' => 'Livraison Standard',
        'calculation_type' => 'flat',
        'zones' => [
            'france' => [
                'label' => 'France',
                'countries' => ['FR'],
                'price' => 5.90,
                'free_above' => 50.00,
            ],
            'benelux' => [
                'label' => 'Benelux',
                'countries' => ['BE', 'NL', 'LU'],
                'price' => 12.90,
                'free_above' => 100.00,
            ],
            'europe' => [
                'label' => 'Europe',
                'countries' => ['DE', 'IT', 'ES', 'PT'],
                'price' => 15.90,
                'free_above' => 150.00,
            ],
        ],
    ],
],
```

### Configuration par poids

```php
'methods' => [
    'weight_based' => [
        'label' => 'Livraison par poids',
        'calculation_type' => 'weight_based',
        'zones' => [
            'france' => [
                'label' => 'France',
                'countries' => ['FR'],
                'weight_tiers' => [
                    ['max_weight' => 0.5, 'price' => 3.90],
                    ['max_weight' => 2.0, 'price' => 5.90],
                    ['max_weight' => 5.0, 'price' => 9.90],
                    ['max_weight' => null, 'price' => 15.90],
                ],
            ],
        ],
    ],
],
```

## Tests

### Tests unitaires
```bash
php artisan test --filter=ShippingServiceTest
```

### Tests feature
```bash
php artisan test --filter=ShippingCalculationTest
```

### Tests API (PowerShell)
```powershell
.\invoke_api_tests.ps1
```

## Dépannage

### Aucune option de livraison retournée

**Causes possibles:**
1. `SHIPPING_ENABLED=false` dans `.env`
2. Aucune zone ne correspond à l'adresse
3. Toutes les méthodes sont désactivées

**Solution:**
- Vérifier la configuration dans `config/shipping.php`
- Tester avec une adresse connue (ex: FR, 75001)
- Consulter les logs

### Mauvaise zone sélectionnée

**Cause:** Ordre des zones dans la configuration

**Solution:** Les zones avec `postal_codes` sont prioritaires. Placer les zones spécifiques avant les zones génériques.

### Prix incorrect

**Vérifier:**
1. Le type de calcul (`calculation_type`)
2. Le seuil de gratuité (`free_above`)
3. Les tiers de poids/prix si applicable

## Sécurité

✅ **Validation backend** - Tous les calculs sont effectués côté serveur
✅ **Pas de confiance frontend** - Le client ne peut pas forcer un tarif
✅ **Configuration centralisée** - Tous les tarifs dans `config/shipping.php`
✅ **Logs disponibles** - Traçabilité des calculs

## Extension

### Ajouter une nouvelle méthode

1. Éditer `config/shipping.php`
2. Ajouter la méthode dans `methods`
3. Définir les zones et tarifs
4. Tester avec l'API

**Aucune modification de code nécessaire !**

### Ajouter un provider externe (UPS, FedEx, etc.)

Créer un nouveau service qui implémente la même interface:

```php
class ExternalShippingProvider
{
    public function calculateRates(array $address, float $weight): array
    {
        // Appel API externe
        return $rates;
    }
}
```

Intégrer dans `ShippingService::calculateShippingOptions()`.

## Support

- **Documentation:** `docs/SHIPPING_GUIDE.md`
- **Configuration:** `config/shipping.php`
- **Tests:** `tests/Unit/ShippingServiceTest.php`
- **API:** `/api/v1/shipping/*`
