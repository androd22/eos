<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Rendu du template avec les données
        return $this->render('home/index.html.twig', [

        ]);
    }

//    #[Route('/api/total-raised', name: 'api_total_raised')]
//    public function getTotalRaised(): Response
//    {
//        // Génération d'un montant aléatoire pour la démonstration
//        $totalRaised = random_int(10000, 100000);
//        return $this->json(['totalRaised' => $totalRaised]);
//    }
}
