<?php
// src/Service/JwtProvider.php

namespace App\Service;

use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JwtProvider
{
    private string $secret;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->secret = $parameterBag->get('mercure_jwt_secret');
    }

    public function generateToken(array $topics): string
    {
        $payload = [
            'mercure' => [
                'subscribe' => $topics,
                'publish' => ['http://localhost:8000/api/auction/*'],
                'exp' => time() + 3600
            ]
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }
}