<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\HttpFoundation\JsonResponse;

class TestMercureController extends AbstractController
{
    #[Route('/test-mercure', name: 'test_mercure')]
    public function testMercure(): Response
    {
        return $this->render('auction/test_mercure.html.twig');
    }

    #[Route('/test-publish', name: 'test_publish')]
    public function publish(HubInterface $hub): JsonResponse
    {
        $update = new Update(
            'test',
            json_encode([
                'message' => 'Test message',
                'time' => (new \DateTime())->format('H:i:s')
            ])
        );

        $hub->publish($update);

        return new JsonResponse(['status' => 'Message published']);
    }
}