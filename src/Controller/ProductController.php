<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ProductController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private TagAwareCacheInterface $cache;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, TagAwareCacheInterface $cache)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->cache = $cache;
    }

    // Route pour récupérer tous les produits 
    
    #[Route('/api/products', name: 'get_all_products', methods: 'GET')]
    public function getAll(ProductRepository $productRepository, Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        // Identifiant de cache unique pour cette combinaison de page et limit
        $cacheKey = "getAllProducts-" . $page . "-" . $limit;

        // Vérification du cache ou récupération depuis la base de données
        $products = $this->cache->get($cacheKey, function (ItemInterface $item) use ($productRepository, $page, $limit) {
            $item->tag("productsCache");  // Utilisation des tags pour invalider le cache plus facilement
            return $productRepository->findBy([], null, $limit, ($page - 1) * $limit);
        });

        // Sérialisation des produits pour la réponse JSON
        $context = \JMS\Serializer\SerializationContext::create()->setGroups(['product']);
        $data = $this->serializer->serialize($products, 'json', $context);

        // Message pour indiquer si les données sont en cache ou non
        $cacheStatus = $products ? 'Data is in cache.' : 'Data is not in cache yet.';

        return new JsonResponse(json_encode([
            'message' => $cacheStatus,
            'data' => json_decode($data), // Convertit les données en tant qu'objet JSON
        ]), JsonResponse::HTTP_OK, [], true);
    }

    // Route pour récupérer un produit par son ID 
    #[Route('/api/products/{id}', name: 'get_product', methods: 'GET')]
    public function get(Product $product): JsonResponse
    {
        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Créez un contexte de sérialisation avec un groupe 'product'
        $context = \JMS\Serializer\SerializationContext::create()->setGroups(['product']);

        // Sérialisez le produit avec les groupes 'product'
        $data = $this->serializer->serialize($product, 'json', $context);

        // Retourner la réponse avec le produit sérialisé
        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    // Route pour créer un produit
    #[Route('/api/products', name: 'create_product', methods: 'POST')]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = $request->getContent();

        try {
            // Désérialisez les données JSON en entité Product
            $product = $this->serializer->deserialize($data, Product::class, 'json');

            // Sauvegarde du produit
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            // Sérialisez la réponse avec les groupes
            $context = \JMS\Serializer\SerializationContext::create()->setGroups(['product']);
            $data = $this->serializer->serialize($product, 'json', $context);

            return new JsonResponse($data, JsonResponse::HTTP_CREATED, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid data provided', 'message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    // Route pour mettre à jour un produit
    #[Route('/api/products/{id}', name: 'update_product', methods: 'PUT')]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $request->getContent();

        try {
            // Désérialiser les données JSON en entité Product
            $this->serializer->deserialize($data, Product::class, 'json');

            // Sauvegarde du produit mis à jour
            $this->entityManager->flush();

            // Sérialisation de la réponse avec les groupes
            $context = \JMS\Serializer\SerializationContext::create()->setGroups(['product']);
            $data = $this->serializer->serialize($product, 'json', $context);

            return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid data provided', 'message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    // Route pour supprimer un produit
    #[Route('/api/products/{id}', name: 'delete_product', methods: 'DELETE')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Product $product): JsonResponse
    {
        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Supprimer le produit de la base de données
        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Product deleted successfully'], JsonResponse::HTTP_OK);
    }
}