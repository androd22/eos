<?php

namespace App\Controller;

use App\Entity\Auction;
use App\Entity\Bid;
use App\Entity\Product;
use App\Repository\AuctionRepository;
use App\Repository\BidRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class BidController extends AbstractController
{
    public const MINIMUM_INCREMENT = 10;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BidRepository $bidRepository,
        private readonly ProductRepository $productRepository,
//        private readonly AuctionRepository $auctionRepository,
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger
    ) {}

    #[Route('/api/product/{id}/bid', name: 'app_bid_place', methods: ['POST'])]
    public function placeBid(Request $request, int $id): JsonResponse
    {
        try {
            // Vérification de l'authentification
            if (!$this->isGranted('ROLE_USER')) {
                throw new \Exception('Vous devez être connecté pour enchérir');
            }

            // Récupération du produit et son enchère
            $product = $this->productRepository->find($id);
            if (!$product) {
                throw new \Exception('Produit non trouvé');
            }

            $auction = $product->getAuction();
            if (!$auction) {
                throw new \Exception('Enchère non trouvée');
            }

            // Vérification du statut de l'enchère
            if ($auction->getStatus() !== 'active') {
                throw new \Exception('Cette enchère n\'est pas active');
            }

            $data = json_decode($request->getContent(), true);
            $amount = floatval($data['amount'] ?? 0);

            // Vérification du montant minimum
            $currentHighestBid = $this->bidRepository->findHighestBidForProduct($product);
            $currentAmount = $currentHighestBid ?
                floatval($currentHighestBid->getAmount()) :
                floatval($product->getInitialPrice());
            $minimumBid = $currentAmount + self::MINIMUM_INCREMENT;

            // Vérification stricte du montant
            if ($amount <= $currentAmount) {
                throw new \Exception(sprintf(
                    'Votre enchère doit être supérieure à l\'enchère actuelle de %s€',
                    number_format($currentAmount, 2, ',', ' ')
                ));
            }

            if ($amount < $minimumBid) {
                throw new \Exception(sprintf(
                    'Le montant minimum est de %s€ (incrément de %d€)',
                    number_format($minimumBid, 2, ',', ' '),
                    self::MINIMUM_INCREMENT
                ));
            }

            // Création de la nouvelle enchère
            $bid = new Bid();
            $bid->setBidder($this->getUser());
            $bid->setProduct($product);
            $bid->setAmount((string)$amount);
            $bid->setCreatedAt(new \DateTime());
            $bid->setStatus(Bid::STATUS_ACTIVE);
            $bid->setIpAddress($request->getClientIp());

            $now = new \DateTime();
            if ($now >= $auction->getFinishedAt()) {
                $auction->setStatus('finished');
                $bid->setStatus(Bid::STATUS_WINNER);
                $this->entityManager->flush();
            }

            // Enregistrement de l'enchère
            $this->entityManager->persist($bid);
            $this->entityManager->flush();


            // Calcul du total des enchères pour cette vente
            $highestBids = $this->bidRepository->getHighestBidsTotal($auction);
            $auctionTotal = array_reduce($highestBids, function ($carry, $item) {
                return $carry + $item['highestBid'];
            }, 0);

            // Log de l'enchère
            $this->logger->info('Nouvelle enchère enregistrée', [
                'product_id' => $id,
                'auction_id' => $auction->getId(),
                'amount' => $amount,
                'total' => $auctionTotal,
                'bidder' => $this->getUser()->getUserIdentifier(),
                'status' => $bid->getStatus()
            ]);

            // Préparation des données pour Mercure
            $updateData = [
                'type' => 'product_bid',
                'productId' => $product->getId(),
                'amount' => number_format($amount, 2, '.', ''),
                'minimumNextBid' => number_format($amount + self::MINIMUM_INCREMENT, 2, '.', ''),
                'bidder' => $this->getUser()->getUserIdentifier(),
                'auctionTotal' => number_format($auctionTotal, 2, '.', ''),
                'status' => $auction->getStatus()
            ];

            // Publication Mercure
            try {
                $this->logger->info('Publication Mercure', [
                    'topics' => [
                        'product/' . $product->getId(),
                        'auction/' . $auction->getId()
                    ],
                    'data' => $updateData
                ]);

                // Publication pour le produit
                $productUpdate = new Update(
                    'product/' . $product->getId(),
                    json_encode($updateData)
                );
                $this->hub->publish($productUpdate);

                // Publication pour l'enchère
                $auctionUpdate = new Update(
                    'auction/' . $auction->getId(),
                    json_encode($updateData)
                );
                $this->hub->publish($auctionUpdate);

            } catch (\Exception $e) {
                $this->logger->error('Erreur publication Mercure', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // Réponse au client
            return new JsonResponse([
                'success' => true,
                'data' => $updateData
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'enchère', [
                'error' => $e->getMessage(),
                'product_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}