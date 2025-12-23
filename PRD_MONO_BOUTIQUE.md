# PRD — Plateforme e-commerce multi-catégories (Mono-boutique / Mono-vendeur)

## 1. Objectif & périmètre

### Objectif
Mettre en place une plateforme e-commerce **pour une seule boutique et un seul vendeur** avec un front client et un dashboard admin, basés sur une **API REST Laravel** consommée par des SPA **Vue.js 3**.

### Périmètre (inclus)
- Catalogue multi-catégories et produits avec variantes
- Panier + checkout (1 page)
- Commandes + statuts + notifications
- Paiement carte (PSP) + COD (paiement à la livraison)
- Livraison (méthodes + tarification par zone) + suivi colis
- Espace client (compte, commandes, adresses)
- Admin dashboard (catalogue, commandes, clients, CMS, promotions, paramètres)
- Sécurité, performance, logs et monitoring

### Hors périmètre (exclu)
- Marketplace / multi-vendeurs / multi-boutiques (pas d’espace vendeur indépendant)
- Paiements “payouts” vers vendeurs

---

## 2. Fonctionnalités générales

### Boutique unique
- Une seule boutique
- Un seul vendeur (gestion interne via admin)
- Compatible multi-secteurs (vêtements, tech, beauté, etc.)

### Catalogue
- Catégories personnalisables (hiérarchie)
- Produits : titre, description, images, prix, stock, statut
- Variantes : taille/couleur/modèle… (stock/prix par variante si nécessaire)
- SEO : titre, meta description, slug
- Import / export CSV/Excel

### Administration (Dashboard Admin)
> **Toutes les fonctionnalités du dashboard utilisent exclusivement l’API REST Laravel (frontend Vue.js).**

- Gestion catalogue (catégories, produits, variantes, stock, médias)
- Gestion commandes (statuts, paiement, livraison, notes internes)
- Gestion clients
- Gestion pages CMS (site vitrine)
- Coupons & promotions
- Modes de paiement (carte, COD)
- Modes de livraison (standard, express, pickup)
- Gestion des modérateurs (comptes + permissions via Spatie)
- Paramètres (devise, taxes, email, SEO global)
- Statistiques (ventes, commandes, top produits, trafic si tracking)
- Gestion Marketing (facebook pixel, google pixel etc...)

---

## 3. Front Office (client)

### Expérience utilisateur
- Page d’accueil personnalisable (sections)
- Carrousel / promotions
- Recherche (avec filtres)
- Responsive & mobile-first

### Pages essentielles
- Page produit complète
- Page catégories + filtres dynamiques
- Page promotions
- Pages CMS : FAQ / CGU / Confidentialité

---

## 4. Panier & Commande

### Panier
- Panier persistant
  - Visiteur : localStorage
  - Utilisateur connecté : synchronisation API + merge à la connexion
- Mise à jour dynamique
- Calcul total automatique (sous-total, livraison, remise, taxes)

### Checkout
- Checkout en une page
- Choix livraison
- Adresse (création/choix)
- Application coupon
- Création commande avec **snapshot** des prix/produits

---

## 5. Paiement

### Paiement par carte
- Intégration PSP (à confirmer : Stripe / PayPal / Paymee)
- Webhooks PSP pour mise à jour statut paiement
- 3D Secure si supporté par le PSP

### Paiement à la livraison (COD)
- Activation par zone
- Confirmation email/SMS (si SMS activé)

### Statuts paiement (référence)
- `unpaid` → `pending` (si PSP) → `paid` ou `failed`
- `cod` (paiement attendu à la livraison)
- `refunded` (si remboursement)

---

## 6. Livraison & Logistique

- Méthodes de livraison : standard, express, pickup
- Tarification par zone
- Suivi colis (tracking)
- Retours (RMA) : optionnel selon version
- laise la possibilite d'integer un api de l'un des societe de livriason local

---

## 7. Espace utilisateur

- Compte / login
- Historique commandes
- Suivi livraison
- Gestion adresses
- Coupons & fidélité (fidélité optionnelle selon version)

---

## 8. Statuts commandes (référence)

Proposition de base (à confirmer) :
- `draft` (optionnel) : commande non finalisée
- `pending` : créée, en attente de paiement/confirmation
- `confirmed` : validée (ex: COD confirmé ou paiement OK)
- `processing` : préparation
- `shipped` : expédiée
- `delivered` : livrée
- `canceled` : annulée
- `refunded` : remboursée (si applicable)

Transitions (exemple) :
- `pending` → `confirmed` → `processing` → `shipped` → `delivered`
- `pending` → `canceled`
- `paid` + annulation → `refunded`

---

## 9. Marketing

- Coupons, promotions
- Pixels publicitaires (GA4, Meta, TikTok) côté front
- Upsell / cross-sell / post-achat upsell : optionnel selon version

---

## 10. Sécurité, RGPD, performance

### Sécurité
- HTTPS obligatoire
- Auth tokens + ACL (Spatie)
- Rate limiting (brute force)
- Validation Form Request
- Logs connexion + audit actions admin

### RGPD
- Pages légales
- Consentement cookies (si tracking)
- Export/suppression compte (si requis)

### Performance
- Cache Redis (catégories, produits, home)
- CDN optionnel
- Lazy loading côté front
- Compression images (WebP)

---

## 11. Architecture technique

### Backend (Laravel 12)
- PHP 8.2+
- MVC + Services + Repositories
- API REST
- Jobs/Queues (Redis) pour emails/SMS
- Observers pour stock/commande
- Tests unitaires & intégration

### Front (Vue 3)
- SPA
- Router + Pinia
- Axios
- TailwindCSS + Headless UI/Flowbite

### DB
- MySQL/MariaDB
- Index sur produits/catégories/commandes

---

## 12. Intégrations

- PSP paiement (webhooks)
- Email (SMTP) + SMS (optionnel)
- Transporteur (API optionnelle) ou tracking manuel
