<?php

namespace App\Controller;

use App\Entity\Bid;
use App\Repository\AuctionRepository;
use App\Repository\BidRepository;
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
    public const MINIMUM_INCREMENT = 5;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuctionRepository $auctionRepository,
        private readonly BidRepository $bidRepository,
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger
    ) {}

    #[Route('/api/auction/{id}/bid', name: 'app_auction_bid', methods: ['POST'])]
    public function placeBid(Request $request, int $id): JsonResponse
    {
        try {
            // Vérification de l'authentification
            if (!$this->isGranted('ROLE_USER')) {
                throw new \Exception('Vous devez être connecté pour enchérir');
            }

            // Récupération de l'enchère
            $auction = $this->auctionRepository->findWithDetailsById($id);
            if (!$auction) {
                throw new \Exception('Enchère non trouvée');
            }

            // Vérification du statut de l'enchère
            if ($auction->getStatus() !== 'active') {
                throw new \Exception('Cette enchère n\'est pas active');
            }

            // Récupération et validation du montant
            $data = json_decode($request->getContent(), true);
            $amount = floatval($data['amount'] ?? 0);

            // Vérification du montant minimum
            $currentHighestBid = $this->bidRepository->findHighestBidForAuction($auction);
            $currentAmount = $currentHighestBid ? floatval($currentHighestBid->getAmount()) : floatval($auction->getProducts()->first()->getInitialPrice());
            $minimumBid = $currentAmount + self::MINIMUM_INCREMENT;

            if ($amount < $minimumBid) {
                throw new \Exception(sprintf(
                    'Le montant minimum est de %s€',
                    number_format($minimumBid, 2, ',', ' ')
                ));
            }

            // Création de la nouvelle enchère
            $bid = new Bid();
            $bid->setBidder($this->getUser());
            $bid->setProduct($auction->getProducts()->first());
            $bid->setAmount((string)$amount);
            $bid->setCreatedAt(new \DateTime());
            $bid->setStatus('active');
            $bid->setIpAddress($request->getClientIp());

            // Enregistrement de l'enchère
            $this->entityManager->persist($bid);
            $this->entityManager->flush();

            // Log de l'enchère
            $this->logger->info('Nouvelle enchère enregistrée', [
                'auction_id' => $id,
                'amount' => $amount,
                'bidder' => $this->getUser()->getUserIdentifier()
            ]);

            // Préparation des données pour Mercure
            $updateData = [
                'highestBid' => number_format($amount, 2, '.', ''),
                'minimumBid' => number_format($amount + self::MINIMUM_INCREMENT, 2, '.', ''),
                'bidder' => $this->getUser()->getUserIdentifier()
            ];

            // Publication Mercure
            try {
                $topic = sprintf('auction/%d', $auction->getId());
                $this->logger->info('Configuration Mercure', [
                    'mercure_url' => $_ENV['MERCURE_URL'] ?? 'non définie',
                    'topic' => $topic,
                    'updateData' => $updateData
                ]);

                $update = new Update(
                    'auction/' . $auction->getId(), // Assurez-vous que le format est exactement le même
                    json_encode($updateData),
                    false
                );

                $result = $this->hub->publish($update);

                $this->logger->info('Publication Mercure réussie', [
                    'topic' => $topic,
                    'result' => json_encode($result)
                ]);

            } catch (\Exception $e) {
                $this->logger->error('Erreur publication Mercure', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // Réponse au client
            return new JsonResponse([
                'success' => true,
                'data' => $updateData
            ]);

        } catch (\Exception $e) {
            // Log de l'erreur
            $this->logger->error('Erreur lors de l\'enchère', [
                'error' => $e->getMessage(),
                'auction_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            // Réponse d'erreur
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}