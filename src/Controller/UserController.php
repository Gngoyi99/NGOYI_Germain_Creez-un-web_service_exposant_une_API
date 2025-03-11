<?php 

// src/Controller/UserController.php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Doctrine\ORM\EntityManagerInterface;

class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;

    // Injection du service EntityManagerInterface et SerializerInterface
    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    // POST /users - Créer un utilisateur
    #[Route('/users', name: 'create_user', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Récupérer les données JSON de la requête
        $data = $request->getContent();

        try {
            // Désérialiser les données JSON en entité User
            $user = $this->serializer->deserialize($data, User::class, 'json');

            // Sauvegarder l'utilisateur dans la base de données
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Retourner la réponse avec l'ID de l'utilisateur créé
            return new JsonResponse([
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
            ], Response::HTTP_CREATED);
        } catch (NotNormalizableValueException $e) {
            // Si des erreurs de désérialisation se produisent, retourner un message d'erreur
            return new JsonResponse(['error' => 'Invalid data provided'], Response::HTTP_BAD_REQUEST);
        }
    }

    // DELETE /users/{id} - Supprimer un utilisateur
    #[Route('/users/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function delete(User $user): JsonResponse
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    // GET /users/{id} - Consulter un utilisateur
    #[Route('/users/{id}', name: 'get_user', methods: ['GET'])]
    public function get(User $user): JsonResponse
    {
        return new JsonResponse([
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
        ]);
    }
}
