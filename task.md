# Tasks — E-commerce Mono-boutique / Mono-vendeur

> Statuts: `TODO` | `IN_PROGRESS` | `DONE` | `BLOCKED`

## Conventions de suivi
- **ID**: identifiant unique
- **Module**: Backend / Front Client / Admin / Infra
- **Dépend de**: IDs
- **AC**: Acceptance Criteria (conditions de validation)

## ⚠️ IMPORTANT: Tests API obligatoires
**Pour chaque tâche terminée, il est OBLIGATOIRE d'ajouter les tests dans:**
1. **`invoke_api_tests.ps1`** - Tests PowerShell automatisés
2. **`postman_collection.json`** - Collection Postman pour tests manuels/CI
3. **`invoke_api_tests.ps1`** - exécuter le fichier de test et confirmer que toutes les API fonctionnent correctement.
**Sans ces tests, la tâche n'est PAS considérée comme complète.**

---

## 0) Cadrage (bloquant avant implémentation)

- **[TODO] (C-01) Valider MVP**
  - **Module**: Produit
  - **Dépend de**: —
  - **AC**: Liste écrite IN/OUT (ex: wishlist, comparateur, RMA, upsell/cross-sell, push) + priorités.

- **[TODO] (C-02) Choisir PSP paiement carte (MVP)**
  - **Module**: Produit/Backend
  - **Dépend de**: —
  - **AC**: Provider choisi + modes test/prod + parcours paiement défini + webhooks listés.

- **[TODO] (C-03) Définir statuts & transitions** (commande/paiement/livraison)
  - **Module**: Produit/Backend
  - **Dépend de**: —
  - **AC**: Tableau des statuts + transitions autorisées + qui a le droit de changer quoi.

- **[TODO] (C-04) Règles livraison** (zones, prix)
  - **Module**: Produit/Backend
  - **Dépend de**: —
  - **AC**: Modèle de zones (pays/région/CP) + méthode de calcul (fixe/par poids) + exemples.

---

## 1) Backend Laravel — Foundations (API, auth, ACL)

- **[DONE] ✅ (B-01) Setup projet Laravel 12 + config env** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: C-01
  - **AC**: App démarre, `.env` prêt, connexion DB OK.

- **[DONE] ✅ (B-02) Architecture API (Services/Repositories) + conventions** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: B-01
  - **AC**: Structure dossiers définie, conventions de nommage validées.

- **[DONE] ✅ (B-03) Auth API (Fortify/Breeze) + tokens** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: B-01
  - **AC**: Login/logout, routes protégées, rate-limit login.

- **[DONE] ✅ (B-04) Rôles & permissions (Spatie)** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: B-03
  - **AC**: Rôles admin/modérateur/support (si besoin) + seed + middleware/policies.

- **[DONE] ✅ (B-05) Standard réponses API + gestion erreurs** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: B-01
  - **AC**: Format JSON uniforme + pagination + erreurs validation propres.

- **[TODO] (B-06) Upload médias (local + S3 optionnel)**
  - **Module**: Backend
  - **Dépend de**: B-01
  - **AC**: Upload images produits, validation mime/size, suppression OK.

- **[DONE] ✅ (B-07) Audit logs actions admin** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: B-04
  - **AC**: Trace create/update/delete + user + date + ip.

---

## 2) Backend — Catalogue

- **[DONE] ✅ (CAT-01) Modèle catégories (hiérarchie, slug, SEO)** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: B-01, B-05
  - **AC**: CRUD admin + listing public + slugs uniques.

- **[DONE] ✅ (CAT-02) Produits (SEO, images, prix, statut)** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: CAT-01, B-06
  - **AC**: CRUD admin + endpoints publics (list/detail) + pagination.

- **[DONE] ✅ (CAT-03) Variantes (taille/couleur…) + attributs** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: CAT-02
  - **AC**: Variants liés au produit, stock/prix par variant si activé.

- **[DONE] ✅ (CAT-04) Stock (mouvements + anti-survente)** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: CAT-03, C-03
  - **AC**: Stock décrémenté selon règle, blocage si stock insuffisant.

- **[DONE] ✅ (CAT-05) Recherche + filtres (prix, catégorie, attributs)** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: CAT-02
  - **AC**: Endpoints filtres, performances acceptables, index DB.

- **[DONE] ✅ (CAT-06) Import/Export CSV/Excel** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: CAT-02
  - **AC**: Import avec rapport erreurs, export filtrable.

---

## 3) Backend — Panier & Checkout

- **[DONE] ✅ (CK-01) Panier (guest + user) + merge** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: B-03
  - **AC**: CRUD panier, merge guest→user, validation stock.

- **[DONE] ✅ (CK-02) Calcul totaux (taxe, livraison, remise)** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: C-04, PR-01, CK-01
  - **AC**: Totaux identiques front/back, tests unitaires.

- **[DONE] ✅ (CK-03) Adresses (CRUD) + validations** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: B-03
  - **AC**: CRUD + validation pays/ville/CP (selon règles).

- **[DONE] ✅ (CK-04) Création commande (snapshot produits/prix)** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: CK-01, CK-02, C-03
  - **AC**: Commande créée avec lignes, totaux, adresse, méthode livraison.

---

## 4) Backend — Paiement & Commandes

- **[DONE] ✅ (PAY-01) Intégration PSP carte (MVP)** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: C-02, CK-04
  - **AC**: Création payment intent/session + retour succès/échec.

- **[DONE] ✅ (PAY-01S) Stripe (Test mode) — Paiement carte (PaymentIntent / Checkout)** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: PAY-01
  - **AC**:
    - Créer un compte Stripe + activer **Test mode**.
    - Récupérer clés test (`sk_test_...`, `pk_test_...`).
    - Ajouter dans `backend/.env`:
      - `PAYMENT_PROVIDER=stripe`
      - `STRIPE_SECRET_KEY=sk_test_...`
      - `STRIPE_PUBLIC_KEY=pk_test_...`
      - (plus tard PAY-02) `STRIPE_WEBHOOK_SECRET=whsec_...`
    - Backend: créer un PaymentIntent (ou Checkout Session) via Stripe API et stocker `provider_reference`.
    - API: retourner `client_secret` (PaymentIntent) ou `checkout_url` (Checkout).
    - Test paiement: carte Stripe test (ex: `4242 4242 4242 4242`).

- **[BLOCKED] (PAY-01P) PayPal (Sandbox) — Paiement (Orders API)**
  - **Module**: Backend
  - **Dépend de**: PAY-01
  - **AC**:
    - **Phase restante (bloquante)**: créer le compte PayPal Developer + app Sandbox et récupérer les clés (pour pouvoir renseigner `.env` et valider en end-to-end).
    - Créer un compte **PayPal Developer** + activer **Sandbox**.
    - Créer une app Sandbox + récupérer (`client_id`, `client_secret`).
    - Ajouter dans `backend/.env`:
      - `PAYMENT_PROVIDER=paypal`
      - `PAYPAL_CLIENT_ID=...`
      - `PAYPAL_CLIENT_SECRET=...`
      - `PAYPAL_MODE=sandbox`
    - Backend: créer un ordre PayPal (Orders API) et stocker `provider_reference`.
    - API: retourner un `approval_url` (lien de validation PayPal) + statut initial.
    - **Validation**: `php artisan optimize:clear` puis `invoke_api_tests.ps1` ; une fois OK, passer PAY-01P en `DONE` et démarrer PAY-02.

- **[DONE] ✅ (PAY-02S) Stripe webhooks + idempotence** *(Completed)*
   - **Module**: Backend
   - **Dépend de**: PAY-01S
   - **AC**:
     - Endpoint `POST /api/v1/webhooks/stripe`.
     - Vérification signature `Stripe-Signature` quand `STRIPE_WEBHOOK_SECRET` est défini.
     - Idempotence via table `webhook_events` (`provider` + `event_id` unique).
     - Sync `payments.status` + `orders.status` (ex: `pending` → `paid`) sur `payment_intent.succeeded` / `payment_intent.payment_failed`.

 - **[BLOCKED] (PAY-02P) PayPal webhooks + idempotence**
   - **Module**: Backend
   - **Dépend de**: PAY-01P
   - **AC**:
     - Configurer webhooks PayPal Sandbox + vérification signature.
     - Idempotence via table `webhook_events`.
     - Sync statut paiement/commande depuis les events PayPal.

- **[DONE] ✅ (PAY-03) COD (paiement livraison) par zone** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: C-04, CK-04
  - **AC**: 
    - COD activable par zone via config `cod.php` (pays, état, code postal avec wildcards).
    - Service `CodEligibilityService` pour validation d'éligibilité.
    - Provider `CodPaymentProvider` intégré via `PaymentProviderResolver`.
    - Statuts configurables (`COD_PAYMENT_STATUS`, `COD_ORDER_STATUS`).
    - Tests unitaires + feature + PowerShell couvrant zones éligibles/non éligibles.
    - Documentation complète dans `docs/COD_CONFIGURATION.md`.
  - **Validation**: `php artisan test --filter=Cod` puis `invoke_api_tests.ps1` avec `PAYMENT_PROVIDER=cod`.

- **[DONE] ✅ (ORD-01) Workflow statuts commande + permissions** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: C-03, B-04
  - **AC**: 
    - Service `OrderStatusService` avec transitions contrôlées (8 statuts).
    - Policy `OrderPolicy` pour permissions (view, updateStatus, cancel).
    - Controller `OrderStatusController` avec endpoints REST.
    - Logs structurés dans canal `catalogue` pour chaque transition.
    - Tests unitaires + feature couvrant toutes les transitions et permissions.
  - **Validation**: `php artisan test --filter=OrderStatus`

- **[DONE] ✅ (ORD-02) Notifications (email) événements commande** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: ORD-01
  - **AC**: 
    - Event `OrderStatusChanged` dispatché à chaque transition.
    - Listener `SendOrderStatusNotification` (queued) pour envoi email.
    - Mailable `OrderStatusChanged` avec template Blade responsive.
    - Configuration SMTP dynamique via `.env` (support tous providers).
    - Documentation complète providers SMTP dans `docs/ORDER_WORKFLOW.md`.
  - **Validation**: Configurer SMTP dans `.env` puis tester transitions de statut.

---

## 5) Backend — Livraison

- **[DONE] ✅ (SHIP-01) Méthodes livraison + zones + tarification** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: C-04
  - **AC**: 
    - Configuration flexible dans `config/shipping.php` (zones, méthodes, tarifs).
    - Service `ShippingService` avec calcul déterministe (flat, weight_based, price_based, free).
    - Support zones géographiques avec wildcards codes postaux (ex: 75*, 20*).
    - Priorité zones spécifiques sur zones génériques.
    - Seuils de gratuité configurables par zone (`free_above`).

- **[TODO] (INT-01) Localisation Backend FR/EN/AR**
  - **Module**: Backend
  - **Dépend de**: CMS-01, CMS-02
  - **AC**:
    - Ajouter colonnes JSON/fields de traduction (titre, contenu, meta) pour pages/blocks/promotions produits.
    - Endpoints `?lang=` pour retourner les contenus localisés, fallback FR si non traduit.
    - Traductions pour messages API/erreurs clés via fichiers `lang/fr|en|ar`.
    - Synchronisation avec frontend: exposer langues disponibles via `/config/i18n`.
  - **Sous-tâches**:
    - (INT-01a) Étendre migrations pages/blocks/promotions (et produits si nécessaire) avec colonnes JSON `*_translations`.
    - (INT-01b) Adapter modèles/contrôleurs pour lecture/écriture selon `?lang=` + fallback FR.
    - (INT-01c) Créer endpoint `/config/i18n` basé sur `config/i18n.php`.
    - (INT-01d) Ajouter fichiers `resources/lang/fr|en|ar/*.php` pour messages API/erreurs.
  - **Validation**: `php artisan test --filter=Shipping`
- **[DONE] ✅ (SHIP-02) Tracking colis (manuel)** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: ORD-01
  - **AC**: 
    - Champs tracking ajoutés au modèle Order (tracking_number, carrier, url, shipped_at, delivered_at).
    - Controller `OrderTrackingController` avec 3 endpoints (update, show, markDelivered).
    - Ajout tracking passe automatiquement la commande en statut `shipped`.
    - Permissions: Admin pour ajout/modification, Client pour consultation.
    - Tests feature (5 tests) couvrant toutes les fonctionnalités.
    - Documentation complète dans `docs/TRACKING_AND_RETURNS.md`.
  - **Validation**: `php artisan test --filter=OrderTrackingTest`

- **[DONE] ✅ (SHIP-03) Retours / RMA (optionnel)** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: C-01
  - **AC**: 
    - Table `order_returns` avec workflow complet (requested → approved → received → refunded).
    - Modèle `OrderReturn` avec relations Order et User.
    - Controller `OrderReturnController` avec 8 endpoints (CRUD + workflow).
    - 6 statuts de retour (requested, approved, rejected, received, refunded, completed).
    - Raisons de retour configurables (defective, wrong_item, not_as_described, changed_mind, other).
    - Permissions granulaires (Client: demande/tracking, Admin: approbation/gestion).
    - Validation: pas de retours multiples, commande doit être livrée.
    - Tests feature (11 tests) couvrant tout le workflow.
    - Documentation complète avec exemples Vue 3 dans `docs/TRACKING_AND_RETURNS.md`.
  - **Validation**: `php artisan test --filter=OrderReturnTest`

---

## 6) Backend — Promotions & CMS

- **[DONE] ✅ (PR-01) Coupons** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: B-01
  - **AC**: 
    - Table `coupons` avec types (fixed, percentage) et conditions multiples.
    - Table `coupon_usage` pour tracking utilisation par utilisateur.
    - Modèle `Coupon` avec validation (dates, limites, produits/catégories applicables).
    - Service `CouponService` pour validation et calcul de réduction.
    - Controller `CouponController` avec CRUD admin + endpoint validation client.
    - Conditions: montant minimum, limite d'usage globale/par utilisateur, dates validité.
    - Support produits et catégories applicables.
    - Routes: `/coupons/validate` (auth), `/admin/coupons/*` (admin).
  - **Validation**: Migrations + modèles + controllers créés

- **[DONE] ✅ (PR-02) Promotions (prix barré / règles)** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: CAT-02
  - **AC**: 
    - Table `promotions` avec types (product, category, cart) et discount (fixed, percentage).
    - Modèle `Promotion` avec validation dates et priorités.
    - Controller `PromotionController` avec CRUD admin + liste publique.
    - Support promotions par produit, catégorie ou panier global.
    - Priorités pour gestion de plusieurs promotions simultanées.
    - Routes: `/promotions` (public), `/admin/promotions/*` (admin).
  - **Validation**: Migrations + modèles + controllers créés

- **[DONE] ✅ (CMS-01) Pages CMS (FAQ/CGU/Confidentialité)** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: B-01
  - **AC**: 
    - Table `pages` avec slug unique, contenu, meta SEO.
    - Modèle `Page` avec auto-génération slug depuis titre.
    - Controller `PageController` avec CRUD admin + endpoints publics.
    - Champs: title, slug, content, meta_description, meta_keywords, is_published, order.
    - Routes: `/pages` (liste publique), `/pages/{slug}` (affichage), `/admin/pages/*` (admin).
  - **Validation**: Migrations + modèles + controllers créés

- **[DONE] ✅ (CMS-02) Home configurable (sections, slider)** *(Completed)*
  - **Module**: Backend
  - **Dépend de**: CMS-01
  - **AC**: 
    - Table `blocks` avec clé unique, type, contenu JSON flexible.
    - Modèle `Block` pour widgets/sections configurables.
    - Controller `BlockController` avec CRUD admin + endpoints publics.
    - Types supportés: slider, banner, featured_products, text, html.
    - Ordre et activation/désactivation par block.
    - Routes: `/blocks` (liste publique), `/blocks/{key}` (affichage), `/admin/blocks/*` (admin).
  - **Validation**: Migrations + modèles + controllers créés

---

## 7) Front Vue.js — Client

### 🎨 Design Reference

**Style principal:**
- **Couleurs:**
  - Primaire: Orange/Jaune (#FFA500, #FFD700) - Fond dégradé chaleureux
  - Secondaire: Noir/Gris foncé (#2C2C2C, #3A3A3A) - Header et navigation
  - Accent: Orange vif (#FF6B35) - Boutons CTA et prix
  - Texte: Blanc sur fond sombre, Noir sur fond clair
  
- **Typographie:**
  - Titres: Bold, grandes tailles, majuscules pour impact
  - Prix: Orange/Rouge pour promotions, gris barré pour prix originaux
  
- **Composants clés:**
  - Header noir avec menu hamburger, recherche, langue, panier
  - Hero slider avec fond image + overlay texte centré
  - Boutons CTA noirs avec texte blanc
  - Cards produits avec image, titre, prix en orange
  - Navigation catégories avec dropdown
  
- **Layout:**
  - Header fixe en haut
  - Hero full-width avec slider
  - Grille produits 3-4 colonnes
  - Responsive mobile-first
  
- **Framework:** Vue 3 + Vite + PrimeVue + PrimeFlex

#### 🖼️ Référence visuelle (mockup partagé)
- **Top utility bar** : fine bande noire avec liens texte (Best Sellers, Gift Ideas, New Releases, Today’s Deals, Customer Service) centrés et espacement large.
- **Header principal** : 
  - Logo typographique jaune/orangé sur fond blanc cassé.
  - Bouton hamburger à gauche + select catégorie sombre + barre de recherche large (placeholder gris clair + bouton recherche orange vif).
  - Sélecteur de langue minimal (drapeau + caret) et actions `Cart`, `Wishlist`.
- **Hero** :
  - Fond jaune dégradé, photo lifestyle centrée (personne tenant un sac).
  - Texte all caps en blanc « GET START YOUR FAVRIOT SHOPING » avec interlettrage serré.
  - CTA « BUY NOW » bouton noir bords arrondis, hover blanc texte noir.
  - Slider controls circulaires (points) beige.
- **Section produits** :
  - Titre `Man & Woman Fashion` en serif noir + soulignement fin gris.
  - Cards blanches avec ombre douce, chacun `Man T-shirt`, `Man-shirt`, `Woman Scarf`, prix orange.
- **Palette à suivre** :
  - Jaune moutarde #FFB400, orange #FF8700 pour CTA.
  - Noir profond #111, gris clair #F5F5F5 pour fonds.
  - Blanc pur pour textes sur fond sombre.
- **Micro-interactions** : survol navigation → soulignement orange, boutons CTA → légère translation + shadow, slider auto-play 5s.

---

- **[DONE] ✅ (F-01) Setup SPA Vue 3 (router, pinia, axios)** *(Completed)*
  - **Module**: Front Client
  - **Dépend de**: B-05
  - **AC**: Layout, routes publiques, gestion erreurs.

- **[TODO] (F-02) Auth client (login/register) + session**
  - **Module**: Front Client
  - **Dépend de**: B-03, F-01
  - **AC**: Login/logout, routes protégées.

- **[TODO] (F-03) Home (sections + promotions)**
  - **Module**: Front Client
  - **Dépend de**: CMS-02, F-01
  - **AC**: Rendu sections, responsive.

- **[TODO] (F-04) Listing catégories + filtres**
  - **Module**: Front Client
  - **Dépend de**: CAT-05
  - **AC**:
    - Filtres dynamiques, pagination.
    - UI cohérente avec le thème (fonds clairs, cards blanches, boutons orange/jaune, pas de fond noir ni boutons verts).

- **[TODO] (F-05) Page produit + variantes**
  - **Module**: Front Client
  - **Dépend de**: CAT-03
  - **AC**: Sélection variant, stock affiché, ajout panier.

- **[TODO] (F-06) Panier (localStorage + sync API)**
  - **Module**: Front Client
  - **Dépend de**: CK-01
  - **AC**: Panier persistant, merge après login.

- **[TODO] (F-07) Checkout 1 page**
  - **Module**: Front Client
  - **Dépend de**: CK-04, SHIP-01, PR-01
  - **AC**: Adresse, livraison, coupon, paiement/COD.

- **[TODO] (F-08) Compte client (adresses, commandes, tracking)**
  - **Module**: Front Client
  - **Dépend de**: CK-03, ORD-01, SHIP-02
  - **AC**:
    - Liste commandes, détail commande, suivi colis.
    - Carnet d’adresses : ajout/édition/suppression, adresse par défaut, sélection facturation/livraison sur checkout.

- **[TODO] (F-09) Pages CMS (FAQ/CGU/Confidentialité)**
  - **Module**: Front Client
  - **Dépend de**: CMS-01
  - **AC**: Rendu pages + SEO basique.

- **[TODO] (F-10) Pixels marketing (optionnel)**
  - **Module**: Front Client
  - **Dépend de**: C-01
  - **AC**: GA4/Meta/TikTok configurables, consentement si requis.

- **[TODO] (F-11) Multilingue FR/EN/AR (switch + contenus)**
  - **Module**: Front Client
  - **Dépend de**: F-01, CMS-01
  - **AC**:
    - Mise en place i18n (vue-i18n ou Pinia store) avec locales `fr`, `en`, `ar`.
    - Switch langue global dans header (persisté localStorage + fallback FR).
    - Gestion RTL pour arabe (classe `dir="rtl"` sur `<html>` + styles adaptés).
    - Textes statiques traduits (menus, CTA, formulaires, validations) et contenu CMS traduit via backend.

---

## 8) Admin Dashboard Vue — Back office

- **[TODO] (A-01) Setup SPA Admin + Auth + ACL UI**
  - **Module**: Admin
  - **Dépend de**: B-03, B-04
  - **AC**: Menus selon permissions, routes protégées.

- **[TODO] (A-02) Catalogue admin (catégories, produits, variantes, médias)**
  - **Module**: Admin
  - **Dépend de**: CAT-03, B-06
  - **AC**: CRUD complet + upload images.

- **[TODO] (A-03) Commandes admin (workflow statuts)**
  - **Module**: Admin
  - **Dépend de**: ORD-01
  - **AC**: Changement statut autorisé, ajout tracking.

- **[TODO] (A-04) Clients admin (listing/détail)**
  - **Module**: Admin
  - **Dépend de**: B-03
  - **AC**: Consultation commandes client, infos.

- **[TODO] (A-05) Coupons/Promotions admin**
  - **Module**: Admin
  - **Dépend de**: PR-01, PR-02
  - **AC**: CRUD coupons/promos.

- **[TODO] (A-06) CMS admin (pages + home sections)**
  - **Module**: Admin
  - **Dépend de**: CMS-02
  - **AC**: CRUD pages + gestion slider/sections.

- **[TODO] (A-07) Modérateurs & permissions**
  - **Module**: Admin
  - **Dépend de**: B-04
  - **AC**: Création compte modérateur + assignation permissions.

- **[TODO] (A-08) Dashboard stats**
  - **Module**: Admin
  - **Dépend de**: ORD-01
  - **AC**: KPIs: ventes, commandes, top produits.

---

## 9) Qualité / Sécurité / Performance / Infra

- **[TODO] (Q-01) Tests unitaires & intégration API**
  - **Module**: Backend
  - **Dépend de**: B-01
  - **AC**: Couverture endpoints critiques (auth, checkout, paiement webhook).

- **[TODO] (Q-02) Queues/Jobs (Redis) + retries**
  - **Module**: Backend
  - **Dépend de**: ORD-02
  - **AC**: Worker OK, jobs email fonctionnels.

- **[TODO] (Q-03) Cache Redis (catégories/home/produits)**
  - **Module**: Backend
  - **Dépend de**: CAT-02, CMS-02
  - **AC**: Cache + invalidation sur update.

- **[TODO] (Q-04) Sécurité (CORS, rate limit, headers)**
  - **Module**: Backend
  - **Dépend de**: B-03
  - **AC**: CORS configuré, throttling, headers sécurisés.

- **[TODO] (Q-05) Observability (logs erreurs + monitoring)**
  - **Module**: Backend
  - **Dépend de**: B-07
  - **AC**: Logs structurés, suivi erreurs.
