<?php

namespace App\Controller\Admin;

use App\Entity\Image;
use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\AuctionRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/admin/product')]
class ProductController extends AbstractController
{
    #[Route(name: 'admin_product', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('admin/product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }


    #[Route('/new/{auction_id?}', name: 'admin_product_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        AuctionRepository $auctionRepository,
        ?int $auction_id = null
    ): Response {
        $product = new Product();

        if ($auction_id) {
            $auction = $auctionRepository->find($auction_id);
            if ($auction) {
                $product->setAuction($auction);
            }
        }

        $form = $this->createForm(ProductType::class, $product, [
            'include_auction' => !$auction_id
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Création du dossier d'upload s'il n'existe pas
            $uploadDir = $this->getParameter('products_directory');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $product->setBatchNumber(213);
            $product->setRegister(new \DateTimeImmutable());
            // Gestion des images
            $imagesForm = $form->get('images')->all();
            foreach ($imagesForm as $imageForm) {
                /** @var UploadedFile $file */
                $file = $imageForm->get('src')->getData();

                if ($file) {
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                    try {
                        $file->move($uploadDir, $newFilename);

                        // On récupère l'entité Image déjà créée par le formulaire
                        $image = $imageForm->getData();
                        $image->setSrc($newFilename);  // On met à jour le chemin du fichier
                        $image->setProduct($product);

                    } catch (FileException $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image : ' . $e->getMessage());
                    }
                }
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Le produit a été créé avec succès.');

            // Redirection appropriée selon le contexte
//            if ($auction_id) {
//                return $this->redirectToRoute('app_auction_show', ['id' => $auction_id]);
//            }
            return $this->redirectToRoute('admin_product_new');
        }

        return $this->render('admin/product/new.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/{id}', name: 'admin_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('admin/product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Product1Type::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_product', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_product', [], Response::HTTP_SEE_OTHER);
    }
}
