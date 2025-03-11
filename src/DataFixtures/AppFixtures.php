<?php
// src/DataFixtures/AppFixtures.php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Client;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création de clients fictifs pour associer à des utilisateurs
        $client1 = new Client();
        $client1->setName('Client 1');
        $client1->setEmail('client1@example.com'); // Email valide
        $client1->setCreatedAt(new \DateTimeImmutable());
        $client1->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($client1);

        $client2 = new Client();
        $client2->setName('Client 2');
        $client2->setEmail('client2@example.com'); // Email valide
        $client2->setCreatedAt(new \DateTimeImmutable());
        $client2->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($client2);

        // Création des utilisateurs
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setFirstname("Prénom " . $i);
            $user->setLastname("Nom " . $i);
            $user->setEmail("user" . $i . "@example.com"); // Email valide
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->userPasswordHasher->hashPassword($user, 'password'));
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());
            // Associer un client aléatoire à chaque utilisateur
            $user->setClient(rand(0, 1) == 0 ? $client1 : $client2); // Choisit un client au hasard
            $manager->persist($user);
        }

        // Création d'un utilisateur admin
        $admin = new User();
        $admin->setFirstname('Admin');
        $admin->setLastname('Admin');
        $admin->setEmail('admin@example.com'); // Email valide
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->userPasswordHasher->hashPassword($admin, 'password'));
        $admin->setCreatedAt(new \DateTimeImmutable());
        $admin->setUpdatedAt(new \DateTimeImmutable());
        $admin->setClient($client1); // Assignation d'un client à l'admin
        $manager->persist($admin);

        // Sauvegarder dans la base de données
        $manager->flush();
    }
}
