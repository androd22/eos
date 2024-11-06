<?php

namespace App\Controller\Admin;

use App\Entity\Auction;
use App\Form\AuctionType;
use App\Repository\AuctionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/auction')]
final class AuctionController extends AbstractController
{
    #[Route(name: 'app_admin_auction_index', methods: ['GET'])]
    public function index(AuctionRepository $auctionRepository): Response
    {
        return $this->render('admin/auction/index.html.twig', [
            'auctions' => $auctionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_auction_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $auction = new Auction();
        $form = $this->createForm(AuctionType::class, $auction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($auction);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_auction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/auction/new.html.twig', [
            'auction' => $auction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_auction_show', methods: ['GET'])]
    public function show(Auction $auction): Response
    {
        return $this->render('admin/auction/show.html.twig', [
            'auction' => $auction,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_auction_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Auction $auction, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AuctionType::class, $auction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_auction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/auction/edit.html.twig', [
            'auction' => $auction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_auction_delete', methods: ['POST'])]
    public function delete(Request $request, Auction $auction, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$auction->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($auction);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_auction_index', [], Response::HTTP_SEE_OTHER);
    }
}
