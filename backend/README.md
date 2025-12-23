## E-commerce API (Laravel 12)

Monorepo backend pour la boutique mono-vendeur / mono-boutique. Ce document décrit la version actuelle de l'API REST (`/api/v1`), les conventions de réponse et la procédure de test automatisé.

---

### 1. Versionnement & Base URL

| Élément              | Valeur                                  |
|----------------------|------------------------------------------|
| Base URL locale      | `http://127.0.0.1:8001`                  |
| Préfixe API stable   | `/api/v1`                                |
| Futur préfixe bêta   | `/api/v2` (routes séparées à venir)      |

Tous les endpoints documentés ci-dessous doivent être préfixés par `/api/v1`. Les routes non versionnées resteront disponibles de manière transitoire mais ne sont pas supportées.

---

### 2. Authentification & Sécurité

| Endpoint                     | Méthode | Corps / Notes                                                                 |
|------------------------------|---------|-------------------------------------------------------------------------------|
| `POST /auth/login`           | POST    | `{ "email": "admin@local.test", "password": "admin123456" }`              |
| `POST /auth/logout`          | POST    | Requiert un header `Authorization: Bearer {token}`                            |
| `GET /user`                  | GET     | Retourne l'utilisateur courant (token Sanctum requis)                         |

- Authentification par token Laravel Sanctum (`Bearer {token}`).
- Les nouveaux utilisateurs `POST /auth/register` reçoivent un token immédiat et, si disponible, le rôle `customer`.
- Les rôles sont gérés via Spatie Permissions (`admin`, `moderator`, `support`, `customer`).

---

### 3. Catalogue – Endpoints Principaux

#### 3.1 Catégories

| Endpoint                                  | Méthode | Auth | Description                                     |
|-------------------------------------------|---------|------|-------------------------------------------------|
| `/categories`                             | GET     | non  | Liste paginée (filtres `only_active`, `parent_id`, `only_roots`, `per_page`). |
| `/categories/{id}`                        | GET     | non  | Détail avec parent + enfants.                   |
| `/categories`                             | POST    | oui  | Création (slug automatique si absent).          |
| `/categories/{id}`                        | PATCH   | oui  | Mise à jour partielle.                          |
| `/categories/{id}`                        | DELETE  | oui  | Suppression logique (hard delete).              |

#### 3.2 Produits

| Endpoint                                  | Méthode | Auth | Description / Filtres                                     |
|-------------------------------------------|---------|------|------------------------------------------------------------|
| `/products`                               | GET     | non  | Filtrage sur `category_id`, `type`, `stock_status`, `q`, `only_active`, `with_variants`; pagination & tri (`sort`, `direction`). |
| `/products/{id}`                          | GET     | non  | Détail produit + `category` + `variants`.                  |
| `/products`                               | POST    | oui  | Création (slug unique auto).                              |
| `/products/{id}`                          | PATCH   | oui  | Mise à jour partielle.                                    |
| `/products/{id}`                          | DELETE  | oui  | Suppression.                                               |

#### 3.3 Variants de produit

Routes imbriquées sous `/products/{product}` :

| Endpoint                                                            | Méthode | Auth | Description                              |
|---------------------------------------------------------------------|---------|------|------------------------------------------|
| `/products/{product}/variants`                                      | GET     | non  | Liste paginée (`only_active`, `stock_status`, `q`). |
| `/products/{product}/variants/{variant}`                            | GET     | non  | Détail d'un variant précis.              |
| `/products/{product}/variants`                                      | POST    | oui  | Création d'un variant pour le produit.   |
| `/products/{product}/variants/{variant}`                            | PATCH   | oui  | Mise à jour partielle.                   |
| `/products/{product}/variants/{variant}`                            | DELETE  | oui  | Suppression.                             |

Tous les controllers catalogue effectuent un logging structuré (`storage/logs/catalogue.log`) via `Log::channel('catalogue')` pour les opérations CRUD (champs : `category_id` / `product_id` / `variant_id`, utilisateur, SKU, etc.).

---

### 4. Formats de réponse

#### 4.1 Collection paginée (ex. `GET /products`)

```json
{
  "data": [
    {
      "id": 1,
      "name": "Example Product",
      "pricing": {
        "price": "49.99",
        "currency": "USD"
      },
      "stock": {
        "quantity": 10,
        "status": "in_stock"
      },
      "variants": [ ... ]
    }
  ],
  "links": {
    "first": "http://127.0.0.1:8001/api/v1/products?page=1",
    "last": "http://127.0.0.1:8001/api/v1/products?page=3",
    "prev": null,
    "next": "http://..."
  },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 37
  }
}
```

#### 4.2 Ressource unique (ex. `GET /categories/{id}`)

```json
{
  "data": {
    "id": 1,
    "name": "Electronics",
    "parent": null,
    "children": [ { "id": 2, "name": "Smartphones" }, ... ],
    "meta": {
      "title": "Electronics",
      "keywords": "electronics"
    }
  }
}
```

Les réponses d'erreur respectent le format Laravel par défaut (`422` pour validation, `401` non authentifié, `404` ressource absente, etc.).

---

### 5. Données de référence & Seeds

| Seeder                | Contenu                                                                |
|-----------------------|-------------------------------------------------------------------------|
| `DatabaseSeeder`      | Crée les rôles Spatie + utilisateur admin (`admin@local.test`).         |
| `CategorySeeder`      | Arbre de catégories (Electronics / Fashion / Home & Living).            |
| `ProductSeeder`       | 15 produits aléatoires reliés aux catégories.                           |
| `ProductVariantSeeder`| 1 à 3 variants par produit (SKU, prix, stock aléatoires).               |

Exécution :

```bash
php artisan migrate --seed
```

---

### 6. Tests automatisés d'API

- **Script PowerShell** : `backend/invoke_api_tests.ps1`
  - Chaîne complète : `health → login → user → categories CRUD → products CRUD → variants CRUD → logout`.
  - Lit/écrit les réponses JSON dans la console (via `ConvertTo-Json`).
  - Génère dynamiquement les SKU/nom pour éviter les collisions uniques.

```powershell
cd backend
powershell -NoProfile -ExecutionPolicy Bypass -File .\invoke_api_tests.ps1
```

- **Collection Postman** : `postman_collection.json`
  - Variables : `token`, `category_id`, `product_id`, `variant_id`.
  - Requêtes organisées par module (Health, Auth, Categories, Products, Variants).

---

### 7. Points d'attention

- **Logging** : fichiers JSON dans `storage/logs/catalogue.log` pour audit métier.
- **Env** : `.env` configuré sur MySQL (`belkhir_store`), port API `8001` (cf. `php artisan serve --port=8001`).
- **Versioning futur** : `/api/v2` disposera d’un fichier de routes dédié (`routes/api_v2.php`). Garder `/api/v1` stable et rétrocompatible.

---

### 8. Ressources complémentaires

- Architecture projet : `task.md`
- Spécification fonctionnelle : `PRD_MONO_BOUTIQUE.md`
- Scripts utilitaires : `invoke_api_tests.ps1`, `postman_collection.json`

Pour toute évolution de l’API, mettre à jour ce document ainsi que le script PowerShell et la collection Postman afin de conserver l’alignement avec le workflow de tests demandé.
