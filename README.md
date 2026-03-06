# SamySam — E-commerce Symfony 8

Application e-commerce de démonstration construite avec **Symfony 8.0**, **Doctrine ORM**, **EasyAdmin** et **Stripe Checkout**.  
Ce projet sert de base pédagogique pour comprendre l'architecture MVC d'une boutique en ligne.

---

## Table des matières

1. [Stack technique](#stack-technique)
2. [Installation](#installation)
3. [Architecture du projet](#architecture-du-projet)
4. [Fonctionnalités](#fonctionnalités)
    - [Catalogue produits](#1--catalogue-produits)
    - [Panier (session)](#2--panier-session)
    - [Commande & paiement Stripe](#3--commande--paiement-stripe)
    - [Historique des commandes](#4--historique-des-commandes)
    - [Webhook Stripe](#5--webhook-stripe)
    - [Authentification](#6--authentification)
    - [Administration EasyAdmin](#7--administration-easyadmin)
    - [Fixtures de données](#8--fixtures-de-données)
    - [Emails (contact, bienvenue, commande)](#9--emails-contact-bienvenue-commande)
5. [Modèle de données](#modèle-de-données)
6. [Routes principales](#routes-principales)
7. [Configuration](#configuration)

---

## Stack technique

| Composant      | Version / Détail           |
| -------------- | -------------------------- |
| PHP            | >= 8.4                     |
| Symfony        | 8.0.\*                     |
| Doctrine ORM   | 3.x (MySQL 8.0)            |
| EasyAdmin      | 4.x                        |
| Stripe PHP SDK | stripe/stripe-php          |
| Frontend       | Twig + Bootstrap 5.3 (CDN) |
| Stimulus       | @hotwired/stimulus (UX)    |

---

## Installation

```bash
# 1. Cloner le dépôt
git clone <url-du-repo> samySam
cd samySam

# 2. Installer les dépendances PHP
composer install

# 3. Créer le fichier d'environnement local
cp .env.example .env.local
# → Remplir les valeurs : DATABASE_URL, STRIPE_SECRET_KEY, etc.

# 4. Créer la base de données et exécuter les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Charger les données de démonstration (fixtures)
php bin/console doctrine:fixtures:load

# 6. Lancer le serveur de développement
symfony serve
# ou
php -S localhost:8000 -t public/
```

> **Compte admin par défaut** (créé par les fixtures) :  
> Email : `admin@shop.fr` — Mot de passe : `admin123`

---

## Architecture du projet

```
samySam/
├── src/
│   ├── Controller/           # Contrôleurs (logique HTTP)
│   │   ├── HomeController        → Redirection vers la boutique
│   │   ├── ShopController        → Catalogue & fiche produit
│   │   ├── CartController        → Gestion du panier
│   │   ├── OrderController       → Checkout, succès, annulation, historique
│   │   ├── SecurityController    → Login, register, logout
│   │   ├── MailController         → Formulaire de contact
│   │   ├── StripeWebhookController → Réception des webhooks Stripe
│   │   └── Admin/                → CRUD EasyAdmin
│   │       ├── DashboardController
│   │       ├── ProductCrudController
│   │       ├── CategoryCrudController
│   │       └── UserCrudController
│   │
│   ├── Entity/               # Entités Doctrine (modèle de données)
│   │   ├── Product               → Produit (nom, prix, stock, catégorie)
│   │   ├── Category              → Catégorie de produits
│   │   ├── Order                 → Commande (référence, total, statut, Stripe, user)
│   │   ├── OrderItem             → Ligne de commande (snapshot prix/nom)
│   │   └── User                  → Utilisateur (email, rôles, mot de passe, orders)
│   │
│   ├── Model/                # Objets non-persistés
│   │   └── CartItem              → Élément du panier (stocké en session)
│   │
│   ├── Form/                 # Formulaires Symfony
│   │   └── ContactType           → Formulaire de contact (nom, email, message)
│   │
│   ├── Repository/           # Requêtes Doctrine personnalisées
│   │   ├── ProductRepository     → findAvailable(), findAvailableByCategory()
│   │   ├── CategoryRepository    → (héritage ServiceEntityRepository)
│   │   ├── OrderRepository       → (héritage ServiceEntityRepository)
│   │   └── UserRepository        → upgradePassword()
│   │
│   ├── Service/              # Logique métier
│   │   ├── ProductService        → Récupération produits & catégories
│   │   ├── CartService           → Gestion du panier en session
│   │   ├── OrderService          → Création commande, gestion stock, statuts, historique
│   │   ├── StripeService         → Création de session Stripe Checkout
│   │   └── MailerService         → Envoi d'emails (contact, bienvenue, commande)
│   │
│   └── DataFixtures/         # Données initiales
│       └── AppFixtures           → Admin + catégories + produits de démo
│
├── templates/                # Vues Twig
│   ├── base.html.twig            → Layout principal (navbar, flash messages)
│   ├── shop/
│   │   ├── index.html.twig       → Catalogue avec filtre par catégorie
│   │   └── product.html.twig     → Fiche détail d'un produit
│   ├── cart/
│   │   └── index.html.twig       → Panier avec tableau récapitulatif
│   ├── order/
│   │   ├── index.html.twig       → Historique des commandes de l'utilisateur
│   │   ├── success.html.twig     → Page de confirmation de paiement
│   │   └── cancel.html.twig      → Page d'annulation de commande
│   ├── security/
│   │   ├── login.html.twig       → Formulaire de connexion
│   │   └── register.html.twig    → Formulaire d'inscription
│   ├── contact/
│   │   └── index.html.twig       → Formulaire de contact
│   ├── mail/
│   │   ├── base.html.twig        → Layout commun des emails
│   │   ├── contact.html.twig     → Email de contact
│   │   ├── welcome.html.twig     → Email de bienvenue
│   │   └── order_confirmation.html.twig → Récap commande
│   └── admin/
│       └── dashboard.html.twig   → Tableau de bord admin
│
├── config/                   # Configuration Symfony
│   ├── services.yaml             → Autowiring, paramètres Stripe
│   ├── routes.yaml               → Routes principales
│   ├── packages/
│   │   ├── security.yaml         → Firewalls, accès par rôle (ROLE_ADMIN, ROLE_USER)
│   │   ├── doctrine.yaml         → Configuration ORM
│   │   ├── stripe.yaml           → Service Stripe\StripeClient
│   │   └── ...                   → Autres packages Symfony
│   └── routes/
│       ├── easyadmin.yaml        → Routes admin
│       └── security.yaml         → Routes login/logout
│
├── migrations/               # Migrations Doctrine (schéma DB)
├── public/index.php          # Point d'entrée web
├── .env                      # Variables d'environnement (valeurs par défaut)
└── .env.example              # Template pour .env.local
```

---

## Fonctionnalités

### 1 — Catalogue produits

> **Quoi** : Affichage et filtrage des produits disponibles en stock.

| Fichier                  | Rôle                                                                                                          |
| ------------------------ | ------------------------------------------------------------------------------------------------------------- |
| `ShopController`         | Reçoit la requête HTTP, délègue au service, rend la vue                                                       |
| `ProductService`         | Couche métier : `getAvailableProducts()`, `getProductsByCategory()`, `getAllCategories()`, `getProductById()` |
| `ProductRepository`      | Requêtes DQL : `findAvailable()` (stock > 0 + JOIN catégorie), `findAvailableByCategory()`                    |
| `Product` (Entity)       | Champs : `name`, `description`, `price` (DECIMAL 10,2), `stock`, `category` (ManyToOne)                       |
| `Category` (Entity)      | Champs : `name`, `products` (OneToMany)                                                                       |
| `shop/index.html.twig`   | Grille de produits Bootstrap + sidebar catégories                                                             |
| `shop/product.html.twig` | Fiche détail produit avec bouton "Ajouter au panier"                                                          |

**Comment ça marche** :

1. `ShopController::index()` lit le paramètre `?category=` dans l'URL
2. Appelle `ProductService` qui interroge `ProductRepository` via des requêtes DQL optimisées (JOIN sur la catégorie pour éviter le N+1)
3. Rend la vue Twig avec la liste des produits et des catégories

**Méthodes clés** :

- `ProductRepository::findAvailable()` → DQL avec `WHERE stock > 0`, `JOIN category`, `ORDER BY name`
- `Product::getPriceInCents()` → Convertit le prix DECIMAL en centimes (int) pour Stripe

---

### 2 — Panier (session)

> **Quoi** : Gestion d'un panier d'achat stocké en session HTTP (pas en base de données).

| Fichier                | Rôle                                                                                             |
| ---------------------- | ------------------------------------------------------------------------------------------------ |
| `CartController`       | Actions : `index`, `add/{id}`, `remove/{id}`, `clear`                                            |
| `CartService`          | Logique panier : `addItem()`, `removeItem()`, `clear()`, `getCart()`, `getTotal()`, `getCount()` |
| `CartItem` (Model)     | Objet non-persisté : `productId`, `productName`, `price` (centimes), `quantity`                  |
| `cart/index.html.twig` | Tableau récapitulatif avec prix, quantités, actions et bouton "Payer"                            |

**Comment ça marche** :

1. L'utilisateur clique "Ajouter au panier" → POST vers `CartController::add()`
2. `CartService::addItem()` récupère le produit en base, vérifie le stock, crée/incrémente un `CartItem` en session
3. Le panier est sérialisé dans la session Symfony via `RequestStack::getSession()`
4. Les prix sont toujours en **centimes** (int) pour éviter les erreurs d'arrondi

**Pourquoi en session** : Simplicité. Pas besoin de table panier en base pour un projet de démonstration. Le panier est vidé à la déconnexion ou expiration de session.

**Méthodes clés** :

- `CartService::addItem(int $productId)` → Vérifie stock > 0, ajoute ou incrémente
- `CartService::getTotal()` → Somme de `CartItem::getTotal()` (price × quantity)
- `CartService::save(array $cart)` → Persiste le tableau en session

---

### 3 — Commande & paiement Stripe

> **Quoi** : Création d'une commande en base, paiement via Stripe Checkout, gestion du succès/annulation.

| Fichier              | Rôle                                                                                                                          |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| `OrderController`    | `index()` (mes commandes), `checkout()` (POST), `success/{ref}`, `cancel/{ref}`                                               |
| `OrderService`       | `createOrder()`, `getOrdersForUser()`, `saveStripeSession()`, `markAsPaid()`, `markAsCancelled()`, `findByReference()`        |
| `StripeService`      | `createCheckoutSession()` → Appel API Stripe                                                                                  |
| `Order` (Entity)     | `reference` (auto-généré), `totalAmount` (centimes), `status` (pending/paid/cancelled), `stripeSessionId`, `user` (ManyToOne) |
| `OrderItem` (Entity) | Snapshot : `productName`, `unitPriceCents`, `quantity`, `totalCents` + relations `order`, `product`                           |

**Comment ça marche (flux checkout)** :

1. **POST `/order/checkout`** → Vérification CSRF, récupération du panier et de l'utilisateur connecté
2. **`OrderService::createOrder(User $user, int $total, array $cartItems)`** :
    - Démarre une **transaction SQL**
    - Crée l'entité `Order` avec référence unique (`ORD-XXXXXXXXXX`) et l'associe à l'utilisateur connecté
    - Pour chaque `CartItem` : récupère le `Product` avec **verrou pessimiste** (`PESSIMISTIC_WRITE`), vérifie le stock, le décrémente, crée un `OrderItem` (snapshot du nom et prix)
    - Commit ou rollback en cas d'erreur
    - **Envoi du récapitulatif par email** via `MailerService::sendOrderConfirmationEmail()`
3. **`StripeService::createCheckoutSession()`** :
    - Configure `Stripe::setApiKey()`
    - Construit les `line_items` (prix en centimes, nom, quantité)
    - Crée la session avec `success_url`, `cancel_url` et `metadata.order_reference`
4. **Redirection** vers la page de paiement hébergée par Stripe
5. **Retour** :
    - **Succès** (`/order/success/{ref}`) → Vérifie que la commande appartient à l'utilisateur, `markAsPaid()`, vide le panier
    - **Annulation** (`/order/cancel/{ref}`) → Vérifie la propriété, `markAsCancelled()`, restaure le stock

**Pourquoi un snapshot dans OrderItem** : Si le prix ou le nom du produit change après la commande, l'historique reste cohérent.

**Pourquoi lier User ↔ Order** : Permet à chaque utilisateur de consulter uniquement ses propres commandes et empêche l'accès aux commandes d'autrui (vérification de propriété sur success/cancel).

**Pourquoi un verrou pessimiste** : Évite l'oversell en cas de requêtes concurrentes sur le même produit.

**Méthodes clés** :

- `OrderService::createOrder(User $user, int $total, array $cartItems)` → Transaction + verrou + décrémentation stock + lien utilisateur
- `OrderService::getOrdersForUser(User $user)` → Commandes de l'utilisateur triées par date décroissante
- `OrderService::markAsCancelled(Order $order)` → Restauration du stock dans une transaction
- `StripeService::createCheckoutSession(array, string, string, string)` → Appel Stripe API

---

### 4 — Historique des commandes

> **Quoi** : Page permettant à chaque utilisateur de consulter l'historique de ses commandes (référence, date, articles, total, statut).

| Fichier                 | Rôle                                                                                  |
| ----------------------- | ------------------------------------------------------------------------------------- |
| `OrderController`       | `index()` → Récupère les commandes de l'utilisateur connecté via le service           |
| `OrderService`          | `getOrdersForUser(User $user)` → `findBy(['user' => $user], ['createdAt' => 'DESC'])` |
| `order/index.html.twig` | Tableau listant les commandes avec badge de statut (payée/annulée/en attente)         |

**Comment ça marche** :

1. L'utilisateur clique sur "Mes commandes" dans la navbar
2. `OrderController::index()` récupère l'utilisateur connecté via `$this->getUser()`
3. `OrderService::getOrdersForUser()` interroge le repository avec filtre sur `user` et tri par `createdAt DESC`
4. Le template affiche un tableau avec les détails de chaque commande

**Sécurité** : Chaque utilisateur ne voit que ses propres commandes. Les routes `success` et `cancel` vérifient également que la commande appartient bien à l'utilisateur connecté (`createAccessDeniedException` sinon).

---

### 5 — Webhook Stripe

> **Quoi** : Endpoint recevant les notifications de Stripe pour confirmer/annuler les paiements côté serveur.

| Fichier                   | Rôle                                                 |
| ------------------------- | ---------------------------------------------------- |
| `StripeWebhookController` | Route POST `/stripe/webhook`, invocable (`__invoke`) |
| `OrderService`            | `markAsPaid()`, `markAsCancelled()`                  |

**Comment ça marche** :

1. Stripe envoie une requête POST signée à `/stripe/webhook`
2. Le contrôleur vérifie la **signature** via `Stripe\Webhook::constructEvent()` (protection contre les requêtes frauduleuses)
3. Selon le type d'événement :
    - `checkout.session.completed` → `markAsPaid()` (idempotent)
    - `checkout.session.expired` / `payment_intent.payment_failed` → `markAsCancelled()` (restauration stock)
    - Autres → ignorés

**Pourquoi** : Le `success_url` peut ne pas être atteint (utilisateur ferme le navigateur). Le webhook garantit la cohérence des statuts.

**Sécurité** : Vérification de signature + `STRIPE_WEBHOOK_SECRET` en variable d'environnement.

---

### 6 — Authentification

> **Quoi** : Inscription, connexion et déconnexion des utilisateurs.

| Fichier                       | Rôle                                                                                                       |
| ----------------------------- | ---------------------------------------------------------------------------------------------------------- |
| `SecurityController`          | `login()`, `register()`, `logout()`                                                                        |
| `User` (Entity)               | `email` (unique), `roles` (JSON), `password` (hashé), `plainPassword` (non-persisté), `orders` (OneToMany) |
| `UserRepository`              | `upgradePassword()` (rehash automatique)                                                                   |
| `security.yaml`               | Firewalls, form_login, access_control                                                                      |
| `security/login.html.twig`    | Formulaire de connexion                                                                                    |
| `security/register.html.twig` | Formulaire d'inscription avec validation                                                                   |

**Contrôle d'accès** (défini dans `security.yaml`) :

| Route                 | Rôle requis     |
| --------------------- | --------------- |
| `/admin/*`            | `ROLE_ADMIN`    |
| `/cart/*`             | `ROLE_USER`     |
| `/order/*`            | `ROLE_USER`     |
| `/login`, `/register` | `PUBLIC_ACCESS` |
| `/` (boutique)        | `PUBLIC_ACCESS` |

**Comment ça marche** :

1. **Inscription** : Validation manuelle (CSRF, longueur mot de passe, confirmation, unicité email) + hashage `UserPasswordHasherInterface` + persist + **envoi email de bienvenue** via `MailerService`
2. **Connexion** : Gérée par le composant Security de Symfony (`form_login`)
3. **Déconnexion** : Route interceptée par Symfony, méthode vide

**Sécurité** :

- Mots de passe hashés avec l'algorithme `auto` (bcrypt/argon2)
- Protection CSRF sur tous les formulaires
- Sérialisation sécurisée (`__serialize` avec CRC32C du hash)

---

### 7 — Administration EasyAdmin

> **Quoi** : Interface d'administration CRUD pour gérer les produits, catégories et utilisateurs.

| Fichier                     | Rôle                                                                                         |
| --------------------------- | -------------------------------------------------------------------------------------------- |
| `DashboardController`       | Page d'accueil admin, menu de navigation                                                     |
| `ProductCrudController`     | CRUD produit : nom, description, prix (MoneyField €), stock, catégorie (AssociationField)    |
| `CategoryCrudController`    | CRUD catégorie : id, nom                                                                     |
| `UserCrudController`        | CRUD utilisateur : email, mot de passe (hashé à la volée via `persistEntity`/`updateEntity`) |
| `admin/dashboard.html.twig` | Template du tableau de bord                                                                  |

**Comment le hashage fonctionne pour les utilisateurs** :

- `UserCrudController::persistEntity()` et `updateEntity()` vérifient si `plainPassword` est rempli
- Si oui → hashage via `UserPasswordHasherInterface`, puis `setPassword()` et `setPlainPassword(null)`

**Accès** : Protégé par `access_control` → `ROLE_ADMIN` requis pour `/admin/*`

---

### 8 — Fixtures de données

> **Quoi** : Jeu de données initial pour démarrer le projet rapidement.

| Fichier       | Rôle                                    |
| ------------- | --------------------------------------- |
| `AppFixtures` | Crée un admin, 2 catégories, 3 produits |

**Données créées** :

- **Admin** : `admin@shop.fr` / `admin123` (`ROLE_ADMIN`)
- **Catégories** : Informatique, Mobilier
- **Produits** : Clavier mécanique (89,99€), Souris sans fil (45€), Chaise de bureau (320€)

```bash
php bin/console doctrine:fixtures:load
```

---

### 9 — Emails (contact, bienvenue, commande)

> **Quoi** : Envoi d'emails transactionnels via le composant Mailer de Symfony : formulaire de contact, bienvenue à l'inscription, récapitulatif de commande.

| Fichier                              | Rôle                                                                          |
| ------------------------------------ | ----------------------------------------------------------------------------- |
| `MailController`                     | `contact()` — affiche et traite le formulaire de contact                      |
| `MailerService`                      | `sendContactEmail()`, `sendWelcomeEmail()`, `sendOrderConfirmationEmail()`    |
| `ContactType` (Form)                 | Formulaire : nom, email, message (avec contraintes de validation)             |
| `mail/base.html.twig`                | Layout HTML commun à tous les emails (header Shop, footer)                    |
| `mail/contact.html.twig`             | Template email de contact (nom, email, message)                               |
| `mail/welcome.html.twig`             | Template email de bienvenue (confirmation d'inscription)                      |
| `mail/order_confirmation.html.twig`  | Template récapitulatif de commande (tableau articles, total, statut)           |
| `contact/index.html.twig`            | Page du formulaire de contact                                                 |

**Déclencheurs** :

1. **Contact** : L'utilisateur soumet le formulaire → `MailerService::sendContactEmail()` envoie à `admin@shop.fr`
2. **Inscription** : Après `persist` + `flush` du User → `MailerService::sendWelcomeEmail()` envoie à l'adresse de l'utilisateur
3. **Commande** : Après `createOrder()` dans le checkout → `MailerService::sendOrderConfirmationEmail()` envoie le récap (articles, prix, total) à l'utilisateur

**Configuration** : Variable `MAILER_DSN` dans `.env.local` (ex: `smtp://localhost:1025` pour Mailpit/Mailhog en dev).

---

## Modèle de données

```
┌──────────────┐       ┌──────────────┐
│   Category   │       │     User     │
├──────────────┤       ├──────────────┤
│ id           │       │ id           │
│ name         │       │ email        │
│              │       │ roles []     │
│ products ◄───┤       │ password     │
└──────┬───────┘       │ orders ◄─────┤
       │ 1:N           └──────┬───────┘
┌──────▼───────┐              │ 1:N
│   Product    │       ┌──────▼───────┐
├──────────────┤       │    Order     │
│ id           │       ├──────────────┤
│ name         │       │ id           │
│ description  │       │ reference    │
│ price        │ (€)   │ totalAmount  │  (centimes)
│ stock        │       │ status       │  (pending/paid/cancelled)
│ category     │       │ stripeSessionId │
│              │       │ user         │  (ManyToOne → User)
└──────┬───────┘       │ createdAt    │
       │               │ orderItems ◄─┤
       │               └──────┬───────┘
       │                      │ 1:N
       │ N:1           ┌──────▼───────┐
       │               │  OrderItem   │
       │               ├──────────────┤
       │               │ id           │
       └───────────────► product      │  (ManyToOne → Product)
                       │ order        │  (ManyToOne → Order)
                       │ productName  │  (snapshot)
                       │ unitPriceCents│ (snapshot)
                       │ quantity     │
                       │ totalCents   │
                       │ createdAt    │
                       └──────────────┘
```

> **CartItem** (Model, non-persisté) : `productId`, `productName`, `price` (centimes), `quantity` — stocké en session PHP.

---

## Routes principales

| Méthode  | Route                  | Nom                  | Contrôleur                     | Accès       |
| -------- | ---------------------- | -------------------- | ------------------------------ | ----------- |
| GET      | `/`                    | `app_home`           | `HomeController::index`        | Public      |
| GET      | `/shop`                | `app_shop_index`     | `ShopController::index`        | Public      |
| GET      | `/shop/product/{id}`   | `app_shop_product`   | `ShopController::show`         | Public      |
| GET      | `/cart`                | `app_cart_index`     | `CartController::index`        | ROLE_USER   |
| POST     | `/cart/add/{id}`       | `app_cart_add`       | `CartController::add`          | ROLE_USER   |
| POST     | `/cart/remove/{id}`    | `app_cart_remove`    | `CartController::remove`       | ROLE_USER   |
| POST     | `/cart/clear`          | `app_cart_clear`     | `CartController::clear`        | ROLE_USER   |
| POST     | `/order/checkout`      | `app_order_checkout` | `OrderController::checkout`    | ROLE_USER   |
| GET      | `/order`               | `app_order_index`    | `OrderController::index`       | ROLE_USER   |
| GET      | `/order/success/{ref}` | `app_order_success`  | `OrderController::success`     | ROLE_USER   |
| GET      | `/order/cancel/{ref}`  | `app_order_cancel`   | `OrderController::cancel`      | ROLE_USER   |
| POST     | `/stripe/webhook`      | `app_stripe_webhook` | `StripeWebhookController`      | Public (\*) |
| GET      | `/login`               | `app_login`          | `SecurityController::login`    | Public      |
| GET/POST | `/register`            | `app_register`       | `SecurityController::register` | Public      |
| GET      | `/logout`              | `app_logout`         | `SecurityController::logout`   | Authentifié |
| GET/POST | `/contact`             | `app_contact`        | `MailController::contact`      | Public      |
| GET      | `/admin`               | `admin`              | `DashboardController::index`   | ROLE_ADMIN  |

> (\*) Le webhook Stripe est public mais protégé par vérification de signature.

---

## Configuration

### Variables d'environnement (`.env` / `.env.local`)

| Variable                  | Description                              |
| ------------------------- | ---------------------------------------- |
| `APP_SECRET`              | Clé secrète Symfony (CSRF, sessions)     |
| `DATABASE_URL`            | Connexion MySQL                          |
| `STRIPE_PUBLIC_KEY`       | Clé publique Stripe (pk*test*...)        |
| `STRIPE_SECRET_KEY`       | Clé secrète Stripe (sk*test*...)         |
| `STRIPE_WEBHOOK_SECRET`   | Secret webhook Stripe (whsec\_...)       |
| `MAILER_DSN`              | Transport email (null://null par défaut) |
| `MESSENGER_TRANSPORT_DSN` | Transport Messenger (doctrine)           |

### Injection du service Stripe

Définie dans `config/services.yaml` :

```yaml
parameters:
    stripe_secret_key: "%env(STRIPE_SECRET_KEY)%"

services:
    _defaults:
        bind:
            string $stripeSecretKey: "%stripe_secret_key%"
```

Et dans `config/packages/stripe.yaml` :

```yaml
services:
    stripe.client:
        class: 'Stripe\StripeClient'
        arguments:
            - "%env(STRIPE_SECRET_KEY)%"
    Stripe\StripeClient: "@stripe.client"
```
