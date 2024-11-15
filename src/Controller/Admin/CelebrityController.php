<?php

namespace App\Controller\Admin;

use App\Entity\Auction;
use App\Entity\Celebrity;
use App\Entity\Product;
use App\Entity\Profession;
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

#[Route('/admin/celebrity')]
final class CelebrityController extends AbstractController
{
    #[Route(name: 'admin_celebrity_index', methods: ['GET'])]
    public function index(CelebrityRepository $celebrityRepository): Response
    {
        return $this->render('admin/celebrity/index.html.twig', [
            'celebrities' => $celebrityRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_celebrity_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ProfessionRepository $professionRepository, UserRepository $userRepository): Response
    {
//        $celebrity = new Celebrity();
//        $form = $this->createForm(CelebrityType::class, $celebrity);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $entityManager->persist($celebrity);
//            $entityManager->flush();
//
//            return $this->redirectToRoute('app_admin_celebrity_index', [], Response::HTTP_SEE_OTHER);
//        }

        $celebrity = new Celebrity();
        $auction = new Auction();
        $product = new Product();

        /****TEMPORAIRE****/
        $profession = $professionRepository->find(1); // ID du chanteur
        if ($profession) {
            $celebrity->setProfession($profession);
        }
        $auction->setStatus("test");
        $auction->setCreatedBy($this->getUser());
        $product->setBatchNumber(213);

        /*******************************/
        $auction->setCelebrity($celebrity);
        $product->setAuction($auction);;


        $form = $this->createForm(CelebrityAuctionType::class, [
            'celebrity' => $celebrity,
            'auction' => $auction,
            'product' => $product,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            // Persist the entities
            $entityManager->persist($data['celebrity']);
            $entityManager->persist($data['auction']);
            $entityManager->persist($data['product']);
            $entityManager->flush();

            $this->addFlash('success', 'La célébrité, l\'enchère et le produit ont été créés avec succès.');
            return $this->redirectToRoute('app_dashboard');
        }
        return $this->render('admin/celebrity/new.html.twig', [
            'form' => $form->createView(),
        ]);
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

            return $this->redirectToRoute('admin_celebrity_index', [], Response::HTTP_SEE_OTHER);
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

        return $this->redirectToRoute('admin_celebrity_index', [], Response::HTTP_SEE_OTHER);
    }
}
