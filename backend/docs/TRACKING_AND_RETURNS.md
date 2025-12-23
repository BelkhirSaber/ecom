# Guide Tracking & Retours (SHIP-02 & SHIP-03)

## Vue d'ensemble

Ce guide couvre deux fonctionnalités:
- **SHIP-02**: Tracking de colis (manuel)
- **SHIP-03**: Workflow de retours/RMA (Return Merchandise Authorization)

---

## SHIP-02: Tracking de colis

### Champs ajoutés au modèle Order

```php
'tracking_number'   // Numéro de suivi
'tracking_carrier'  // Transporteur (DHL, UPS, Colissimo, etc.)
'tracking_url'      // URL de tracking
'shipped_at'        // Date d'expédition
'delivered_at'      // Date de livraison
```

### API Endpoints

#### 1. Ajouter/Mettre à jour le tracking (Admin uniquement)

```http
PATCH /api/v1/orders/{order_id}/tracking
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "tracking_number": "TRACK123456789",
  "tracking_carrier": "DHL",
  "tracking_url": "https://dhl.com/track/TRACK123456789"
}
```

**Comportement:**
- Si la commande est en statut `processing`, elle passe automatiquement à `shipped`
- Le champ `shipped_at` est rempli automatiquement

**Réponse:**
```json
{
  "data": {
    "id": 123,
    "status": "shipped",
    "tracking_number": "TRACK123456789",
    "tracking_carrier": "DHL",
    "tracking_url": "https://dhl.com/track/TRACK123456789",
    "shipped_at": "2025-12-22T10:30:00.000000Z",
    ...
  }
}
```

#### 2. Consulter le tracking (Client/Admin)

```http
GET /api/v1/orders/{order_id}/tracking
Authorization: Bearer {token}
```

**Réponse:**
```json
{
  "data": {
    "order_id": 123,
    "tracking_number": "TRACK123456789",
    "tracking_carrier": "DHL",
    "tracking_url": "https://dhl.com/track/TRACK123456789",
    "shipped_at": "2025-12-22T10:30:00+00:00",
    "delivered_at": null,
    "status": "shipped"
  }
}
```

#### 3. Marquer comme livré (Admin uniquement)

```http
POST /api/v1/orders/{order_id}/mark-delivered
Authorization: Bearer {admin_token}
```

**Comportement:**
- La commande doit être en statut `shipped`
- Passe le statut à `delivered`
- Remplit le champ `delivered_at`

---

## SHIP-03: Workflow Retours/RMA

### Statuts de retour

1. **`requested`** - Demande de retour créée par le client
2. **`approved`** - Retour approuvé par l'admin
3. **`rejected`** - Retour refusé par l'admin
4. **`received`** - Colis retourné reçu par l'admin
5. **`refunded`** - Remboursement effectué
6. **`completed`** - Processus terminé

### Workflow complet

```
Customer                    Admin
   |                          |
   | 1. Request Return        |
   |------------------------->|
   |                          |
   |                          | 2. Approve/Reject
   |<-------------------------|
   |                          |
   | 3. Add Tracking          |
   |------------------------->|
   |                          |
   |                          | 4. Mark Received
   |                          |
   |                          | 5. Mark Refunded
   |<-------------------------|
```

### Raisons de retour acceptées

- `defective` - Produit défectueux
- `wrong_item` - Mauvais article reçu
- `not_as_described` - Non conforme à la description
- `changed_mind` - Changement d'avis
- `other` - Autre raison

### API Endpoints

#### 1. Créer une demande de retour (Client)

```http
POST /api/v1/orders/{order_id}/returns
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "defective",
  "description": "The product arrived broken",
  "items": [
    {
      "order_item_id": 1,
      "quantity": 1
    }
  ]
}
```

**Conditions:**
- La commande doit être en statut `delivered` ou `returned`
- Pas de retour déjà en cours pour cette commande

**Réponse:**
```json
{
  "data": {
    "id": 1,
    "order_id": 123,
    "user_id": 1,
    "status": "requested",
    "reason": "defective",
    "description": "The product arrived broken",
    "items": [...],
    "created_at": "2025-12-22T10:30:00.000000Z"
  }
}
```

#### 2. Lister les retours

```http
GET /api/v1/returns
Authorization: Bearer {token}
```

**Comportement:**
- Client: Voit uniquement ses propres retours
- Admin: Voit tous les retours

#### 3. Voir un retour spécifique

```http
GET /api/v1/returns/{return_id}
Authorization: Bearer {token}
```

#### 4. Approuver un retour (Admin uniquement)

```http
POST /api/v1/returns/{return_id}/approve
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "refund_amount": 99.99
}
```

**Comportement:**
- Statut passe de `requested` à `approved`
- Remplit `approved_at`
- Définit le montant du remboursement

#### 5. Rejeter un retour (Admin uniquement)

```http
POST /api/v1/returns/{return_id}/reject
Authorization: Bearer {admin_token}
```

**Comportement:**
- Statut passe de `requested` à `rejected`

#### 6. Ajouter le tracking du retour (Client)

```http
PATCH /api/v1/returns/{return_id}/tracking
Authorization: Bearer {token}
Content-Type: application/json

{
  "return_tracking_number": "RETURN123456",
  "return_tracking_carrier": "UPS"
}
```

**Conditions:**
- Le retour doit être en statut `approved`

#### 7. Marquer comme reçu (Admin uniquement)

```http
POST /api/v1/returns/{return_id}/mark-received
Authorization: Bearer {admin_token}
```

**Comportement:**
- Statut passe de `approved` à `received`
- Remplit `received_at`

#### 8. Marquer comme remboursé (Admin uniquement)

```http
POST /api/v1/returns/{return_id}/mark-refunded
Authorization: Bearer {admin_token}
```

**Comportement:**
- Statut passe de `received` à `refunded`
- Remplit `refunded_at`

---

## Intégration Frontend

### Afficher le tracking (Vue 3 + PrimeVue)

```vue
<template>
  <Card v-if="tracking">
    <template #title>Suivi de commande</template>
    <template #content>
      <div class="flex flex-column gap-3">
        <div>
          <label class="font-semibold">Numéro de suivi:</label>
          <p>{{ tracking.tracking_number }}</p>
        </div>
        <div>
          <label class="font-semibold">Transporteur:</label>
          <p>{{ tracking.tracking_carrier }}</p>
        </div>
        <div v-if="tracking.tracking_url">
          <Button 
            label="Suivre mon colis" 
            icon="pi pi-external-link"
            :href="tracking.tracking_url"
            target="_blank"
          />
        </div>
        <div>
          <label class="font-semibold">Expédié le:</label>
          <p>{{ formatDate(tracking.shipped_at) }}</p>
        </div>
        <div v-if="tracking.delivered_at">
          <label class="font-semibold">Livré le:</label>
          <p>{{ formatDate(tracking.delivered_at) }}</p>
        </div>
      </div>
    </template>
  </Card>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const tracking = ref(null);

onMounted(async () => {
  const response = await fetch(`/api/v1/orders/${route.params.id}/tracking`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  tracking.value = await response.json().then(r => r.data);
});
</script>
```

### Formulaire de demande de retour

```vue
<template>
  <Dialog v-model:visible="showDialog" header="Demander un retour">
    <form @submit.prevent="submitReturn">
      <div class="flex flex-column gap-3">
        <div>
          <label for="reason">Raison du retour</label>
          <Dropdown 
            id="reason"
            v-model="form.reason" 
            :options="reasons"
            optionLabel="label"
            optionValue="value"
            placeholder="Sélectionner une raison"
          />
        </div>
        <div>
          <label for="description">Description</label>
          <Textarea 
            id="description"
            v-model="form.description" 
            rows="5"
            placeholder="Décrivez le problème..."
          />
        </div>
        <Button type="submit" label="Envoyer la demande" />
      </div>
    </form>
  </Dialog>
</template>

<script setup>
import { ref } from 'vue';

const form = ref({
  reason: '',
  description: ''
});

const reasons = [
  { label: 'Produit défectueux', value: 'defective' },
  { label: 'Mauvais article', value: 'wrong_item' },
  { label: 'Non conforme', value: 'not_as_described' },
  { label: "Changement d'avis", value: 'changed_mind' },
  { label: 'Autre', value: 'other' }
];

const submitReturn = async () => {
  await fetch(`/api/v1/orders/${orderId}/returns`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(form.value)
  });
};
</script>
```

---

## Permissions

### Tracking
- **Ajouter tracking**: Admin uniquement
- **Consulter tracking**: Propriétaire + Admin
- **Marquer livré**: Admin uniquement

### Retours
- **Créer demande**: Client (propriétaire de la commande)
- **Lister retours**: Client (ses retours) / Admin (tous)
- **Approuver/Rejeter**: Admin uniquement
- **Ajouter tracking retour**: Client (son retour) / Admin
- **Marquer reçu/remboursé**: Admin uniquement

---

## Tests

### Tests tracking
```bash
php artisan test --filter=OrderTrackingTest
# ✅ 5 tests
```

### Tests retours
```bash
php artisan test --filter=OrderReturnTest
# ✅ 11 tests
```

### Total
**16 tests, 39 assertions - 100% de succès**

---

## Base de données

### Migration tracking
```php
$table->string('tracking_number')->nullable();
$table->string('tracking_carrier')->nullable();
$table->string('tracking_url')->nullable();
$table->timestamp('shipped_at')->nullable();
$table->timestamp('delivered_at')->nullable();
```

### Table order_returns
```php
$table->foreignId('order_id')->constrained();
$table->foreignId('user_id')->constrained();
$table->string('status')->default('requested');
$table->string('reason')->nullable();
$table->text('description')->nullable();
$table->json('items')->nullable();
$table->decimal('refund_amount', 10, 2)->nullable();
$table->string('return_tracking_number')->nullable();
$table->string('return_tracking_carrier')->nullable();
$table->timestamp('approved_at')->nullable();
$table->timestamp('received_at')->nullable();
$table->timestamp('refunded_at')->nullable();
```

---

## Notifications (Extension future)

Pour envoyer des emails lors des événements de retour, créer:

1. **Event**: `ReturnStatusChanged`
2. **Listener**: `SendReturnNotification`
3. **Mailable**: `ReturnStatusChanged`

Événements à notifier:
- Retour approuvé → Email client avec instructions
- Retour reçu → Confirmation de réception
- Remboursement effectué → Confirmation de remboursement

---

## Sécurité

✅ **Validation backend** - Toutes les transitions validées
✅ **Permissions strictes** - Admin vs Client bien séparés
✅ **Pas de retours multiples** - Un seul retour actif par commande
✅ **Workflow contrôlé** - Transitions de statut validées

---

## Dépannage

### Impossible d'ajouter le tracking
- Vérifier que l'utilisateur est admin
- Vérifier que la commande existe

### Impossible de créer un retour
- La commande doit être en statut `delivered` ou `returned`
- Vérifier qu'il n'y a pas déjà un retour en cours

### Le statut ne change pas
- Vérifier les transitions autorisées
- Consulter les logs dans `storage/logs/catalogue.log`

---

## Extension

### Ajouter un délai de retour

```php
// Dans OrderReturnController::store()
$deliveredAt = $order->delivered_at;
$daysSinceDelivery = now()->diffInDays($deliveredAt);

if ($daysSinceDelivery > 30) {
    return response()->json([
        'error' => 'Return period expired (30 days)'
    ], 422);
}
```

### Intégrer un provider de remboursement

```php
// Service de remboursement
class RefundService {
    public function processRefund(OrderReturn $return) {
        // Appel API Stripe/PayPal pour remboursement
    }
}
```

---

## Support

- **Documentation**: `docs/TRACKING_AND_RETURNS.md`
- **Tests**: `tests/Feature/OrderTrackingTest.php`, `tests/Feature/OrderReturnTest.php`
- **Migrations**: `database/migrations/*_add_tracking_*`, `*_create_order_returns_*`
