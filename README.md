# BileMo - API REST de Gestion des Produits

## Description

BileMo est une plateforme de gestion des produits où les administrateurs peuvent gérer une base de données de produits. Ce projet fournit une API REST construite avec Symfony pour la gestion des produits. L'API inclut des fonctionnalités telles que la récupération, la création, la mise à jour, la suppression de produits, ainsi que la mise en cache des données pour une meilleure performance.

## Fonctionnalités

- **Gestion des produits** : Création, lecture, mise à jour et suppression des produits.
- **Mise en cache** : Utilisation de la mise en cache pour améliorer les performances des requêtes, avec un cache basé sur le système de fichiers.
- **HATEOAS** : Implémentation du principe HATEOAS pour ajouter des liens dans les réponses JSON afin de permettre une navigation dynamique dans l'API.
- **Sécurité** : Utilisation de rôles pour l'accès aux différentes routes de l'API.

## Prérequis

Avant de commencer, assurez-vous d'avoir les outils suivants installés :

- PHP >= 8.0
- Symfony CLI (optionnel, mais recommandé)
- Composer
- Redis ou un cache compatible si vous souhaitez utiliser des caches plus avancés

## Installation

### 1. Clonez le repository

```bash
git clone https://github.com/ton-organisation/bilemo-api.git
cd bilemo-api
```

### 2. Installez les dépendances

Utilisez Composer pour installer les dépendances du projet :

```bash
composer install
```

### 3. Configuration de l'environnement

Copiez le fichier `.env` pour configurer votre environnement local :

```bash
cp .env.example .env
```

### 4. Configuration du cache

Par défaut, l'application utilise le **cache filesystem**. Si vous souhaitez utiliser Redis ou un autre système de cache, modifiez la configuration dans `config/packages/framework.yaml`.

### 5. Créez la base de données

Si vous n'avez pas encore de base de données, vous pouvez la créer avec la commande suivante :

```bash
php bin/console doctrine:database:create
```

Puis, exécutez les migrations pour générer les tables :

```bash
php bin/console doctrine:migrations:migrate
```

### 6. Démarrez le serveur Symfony

Lancez le serveur local Symfony pour tester l'API :

```bash
symfony server:start
```

## Utilisation de l'API

### 1. Récupérer tous les produits

```bash
GET /api/products
```

Retourne tous les produits de la base de données. Les données peuvent être mises en cache pour améliorer la performance.

### 2. Récupérer un produit par son ID

```bash
GET /api/products/{id}
```

Retourne les détails d'un produit spécifique. Ce produit est mis en cache pour améliorer la vitesse de récupération.

### 3. Créer un produit

```bash
POST /api/products
```

Permet de créer un produit. Les données du produit doivent être envoyées au format JSON, par exemple :

```json
{
  "name": "Produit Example",
  "description": "Description du produit",
  "price": 100,
  "stock": 50
}
```

### 4. Mettre à jour un produit

```bash
PUT /api/products/{id}
```

Met à jour un produit existant. Vous devez fournir les nouvelles informations du produit au format JSON.

### 5. Supprimer un produit

```bash
DELETE /api/products/{id}
```

Supprime un produit de la base de données.

## Sécurité

L'API utilise des rôles basés sur Symfony pour sécuriser l'accès aux routes :

- **ROLE_ADMIN** : Les administrateurs peuvent créer, mettre à jour et supprimer des produits.
- **ROLE_USER** : Les utilisateurs authentifiés peuvent seulement consulter les produits.
- **ROLE_CLIENT** : Les clients authentifiés peuvent créer, mettre à jour et supprimer des utilisateurs ainsi que consulter les produits.

## Mise en cache

La mise en cache est activée pour les routes qui retournent des listes de produits, telles que `/api/products`. Si les données ne sont pas en cache, elles sont récupérées de la base de données et mises en cache pour une durée de 1 heure.

## Exemple de Réponse

### Récupérer un produit

```json
{
    "name": "Produit 1",
    "description": "Description du produit 1",
    "price": 10.0,
    "stock": 99,
    "_links": {
        "self": {
            "href": "/api/products/1"
        },
        "delete": {
            "href": "/api/products/1"
        },
        "update": {
            "href": "/api/products/1"
        }
    }
}
```

### Récupérer tous les produits

```json
[
    {
        "name": "Produit 1",
        "description": "Description du produit 1",
        "price": 10.0,
        "stock": 99,
        "_links": {
            "self": {
                "href": "/api/products/1"
            },
            "delete": {
                "href": "/api/products/1"
            },
            "update": {
                "href": "/api/products/1"
            }
        }
    },
    {
        "name": "Produit 2",
        "description": "Description du produit 2",
        "price": 20.0,
        "stock": 98,
        "_links": {
            "self": {
                "href": "/api/products/2"
            },
            "delete": {
                "href": "/api/products/2"
            },
            "update": {
                "href": "/api/products/2"
            }
        }
    }
]
```
