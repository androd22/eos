<?php
namespace App\Controller;

use App\DTO\ContactDTO;
use App\Form\ContactType;
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
    public function index(Request $request): Response
    {
        $contactDTO = new ContactDTO();
        $form = $this->createForm(ContactType::class, $contactDTO, [
            'action' => $this->generateUrl('app_home_contact')  // Form action séparée
        ]);

        return $this->render('home/index.html.twig', [
            'contactForm' => $form->createView()
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
