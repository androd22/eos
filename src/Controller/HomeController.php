<?php
namespace App\Controller;

use App\DTO\ContactDTO;
use App\Form\ContactType;
use App\Repository\AuctionRepository;
use App\Repository\BidRepository;
use App\Repository\CelebrityRepository;
use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private EmailService $emailService
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(Request $request, BidRepository $bidRepository, CelebrityRepository $celebrityRepository, AuctionRepository $auctionRepository): Response
    {
        $celebrities = $celebrityRepository->findAll();
        $nbCelebrity = $celebrityRepository->countCelebrities();
        $totalRaised = $bidRepository->getTotalWinnerAmount();
        $financialGoal = 3600000;
        $formattedFinancialGoal = number_format($financialGoal, 0, '.', ' ');
        $percentage = $financialGoal > 0 ? ($totalRaised / $financialGoal) * 100 : 0;
        $contactDTO = new ContactDTO();
        $form = $this->createForm(ContactType::class, $contactDTO, [
            'action' => $this->generateUrl('app_home_contact')  // Form action séparée
        ]);

        $tailwindWidthClass = match (true) {
            $percentage  < 1  => 'w-px',
            $percentage <= 10 => 'w-1/12',
            $percentage <= 20 => 'w-2/12',
            $percentage <= 30 => 'w-3/12',
            $percentage <= 40 => 'w-4/12',
            $percentage <= 50 => 'w-5/12',
            $percentage <= 60 => 'w-6/12',
            $percentage <= 70 => 'w-7/12',
            $percentage <= 80 => 'w-8/12',
            $percentage <= 90 => 'w-9/12',
            default => 'w-full',
        };

        return $this->render('home/index.html.twig', [
            'contactForm' => $form->createView(),
            'totalRaised' => $totalRaised,
            "nbCelebrity" => $nbCelebrity,
            'percentage' => $percentage,
            'formattedFinancialGoal' => $formattedFinancialGoal,
            'tailwindWidthClass' => $tailwindWidthClass,
            'celebrities' => $celebrities,
            'auctions' => $auctionRepository->findBy(
                ['status' => ['active', 'upcoming']],
                ['startedAt' => 'ASC']
            ),
        ]);
    }

    #[Route('/contact', name: 'app_home_contact', methods: ['POST'])]
    public function contact(Request $request): Response
    {
        $contactDTO = new ContactDTO();
        $form = $this->createForm(ContactType::class, $contactDTO);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->emailService->sendContactEmail($contactDTO);
                $this->addFlash('success', 'Votre message a bien été envoyé !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi du message.');
            }
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->redirectToRoute('app_home');
    }
//    #[Route('/api/total-raised', name: 'api_total_raised')]
//    public function getTotalRaised(): Response
//    {
//        // Génération d'un montant aléatoire pour la démonstration
//        $totalRaised = random_int(10000, 100000);
//        return $this->json(['totalRaised' => $totalRaised]);
//    }
}
