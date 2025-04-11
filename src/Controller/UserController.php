<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Client;
use App\Repository\UserRepository;
use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;

class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private UserPasswordHasherInterface $passwordHasher;
    private TagAwareCacheInterface $cache;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordHasher,
        TagAwareCacheInterface $cache
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->passwordHasher = $passwordHasher;
        $this->cache = $cache;
    }

    //Route pour obtenir un utilisateur spécifique avec cache et sérialisation
    #[Route('/api/users/{id}', name: 'get_user', methods: 'GET')]
    public function get(User $user, int $id): JsonResponse
    {
        // Créer une clé unique pour le cache
        $cacheKey = "getUser-" . $id;

        // Récupérer l'utilisateur du cache ou de la base de données
        $cachedUser = $this->cache->get($cacheKey, function (ItemInterface $item) use ($user) {
            $item->tag('usersCache');
            return $user;
        });

        // Sérialisation de l'utilisateur
        $context = SerializationContext::create()->setGroups(['user']);
        $data = $this->serializer->serialize($cachedUser, 'json', $context);

        // Message pour savoir si les données sont dans le cache ou non
        $cacheStatus = $cachedUser ? 'Data is in cache.' : 'Data is not in cache yet.';

        return new JsonResponse(
            json_encode([
                'message' => $cacheStatus,
                'data' => json_decode($data)  // Assurez-vous que le résultat est bien un objet JSON
            ]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    //Route pour obtenir tous les utilisateurs du client avec cache et pagination
    #[Route('/api/users', name: 'get_all_users', methods: 'GET')]
    public function getAll(UserRepository $userRepository, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();  // Récupérer l'utilisateur authentifié

        // Vérification si l'utilisateur est authentifié
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_FORBIDDEN);
        }

        // Récupérer le client de l'utilisateur authentifié
        $client = $currentUser->getClient();

        // Vérification si l'utilisateur est associé à un client
        if (!$client) {
            return new JsonResponse(['error' => 'User is not associated with a client'], Response::HTTP_FORBIDDEN);
        }

        // Pagination : 5 utilisateurs par page
        $page = $request->query->get('page', 1);  // Paramètre de page (par défaut 1)
        $limit = $request->query->get('limit', 5);  // Paramètre de limite (par défaut 5)

        // Créer une clé unique pour le cache
        $cacheKey = "getAllUsers-" . $client->getId() . "-" . $page . "-" . $limit;

        // Récupérer les utilisateurs du cache ou de la base de données
        $users = $this->cache->get($cacheKey, function (ItemInterface $item) use ($userRepository, $client, $page, $limit) {
            $item->tag('usersCache');
            return $userRepository->findBy(['client' => $client], null, $limit, ($page - 1) * $limit);
        });

        // Vérification si des utilisateurs ont été trouvés
        if (empty($users)) {
            return new JsonResponse(['error' => 'No users found for this client'], Response::HTTP_NOT_FOUND);
        }

        // Sérialisation des utilisateurs en JSON
        $context = SerializationContext::create()->setGroups(['user', 'client']);
        $data = $this->serializer->serialize($users, 'json', $context);

        // Message pour savoir si les données sont dans le cache ou non
        $cacheStatus = $users ? 'Data is in cache.' : 'Data is not in cache yet.';

        return new JsonResponse(
            json_encode([
                'message' => $cacheStatus,
                'data' => json_decode($data)  // Assurez-vous que le résultat est bien un objet JSON
            ]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    //Route pour créer un nouvel utilisateur
    #[Route('/api/users', name: 'create_user', methods: 'POST')]
    #[IsGranted('ROLE_ADMIN')]
    #[IsGranted('ROLE_CLIENT')]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->denyAccessUnlessGranted('ROLE_CLIENT');

        $data = $request->getContent();

        try {
            // Désérialisez les données JSON en entité User
            $context = DeserializationContext::create()->setGroups(['user']);
            $user = $this->serializer->deserialize($data, User::class, 'json', $context);

            // Récupérer l'ID du client depuis les données JSON
            $dataArray = json_decode($data, true); // Convertir en tableau associatif
            $clientId = $dataArray['client_id'] ?? null;

            if ($clientId === null) {
                return new JsonResponse(['error' => 'Client ID is required'], Response::HTTP_BAD_REQUEST);
            }

            // Récupérer le client à partir de l'ID
            $client = $this->entityManager->getRepository(Client::class)->find($clientId);

            if (!$client) {
                return new JsonResponse(['error' => 'Client not found'], Response::HTTP_NOT_FOUND);
            }

            // Affecter le client à l'utilisateur
            $user->setClient($client);

            // Vérification du mot de passe
            if (empty($user->getPassword())) {
                return new JsonResponse(['error' => 'Password is required'], Response::HTTP_BAD_REQUEST);
            }

            // Hachage du mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            // Sauvegarder l'utilisateur dans la base de données
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return new JsonResponse([
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid data provided', 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    //Route pour supprimer un utilisateur
    #[Route('/api/users/{id}', name: 'delete_user', methods: 'DELETE')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'User deleted successfully'], Response::HTTP_OK);
    }

    //Route pour mettre à jour un utilisateur
    #[Route('/api/users/{id}', name: 'update_user', methods: 'PUT')]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->getContent();

        try {
            // Désérialiser les données JSON en entité User
            $context = DeserializationContext::create()->setGroups(['user']);
            $this->serializer->deserialize($data, User::class, 'json', $context);

            // Sauvegarde du produit mis à jour
            $this->entityManager->flush();

            return new JsonResponse([
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid data provided', 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}