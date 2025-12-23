# Guide de test des notifications email avec Mailtrap

## Qu'est-ce que Mailtrap ?

Mailtrap est un service de test d'emails qui capture tous les emails envoyés par votre application sans les envoyer réellement aux destinataires. C'est l'outil idéal pour tester les notifications en développement.

## Étape 1: Créer un compte Mailtrap (gratuit)

1. Aller sur https://mailtrap.io
2. Cliquer sur **"Sign Up"** (ou **"Start Free"**)
3. S'inscrire avec votre email ou via Google/GitHub
4. Confirmer votre email

## Étape 2: Obtenir les credentials SMTP

1. Une fois connecté, vous verrez votre **inbox** par défaut
2. Cliquer sur votre inbox (généralement nommé "My Inbox")
3. Dans l'onglet **"SMTP Settings"**, vous verrez:
   - **Host**: `sandbox.smtp.mailtrap.io`
   - **Port**: `2525` (ou 587, 465)
   - **Username**: `votre_username` (ex: 1a2b3c4d5e6f7g)
   - **Password**: `votre_password` (ex: 9h8i7j6k5l4m3n)

## Étape 3: Configurer Laravel

Ouvrez votre fichier `.env` et ajoutez/modifiez ces lignes:

```env
# Configuration Mailtrap
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre_username_mailtrap
MAIL_PASSWORD=votre_password_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votresite.com
MAIL_FROM_NAME="${APP_NAME}"
```

**⚠️ Important:** Remplacez `votre_username_mailtrap` et `votre_password_mailtrap` par vos vraies credentials Mailtrap.

## Étape 4: Tester la configuration

### Test rapide avec Tinker

```bash
php artisan tinker
```

Puis dans Tinker:

```php
Mail::raw('Test email from Laravel', function($msg) {
    $msg->to('test@example.com')->subject('Test Email');
});
```

Si tout fonctionne, vous verrez l'email dans votre inbox Mailtrap !

### Test avec une vraie commande

1. **Créer un utilisateur admin** (si pas déjà fait):

```bash
php artisan tinker
```

```php
$admin = User::create([
    'name' => 'Admin Test',
    'email' => 'admin@test.com',
    'password' => bcrypt('password123'),
    'role' => 'admin'
]);
```

2. **Créer une commande de test**:

```php
$order = Order::create([
    'user_id' => 1, // ID de votre utilisateur
    'status' => 'pending',
    'currency' => 'USD',
    'grand_total' => 99.99,
    'subtotal' => 99.99,
    'discount_total' => 0,
    'shipping_total' => 0,
    'tax_total' => 0,
]);
```

3. **Tester le changement de statut via API**:

```bash
# D'abord, obtenir un token
curl -X POST http://127.0.0.1:8001/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password123"}'
```

Copier le `token` de la réponse, puis:

```bash
# Changer le statut de la commande
curl -X PATCH http://127.0.0.1:8001/api/v1/orders/1/status \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI" \
  -d '{"status":"processing","reason":"Test notification email"}'
```

4. **Vérifier l'email dans Mailtrap**:
   - Aller sur https://mailtrap.io
   - Ouvrir votre inbox
   - Vous devriez voir l'email de notification !

## Étape 5: Configurer les queues (important !)

Les notifications sont envoyées via le système de queues Laravel. Pour les traiter:

### Option 1: Mode synchrone (développement simple)

Dans `.env`:
```env
QUEUE_CONNECTION=sync
```

Les emails seront envoyés immédiatement (bloquant).

### Option 2: Mode asynchrone (recommandé)

Dans `.env`:
```env
QUEUE_CONNECTION=database
```

Puis exécuter le worker de queues:
```bash
php artisan queue:work
```

**💡 Astuce:** Gardez cette commande en cours d'exécution dans un terminal séparé pendant le développement.

## Étape 6: Tester tous les scénarios

### Test 1: Commande en traitement
```bash
curl -X PATCH http://127.0.0.1:8001/api/v1/orders/1/status \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"status":"processing"}'
```

### Test 2: Commande expédiée
```bash
curl -X PATCH http://127.0.0.1:8001/api/v1/orders/1/status \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"status":"shipped"}'
```

### Test 3: Commande livrée
```bash
curl -X PATCH http://127.0.0.1:8001/api/v1/orders/1/status \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"status":"delivered"}'
```

### Test 4: Annulation
```bash
curl -X POST http://127.0.0.1:8001/api/v1/orders/1/cancel \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"reason":"Test cancellation"}'
```

## Étape 7: Vérifier les logs

Si un email n'arrive pas dans Mailtrap, vérifier les logs:

```bash
# Logs Laravel généraux
tail -f storage/logs/laravel.log

# Logs catalogue (pour les commandes)
tail -f storage/logs/catalogue.log
```

Rechercher les messages:
- `order.status_changed` - Confirmation du changement de statut
- `order.notification_sent` - Email envoyé avec succès
- `order.notification_failed` - Erreur d'envoi d'email

## Dépannage

### ❌ Erreur: "Connection refused"

**Solution:** Vérifier que le port est correct (2525 pour Mailtrap).

```env
MAIL_PORT=2525
```

### ❌ Erreur: "Authentication failed"

**Solution:** Vérifier username/password dans Mailtrap et `.env`.

### ❌ Les emails n'arrivent pas

**Solutions:**
1. Vérifier que `MAIL_MAILER=smtp` (pas `log`)
2. Vérifier que le queue worker tourne: `php artisan queue:work`
3. Vérifier les logs: `tail -f storage/logs/catalogue.log`
4. Tester avec Tinker (voir Étape 4)

### ❌ Erreur: "Class 'App\Events\OrderStatusChanged' not found"

**Solution:** Vider le cache:
```bash
php artisan config:clear
php artisan cache:clear
composer dump-autoload
```

## Script PowerShell de test complet

Créez un fichier `test_email_notifications.ps1`:

```powershell
$ErrorActionPreference = 'Stop'
$base = 'http://127.0.0.1:8001/api/v1'

# 1. Login
Write-Host "`n=== Login ===" -ForegroundColor Cyan
$loginBody = @{
    email    = 'admin@test.com'
    password = 'password123'
} | ConvertTo-Json

$login = Invoke-RestMethod -Method Post -Uri "$base/auth/login" -ContentType 'application/json' -Body $loginBody
$token = $login.token
Write-Host "Token obtained: $($token.Substring(0,20))..." -ForegroundColor Green

# 2. Créer une commande
Write-Host "`n=== Create Order ===" -ForegroundColor Cyan
$orderBody = @{
    cart_id = 1
    shipping_address_id = 1
} | ConvertTo-Json

$order = Invoke-RestMethod -Method Post -Uri "$base/orders" -Headers @{ 
    Accept = 'application/json'
    Authorization = "Bearer $token" 
} -ContentType 'application/json' -Body $orderBody

$orderId = $order.data.id
Write-Host "Order created: #$orderId" -ForegroundColor Green

# 3. Tester les transitions de statut
$statuses = @('processing', 'shipped', 'delivered')

foreach ($status in $statuses) {
    Write-Host "`n=== Update to $status ===" -ForegroundColor Cyan
    
    $statusBody = @{
        status = $status
        reason = "Test notification for $status"
    } | ConvertTo-Json
    
    $result = Invoke-RestMethod -Method Patch -Uri "$base/orders/$orderId/status" -Headers @{ 
        Accept = 'application/json'
        Authorization = "Bearer $token"
    } -ContentType 'application/json' -Body $statusBody
    
    Write-Host "Status updated to: $($result.data.status)" -ForegroundColor Green
    Write-Host "Check Mailtrap inbox for email!" -ForegroundColor Yellow
    
    Start-Sleep -Seconds 2
}

Write-Host "`n=== Tests completed! ===" -ForegroundColor Green
Write-Host "Check your Mailtrap inbox at https://mailtrap.io" -ForegroundColor Cyan
```

Exécuter:
```powershell
.\test_email_notifications.ps1
```

## Fonctionnalités Mailtrap utiles

### 1. Prévisualisation HTML
- Voir le rendu HTML de l'email
- Tester sur différents clients email

### 2. Vérification spam
- Mailtrap analyse le score spam de vos emails
- Suggestions pour améliorer la délivrabilité

### 3. Validation HTML/CSS
- Vérifie que votre HTML est valide
- Détecte les problèmes de compatibilité

### 4. Copier le HTML
- Copier le code source HTML pour debug
- Tester dans d'autres outils

## Passer en production

Quand vous êtes prêt pour la production, changez simplement les credentials dans `.env`:

```env
# Production - Gmail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre_email@gmail.com
MAIL_PASSWORD=votre_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votredomaine.com
MAIL_FROM_NAME="${APP_NAME}"
```

Ou utilisez un service professionnel comme SendGrid, Mailgun, etc.

## Résumé des commandes essentielles

```bash
# 1. Configurer .env avec Mailtrap
# 2. Tester la connexion
php artisan tinker
Mail::raw('Test', fn($m) => $m->to('test@test.com')->subject('Test'));

# 3. Lancer le queue worker
php artisan queue:work

# 4. Exécuter les tests
php artisan test --filter=OrderStatus

# 5. Vérifier les logs
tail -f storage/logs/catalogue.log
```

## Support

- **Documentation Mailtrap:** https://mailtrap.io/docs
- **Documentation Laravel Mail:** https://laravel.com/docs/mail
- **Logs du projet:** `storage/logs/catalogue.log`

Bon test ! 🚀
