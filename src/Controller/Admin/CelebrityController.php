<?php

namespace App\Controller\Admin;

use App\Entity\Auction;
use App\Entity\Celebrity;
use App\Form\CelebrityAuctionType;
use App\Form\CelebrityType;
use App\Repository\CelebrityRepository;
use App\Repository\ProfessionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/celebrity')]
#[IsGranted('ROLE_ADMIN')]
class CelebrityController extends AbstractController
{
    #[Route(name: 'admin_celebrity', methods: ['GET'])]
    public function index(CelebrityRepository $celebrityRepository): Response
    {
        return $this->render('admin/celebrity/index.html.twig', [
            'celebrities' => $celebrityRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_celebrity_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ProfessionRepository $professionRepository, UserRepository $userRepository): Response
    {
        $celebrity = new Celebrity();
        $auction = new Auction();

        /****TEMPORAIRE****/
        $profession = $professionRepository->find(1); // ID du chanteur
        if ($profession) {
            $celebrity->setProfession($profession);
        }
        $auction->setStatus("test");
        $auction->setCreatedBy($this->getUser());
        /*******************************/

        $auction->setCelebrity($celebrity);
        $form = $this->createForm(CelebrityAuctionType::class, [
            'celebrity' => $celebrity,
            'auction' => $auction,
        ] , [
            'auction_options' => ['is_celebrity_registration' => true]
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Gestion de l'image
            if ($imageFile = $form->get('celebrity')['image']->getData()) {
                $newFilename = $this->handleFileUpload($imageFile, 'celebrities/images');
                $data['celebrity']->setImage($newFilename);
            }

            // Gestion de la vidéo de présentation
            if ($videoPresFile = $form->get('celebrity')['video_pres']->getData()) {
                $newFilename = $this->handleFileUpload($videoPresFile, 'celebrities/videos');
                $data['celebrity']->setVideoPres($newFilename);
            }

            // Gestion de la vidéo de remerciement
            if ($videoThanksFile = $form->get('celebrity')['video_thanks']->getData()) {
                $newFilename = $this->handleFileUpload($videoThanksFile, 'celebrities/videos');
                $data['celebrity']->setVideoThanks($newFilename);
            }

            $data['auction'] = $auction->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($data['celebrity']);
            $entityManager->persist($data['auction']);
            $entityManager->flush();

            $this->addFlash('success', 'La célébrité et l\'enchère ont été créés avec succès.');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('admin/celebrity/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function handleFileUpload($file, $directory): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/' . $directory,
                $newFilename
            );
        } catch (FileException $e) {
            throw new \Exception('Une erreur est survenue lors de l\'upload du fichier');
        }

        return $newFilename;
    }

    #[Route('/{id}', name: 'admin_celebrity_show', methods: ['GET'])]
    public function show(Celebrity $celebrity): Response
    {
        return $this->render('admin/celebrity/show.html.twig', [
            'celebrity' => $celebrity,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_celebrity_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Celebrity $celebrity, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CelebrityType::class, $celebrity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('admin_celebrity', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('admin/celebrity/edit.html.twig', [
            'celebrity' => $celebrity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_celebrity_delete', methods: ['POST'])]
    public function delete(Request $request, Celebrity $celebrity, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$celebrity->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($celebrity);
            $entityManager->flush();
        }
        return $this->redirectToRoute('admin_celebrity', [], Response::HTTP_SEE_OTHER);
    }
}
