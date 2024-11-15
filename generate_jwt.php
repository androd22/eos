<?php
require 'vendor/autoload.php';

use Firebase\JWT\JWT;

// Remplacez par la valeur de votre MERCURE_JWT_SECRET
$secret = 'L4vi3e5tun3p0mm3!L4vi3e5tun3p0mm3!';

$payload = [
    'mercure' => [
        'publish' => ['*'], // Autorise toutes les publications
        'subscribe' => ['*'] // Autorise toutes les souscriptions
    ]
];

$jwt = JWT::encode($payload, $secret, 'HS256');
echo "Votre jeton JWT : " . $jwt;
