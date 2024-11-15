<?php
// src/Controller/TokenController.php

namespace App\Controller;

use App\Service\JwtProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TokenController extends AbstractController
{
    private JwtProvider $jwtProvider;

    public function __construct(JwtProvider $jwtProvider)
    {
        $this->jwtProvider = $jwtProvider;
    }

    #[Route('/api/token', name: 'api_token', methods: ['GET'])]
    public function generateToken(): JsonResponse
    {
        // Liste des topics auxquels l'utilisateur peut s'abonner
        $subscribeTopics = ['*'];

        // Générer le token JWT
        $token = $this->jwtProvider->generateToken($subscribeTopics);

        // Retourner le token dans une réponse JSON
        return new JsonResponse(['token' => $token]);
    }
}
