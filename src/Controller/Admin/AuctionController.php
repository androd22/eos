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
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/auction')]
final class AuctionController extends AbstractController
{
    #[Route(name: 'admin_auction', methods: ['GET'])]
    public function index(AuctionRepository $auctionRepository): Response
    {

        return $this->render('admin/auction/index.html.twig', [
            'auctions' => $auctionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_auction_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response
    {
        $auction = new Auction();
        $form = $this->createForm(AuctionType::class, $auction, [
            'is_celebrity_registration' => false
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTimeImmutable();
            $auction->setCreatedBy($this->getUser());
            // Gérer l'upload de l'image de l'enchère


            // Définir le statut initial et la date de création
            $auction->setStatus(Auction::STATUS_UPCOMING);
            $auction->setCreatedAt($now);
            // Persister et flush
            $entityManager->persist($auction);
            $entityManager->flush();

            $this->addFlash('success', 'L\'enchère a été créée avec succès.');
            return $this->redirectToRoute('admin_product_new', [
                'auction_id' => $auction->getId()
            ]);
        }

        return $this->render('admin/auction/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_auction_show', methods: ['GET'])]
    public function show(Auction $auction): Response
    {
        return $this->render('admin/auction/show.html.twig', [
            'auction' => $auction,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_auction_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Auction $auction,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response
    {
        $form = $this->createForm(AuctionType::class, $auction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de la nouvelle image si elle existe
            if ($imageFile = $form->get('image')->getData()) {
                // Supprimer l'ancienne image si elle existe
                if ($auction->getImage()) {
                    $oldImagePath = $this->getParameter('auctions_directory') . '/' . $auction->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Upload de la nouvelle image
                try {
                    $newFilename = $this->handleFileUpload($imageFile, 'auctions_directory', $slugger);
                    $auction->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image : ' . $e->getMessage());
                    return $this->redirectToRoute('admin_auction_edit', ['id' => $auction->getId()]);
                }
            }

            try {
                $entityManager->flush();
                $this->addFlash('success', 'L\'enchère a été modifiée avec succès.');
                return $this->redirectToRoute('admin_auction', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la modification de l\'enchère.');
                return $this->redirectToRoute('admin_auction_edit', ['id' => $auction->getId()]);
            }
        }

        return $this->render('admin/auction/edit.html.twig', [
            'auction' => $auction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: '_admin_auction_delete', methods: ['POST'])]
    public function delete(Request $request, Auction $auction, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$auction->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($auction);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_auction', [], Response::HTTP_SEE_OTHER);
    }

    private function handleFileUpload(
        \Symfony\Component\HttpFoundation\File\UploadedFile $file,
        string $targetDirectory,
        SluggerInterface $slugger
    ): string {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move(
                $this->getParameter($targetDirectory),
                $newFilename
            );
            return $newFilename;
        } catch (\Exception $e) {
            throw new \Exception('Une erreur est survenue lors de l\'upload du fichier : ' . $e->getMessage());
        }
    }
}
