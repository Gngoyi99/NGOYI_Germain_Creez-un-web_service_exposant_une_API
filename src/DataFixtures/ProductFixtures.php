<?php
// src/DataFixtures/ProductFixtures.php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Créer 10 produits avec des données statiques
        for ($i = 1; $i <= 10; $i++) {
            $product = new Product();
            $product->setName("Produit $i") // Nom du produit : "Produit 1", "Produit 2", etc.
                    ->setDescription("Description du produit $i") // Description : "Description du produit 1", etc.
                    ->setPrice(10.0 * $i) // Prix : 10, 20, 30, etc.
                    ->setStock(100 - $i) // Stock : 99, 98, 97, etc.
                    ->setCreatedAt(new \DateTimeImmutable()) // Date de création
                    ->setUpdatedAt(new \DateTimeImmutable()); // Date de mise à jour
            $manager->persist($product); // Sauvegarder le produit dans le gestionnaire d'objets
        }

        // Sauvegarder tous les produits dans la base de données
        $manager->flush();
    }
}

