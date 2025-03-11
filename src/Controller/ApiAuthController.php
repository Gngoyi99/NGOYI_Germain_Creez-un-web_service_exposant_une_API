<?php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class ApiAuthController extends AbstractController
{
    private JWTManager $jwtManager;

    // Injection du gestionnaire JWT
    public function __construct(JWTManager $jwtManager)
    {
        $this->jwtManager = $jwtManager;
    }

    #[Route('/api/login_check', name: 'app_login_check', methods: 'POST')]
    public function loginCheck(Request $request): JsonResponse
    {
        // Récupérer l'utilisateur authentifié via JWT
        $user = $this->getUser();

        // Si l'utilisateur n'est pas authentifié, lancer une exception
        if (!$user) {
            throw new AuthenticationException('Invalid credentials');
        }

        // Générer le token JWT pour l'utilisateur authentifié
        $token = $this->jwtManager->create($user);

        // Retourner une réponse JSON avec le token JWT
        return new JsonResponse(['token' => $token]);
    }
}
