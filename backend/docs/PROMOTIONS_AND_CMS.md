# Guide Promotions & CMS (PR-01, PR-02, CMS-01, CMS-02)

## Vue d'ensemble

Ce guide couvre 4 fonctionnalités:
- **PR-01**: Système de coupons avec conditions
- **PR-02**: Promotions automatiques (prix barrés)
- **CMS-01**: Gestion de pages CMS
- **CMS-02**: Système de blocks/widgets configurables

---

## PR-01: Coupons

### Modèle de données

```php
// Table: coupons
'code'                      // Code unique (ex: SUMMER2025)
'type'                      // fixed, percentage
'value'                     // Montant ou pourcentage
'min_order_amount'          // Montant minimum de commande
'max_discount_amount'       // Plafond de réduction
'usage_limit'               // Limite d'utilisation globale
'usage_count'               // Compteur d'utilisation
'usage_limit_per_user'      // Limite par utilisateur
'starts_at'                 // Date de début
'expires_at'                // Date d'expiration
'is_active'                 // Actif/Inactif
'applicable_products'       // IDs produits applicables (JSON)
'applicable_categories'     // IDs catégories applicables (JSON)
```

### Types de coupons

#### 1. Réduction fixe
```json
{
  "code": "SAVE10",
  "type": "fixed",
  "value": 10.00,
  "min_order_amount": 50.00
}
```
**Résultat**: -10€ sur commandes ≥ 50€

#### 2. Réduction pourcentage
```json
{
  "code": "SAVE20",
  "type": "percentage",
  "value": 20,
  "max_discount_amount": 50.00
}
```
**Résultat**: -20% (max 50€)

### API Endpoints

#### Valider un coupon (Client)
```http
POST /api/v1/coupons/validate
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "SUMMER2025"
}
```

**Réponse succès:**
```json
{
  "valid": true,
  "discount_amount": 15.50,
  "message": "Coupon applied successfully",
  "coupon": {
    "code": "SUMMER2025",
    "type": "percentage",
    "value": 20
  }
}
```

**Réponse erreur:**
```json
{
  "valid": false,
  "error": "Coupon is not valid or has expired"
}
```

#### CRUD Admin

**Lister les coupons:**
```http
GET /api/v1/admin/coupons
Authorization: Bearer {admin_token}
```

**Créer un coupon:**
```http
POST /api/v1/admin/coupons
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "code": "WELCOME10",
  "type": "fixed",
  "value": 10.00,
  "min_order_amount": 30.00,
  "usage_limit": 100,
  "usage_limit_per_user": 1,
  "starts_at": "2025-01-01T00:00:00Z",
  "expires_at": "2025-12-31T23:59:59Z",
  "is_active": true,
  "applicable_products": [1, 2, 3],
  "applicable_categories": [5, 6]
}
```

**Mettre à jour:**
```http
PATCH /api/v1/admin/coupons/{id}
```

**Supprimer:**
```http
DELETE /api/v1/admin/coupons/{id}
```

### Conditions de validation

Le service `CouponService` valide:
1. ✅ Coupon existe
2. ✅ Coupon actif (`is_active = true`)
3. ✅ Date de début respectée
4. ✅ Date d'expiration non dépassée
5. ✅ Limite d'usage globale non atteinte
6. ✅ Limite d'usage par utilisateur non atteinte
7. ✅ Montant minimum de commande respecté
8. ✅ Produits applicables (si définis)
9. ✅ Catégories applicables (si définies)

### Intégration panier

```javascript
// Appliquer un coupon
async function applyCoupon(code) {
  try {
    const response = await fetch('/api/v1/coupons/validate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({ code })
    });
    
    const result = await response.json();
    
    if (result.valid) {
      // Mettre à jour le panier avec la réduction
      cart.discount = result.discount_amount;
      cart.coupon_code = code;
    } else {
      alert(result.error);
    }
  } catch (error) {
    console.error('Erreur validation coupon:', error);
  }
}
```

---

## PR-02: Promotions

### Modèle de données

```php
// Table: promotions
'name'                      // Nom de la promotion
'type'                      // product, category, cart
'discount_type'             // fixed, percentage
'discount_value'            // Montant ou pourcentage
'applicable_products'       // IDs produits (JSON)
'applicable_categories'     // IDs catégories (JSON)
'min_order_amount'          // Montant minimum (pour type cart)
'priority'                  // Priorité (0-100)
'starts_at'                 // Date de début
'expires_at'                // Date d'expiration
'is_active'                 // Actif/Inactif
```

### Types de promotions

#### 1. Promotion produit
```json
{
  "name": "Soldes iPhone",
  "type": "product",
  "discount_type": "percentage",
  "discount_value": 15,
  "applicable_products": [10, 11, 12],
  "priority": 10
}
```

#### 2. Promotion catégorie
```json
{
  "name": "Électronique -20%",
  "type": "category",
  "discount_type": "percentage",
  "discount_value": 20,
  "applicable_categories": [3, 4],
  "priority": 5
}
```

#### 3. Promotion panier
```json
{
  "name": "Livraison offerte",
  "type": "cart",
  "discount_type": "fixed",
  "discount_value": 5.90,
  "min_order_amount": 50.00,
  "priority": 1
}
```

### API Endpoints

#### Lister les promotions actives (Public)
```http
GET /api/v1/promotions
```

**Réponse:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Soldes d'été",
      "type": "category",
      "discount_type": "percentage",
      "discount_value": 30,
      "applicable_categories": [1, 2],
      "priority": 10,
      "starts_at": "2025-06-01T00:00:00Z",
      "expires_at": "2025-08-31T23:59:59Z"
    }
  ]
}
```

#### CRUD Admin

**Créer une promotion:**
```http
POST /api/v1/admin/promotions
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "Black Friday",
  "type": "product",
  "discount_type": "percentage",
  "discount_value": 50,
  "applicable_products": [1, 2, 3, 4, 5],
  "priority": 100,
  "starts_at": "2025-11-29T00:00:00Z",
  "expires_at": "2025-11-29T23:59:59Z",
  "is_active": true
}
```

### Affichage prix barrés

```vue
<template>
  <div class="product-price">
    <span v-if="hasPromotion" class="original-price">
      {{ originalPrice }}€
    </span>
    <span class="current-price" :class="{ 'promo': hasPromotion }">
      {{ currentPrice }}€
    </span>
    <span v-if="hasPromotion" class="discount-badge">
      -{{ discountPercent }}%
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  product: Object,
  promotion: Object
});

const hasPromotion = computed(() => !!props.promotion);

const originalPrice = computed(() => props.product.price);

const currentPrice = computed(() => {
  if (!props.promotion) return props.product.price;
  
  if (props.promotion.discount_type === 'fixed') {
    return props.product.price - props.promotion.discount_value;
  } else {
    return props.product.price * (1 - props.promotion.discount_value / 100);
  }
});

const discountPercent = computed(() => {
  if (!props.promotion) return 0;
  
  if (props.promotion.discount_type === 'percentage') {
    return props.promotion.discount_value;
  } else {
    return Math.round((props.promotion.discount_value / props.product.price) * 100);
  }
});
</script>

<style scoped>
.original-price {
  text-decoration: line-through;
  color: #999;
  margin-right: 10px;
}

.current-price.promo {
  color: #e74c3c;
  font-weight: bold;
}

.discount-badge {
  background: #e74c3c;
  color: white;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.9em;
  margin-left: 10px;
}
</style>
```

---

## CMS-01: Pages CMS

### Modèle de données

```php
// Table: pages
'title'                     // Titre de la page
'slug'                      // URL slug (auto-généré)
'content'                   // Contenu HTML/Markdown
'meta_description'          // Description SEO
'meta_keywords'             // Mots-clés SEO (JSON)
'is_published'              // Publié/Brouillon
'order'                     // Ordre d'affichage
```

### API Endpoints

#### Lister les pages (Public)
```http
GET /api/v1/pages
```

**Réponse:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Conditions Générales de Vente",
      "slug": "cgv",
      "meta_description": "Nos conditions générales de vente"
    },
    {
      "id": 2,
      "title": "Politique de Confidentialité",
      "slug": "confidentialite",
      "meta_description": "Notre politique de confidentialité"
    }
  ]
}
```

#### Afficher une page (Public)
```http
GET /api/v1/pages/{slug}
```

**Exemple:**
```http
GET /api/v1/pages/cgv
```

**Réponse:**
```json
{
  "data": {
    "id": 1,
    "title": "Conditions Générales de Vente",
    "slug": "cgv",
    "content": "<h1>CGV</h1><p>...</p>",
    "meta_description": "Nos conditions générales de vente",
    "meta_keywords": ["cgv", "conditions", "vente"],
    "created_at": "2025-01-01T00:00:00Z",
    "updated_at": "2025-01-15T10:30:00Z"
  }
}
```

#### CRUD Admin

**Créer une page:**
```http
POST /api/v1/admin/pages
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "title": "FAQ",
  "slug": "faq",
  "content": "<h1>Questions Fréquentes</h1>...",
  "meta_description": "Trouvez les réponses à vos questions",
  "meta_keywords": ["faq", "questions", "aide"],
  "is_published": true,
  "order": 1
}
```

**Mettre à jour:**
```http
PATCH /api/v1/admin/pages/{id}
```

**Supprimer:**
```http
DELETE /api/v1/admin/pages/{id}
```

### Composant Vue

```vue
<template>
  <div class="cms-page">
    <h1>{{ page.title }}</h1>
    <div class="content" v-html="page.content"></div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const page = ref(null);

onMounted(async () => {
  const response = await fetch(`/api/v1/pages/${route.params.slug}`);
  const data = await response.json();
  page.value = data.data;
  
  // SEO
  document.title = page.value.title;
  document.querySelector('meta[name="description"]')
    .setAttribute('content', page.value.meta_description);
});
</script>
```

---

## CMS-02: Blocks/Widgets

### Modèle de données

```php
// Table: blocks
'key'                       // Clé unique (ex: home-slider)
'type'                      // slider, banner, featured_products, text, html
'title'                     // Titre (optionnel)
'content'                   // Contenu JSON flexible
'order'                     // Ordre d'affichage
'is_active'                 // Actif/Inactif
```

### Types de blocks

#### 1. Slider
```json
{
  "key": "home-slider",
  "type": "slider",
  "title": "Slider principal",
  "content": {
    "slides": [
      {
        "image": "/images/slide1.jpg",
        "title": "Nouveautés 2025",
        "subtitle": "Découvrez notre collection",
        "link": "/products",
        "button_text": "Voir plus"
      },
      {
        "image": "/images/slide2.jpg",
        "title": "Soldes -50%",
        "subtitle": "Sur une sélection d'articles",
        "link": "/promotions"
      }
    ],
    "autoplay": true,
    "interval": 5000
  },
  "order": 1,
  "is_active": true
}
```

#### 2. Banner
```json
{
  "key": "promo-banner",
  "type": "banner",
  "content": {
    "image": "/images/banner.jpg",
    "text": "Livraison gratuite dès 50€",
    "link": "/shipping",
    "background_color": "#ff6b6b"
  },
  "order": 2,
  "is_active": true
}
```

#### 3. Featured Products
```json
{
  "key": "featured-products",
  "type": "featured_products",
  "title": "Nos coups de cœur",
  "content": {
    "product_ids": [1, 5, 8, 12],
    "display_mode": "grid",
    "columns": 4
  },
  "order": 3,
  "is_active": true
}
```

#### 4. Text
```json
{
  "key": "welcome-text",
  "type": "text",
  "title": "Bienvenue",
  "content": {
    "text": "Découvrez notre boutique en ligne...",
    "alignment": "center"
  },
  "order": 4,
  "is_active": true
}
```

#### 5. HTML
```json
{
  "key": "custom-section",
  "type": "html",
  "content": {
    "html": "<div class='custom'>...</div>"
  },
  "order": 5,
  "is_active": true
}
```

### API Endpoints

#### Lister les blocks actifs (Public)
```http
GET /api/v1/blocks
```

**Réponse:**
```json
{
  "data": [
    {
      "id": 1,
      "key": "home-slider",
      "type": "slider",
      "title": "Slider principal",
      "content": { ... },
      "order": 1
    }
  ]
}
```

#### Afficher un block (Public)
```http
GET /api/v1/blocks/{key}
```

**Exemple:**
```http
GET /api/v1/blocks/home-slider
```

#### CRUD Admin

**Créer un block:**
```http
POST /api/v1/admin/blocks
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "key": "home-slider",
  "type": "slider",
  "title": "Slider principal",
  "content": { ... },
  "order": 1,
  "is_active": true
}
```

### Composant Vue dynamique

```vue
<template>
  <div class="home-page">
    <BlockRenderer
      v-for="block in blocks"
      :key="block.key"
      :block="block"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import BlockRenderer from '@/components/BlockRenderer.vue';

const blocks = ref([]);

onMounted(async () => {
  const response = await fetch('/api/v1/blocks');
  const data = await response.json();
  blocks.value = data.data;
});
</script>
```

```vue
<!-- BlockRenderer.vue -->
<template>
  <component
    :is="blockComponent"
    :block="block"
  />
</template>

<script setup>
import { computed } from 'vue';
import SliderBlock from './blocks/SliderBlock.vue';
import BannerBlock from './blocks/BannerBlock.vue';
import FeaturedProductsBlock from './blocks/FeaturedProductsBlock.vue';
import TextBlock from './blocks/TextBlock.vue';
import HtmlBlock from './blocks/HtmlBlock.vue';

const props = defineProps({
  block: Object
});

const blockComponent = computed(() => {
  const components = {
    slider: SliderBlock,
    banner: BannerBlock,
    featured_products: FeaturedProductsBlock,
    text: TextBlock,
    html: HtmlBlock
  };
  
  return components[props.block.type] || TextBlock;
});
</script>
```

---

## Permissions

### Coupons
- **Validation**: Utilisateur authentifié
- **CRUD**: Admin uniquement

### Promotions
- **Liste publique**: Tous
- **CRUD**: Admin uniquement

### Pages
- **Liste/Affichage**: Tous (pages publiées)
- **CRUD**: Admin uniquement

### Blocks
- **Liste/Affichage**: Tous (blocks actifs)
- **CRUD**: Admin uniquement

---

## Base de données

### Migrations à exécuter
```bash
php artisan migrate
```

**Tables créées:**
- `coupons`
- `coupon_usage`
- `promotions`
- `pages`
- `blocks`

---

## Sécurité

✅ **Validation backend** - Toutes les données validées
✅ **Permissions strictes** - Admin vs Public bien séparés
✅ **Sanitization** - Contenu HTML nettoyé
✅ **Rate limiting** - Protection contre abus
✅ **Logs** - Traçabilité des actions admin

---

## Extension

### Ajouter un type de block personnalisé

1. Créer le composant Vue:
```vue
<!-- CustomBlock.vue -->
<template>
  <div class="custom-block">
    {{ block.content.custom_data }}
  </div>
</template>
```

2. Enregistrer dans BlockRenderer:
```javascript
const components = {
  // ...
  custom: CustomBlock
};
```

3. Créer le block via API:
```json
{
  "key": "my-custom-block",
  "type": "custom",
  "content": {
    "custom_data": "..."
  }
}
```

---

## Support

- **Documentation**: `docs/PROMOTIONS_AND_CMS.md`
- **Migrations**: `database/migrations/*_create_coupons_*`, etc.
- **Modèles**: `app/Models/Coupon.php`, `Promotion.php`, `Page.php`, `Block.php`
- **Controllers**: `app/Http/Controllers/Api/V1/*Controller.php`
- **Routes**: `routes/api_v1.php`
