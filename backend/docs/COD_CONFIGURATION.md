# COD (Cash on Delivery) Configuration Guide

## Overview

Le système COD permet d'activer le paiement à la livraison pour des zones géographiques spécifiques basées sur le pays, l'état/région et le code postal de l'adresse de livraison.

## Configuration

### Variables d'environnement

Ajoutez ces variables dans votre fichier `.env` :

```env
# Activer/désactiver COD globalement
COD_ENABLED=true

# Statut par défaut pour les paiements COD
COD_PAYMENT_STATUS=pending_cod

# Statut par défaut pour les commandes COD
COD_ORDER_STATUS=pending_cod

# Liste des providers de paiement autorisés (inclure 'cod' pour activer)
PAYMENT_PROVIDERS=fake,stripe,paypal,cod

# Provider par défaut (optionnel)
PAYMENT_PROVIDER=fake
```

### Configuration des zones

Les zones COD sont définies dans `config/cod.php`. Exemple :

```php
'zones' => [
    'france_idf' => [
        'label' => 'France - Île-de-France',
        'countries' => ['FR'],
        'postal_codes' => ['75*', '92*', '93*', '94*'],
    ],
    'france_paca' => [
        'label' => 'France - PACA',
        'countries' => ['FR'],
        'states' => ['PACA', 'Provence-Alpes-Côte d\'Azur'],
        'postal_codes' => ['13*', '06*', '83*', '84*'],
    ],
    'belgium_brussels' => [
        'label' => 'Belgique - Bruxelles',
        'countries' => ['BE'],
        'postal_codes' => ['1*'],
    ],
],
```

### Structure d'une zone

Chaque zone peut contenir :

- **`label`** (string) : Nom affiché de la zone
- **`countries`** (array) : Codes pays ISO 3166-1 alpha-2 (ex: `['FR', 'BE']`)
- **`states`** (array, optionnel) : États/régions autorisés
- **`postal_codes`** (array) : Patterns de codes postaux avec support wildcard `*`

### Règles de matching

1. **Pays** : L'adresse doit correspondre à un des pays listés
2. **État** : Si `states` est défini, l'état doit correspondre (sinon ignoré)
3. **Code postal** : Doit matcher un des patterns définis

#### Exemples de patterns de codes postaux

- `75*` : Tous les codes commençant par 75 (75001, 75008, 75116, etc.)
- `920*` : Tous les codes commençant par 920 (92000-92099)
- `13001` : Code postal exact
- `1*` : Tous les codes commençant par 1

## Utilisation API

### Créer un paiement COD

```http
POST /api/v1/orders/{order_id}/payments
Authorization: Bearer {token}
Content-Type: application/json

{
  "provider": "cod"
}
```

### Réponse succès (zone éligible)

```json
{
  "data": {
    "id": 123,
    "order_id": 456,
    "provider": "cod",
    "provider_reference": "cod_456",
    "status": "pending_cod",
    "amount": "299.98",
    "currency": "USD",
    "metadata": {
      "zone_key": "france_idf",
      "zone_label": "France - Île-de-France"
    },
    "created_at": "2025-12-22T08:30:00.000000Z"
  }
}
```

### Réponse erreur (zone non éligible)

```json
{
  "message": "Unable to create payment at this time.",
  "errors": {
    "payment": ["Unable to create payment at this time."]
  }
}
```

## Workflow

1. **Création de commande** : L'utilisateur crée une commande avec une adresse de livraison
2. **Sélection COD** : L'utilisateur choisit `provider: "cod"` lors de la création du paiement
3. **Validation de zone** : Le système vérifie si l'adresse de livraison correspond à une zone COD autorisée
4. **Création paiement** : Si éligible, le paiement est créé avec le statut `pending_cod`
5. **Mise à jour commande** : Le statut de la commande passe à `pending_cod`
6. **Traitement** : L'admin peut ensuite gérer manuellement la commande COD

## Sécurité

- ✅ **Validation backend** : L'éligibilité COD est vérifiée côté serveur uniquement
- ✅ **Pas de confiance frontend** : Le client ne peut pas forcer un paiement COD pour une zone non autorisée
- ✅ **Logs structurés** : Tous les paiements COD sont loggés dans le canal `catalogue`

## Tests

### Tests unitaires

```bash
php artisan test --filter=CodEligibilityServiceTest
```

### Tests feature

```bash
php artisan test --filter=CodPaymentTest
```

### Tests API (PowerShell)

```powershell
# Configurer COD dans .env
$env:COD_ENABLED = "true"
$env:PAYMENT_PROVIDER = "cod"

# Exécuter les tests
.\invoke_api_tests.ps1
```

## Dépannage

### COD non disponible

1. Vérifier `COD_ENABLED=true` dans `.env`
2. Vérifier que `cod` est dans `PAYMENT_PROVIDERS`
3. Vérifier que l'adresse de livraison correspond à une zone définie dans `config/cod.php`
4. Vérifier les logs dans `storage/logs/catalogue.log`

### Erreur "COD is not available for this address"

L'adresse de livraison ne correspond à aucune zone COD configurée. Vérifier :
- Le code pays (`country_code`)
- Le code postal (`postal_code`)
- L'état/région (`state`) si défini dans la zone

## Extension

Pour ajouter de nouvelles zones, éditez `config/cod.php` :

```php
'zones' => [
    // Zones existantes...
    
    'nouvelle_zone' => [
        'label' => 'Nom de la zone',
        'countries' => ['FR'],
        'postal_codes' => ['pattern*'],
    ],
],
```

Aucune modification de code n'est nécessaire, seulement la configuration.
