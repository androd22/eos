<?php

namespace App\Controller;

use App\Entity\Auction;
use App\Repository\AuctionRepository;
use App\Repository\BidRepository;
use App\Repository\ProfessionRepository;
use App\Service\JwtProvider;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\WebLink\Link;
use Psr\Log\LoggerInterface;

class AuctionController extends AbstractController
{
    public function __construct(
        private readonly AuctionRepository $auctionRepository,
        private readonly BidRepository $bidRepository,
        private readonly ProfessionRepository $professionRepository,
        private readonly PaginatorInterface $paginator,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
        private readonly JwtProvider $jwtProvider
    ) {}

    #[Route('/encheres', name: 'app_auctions')]
    public function index(Request $request): Response
    {
        $searchCriteria = $this->auctionRepository->createSearchCriteria($request);

        $auctions = $this->paginator->paginate(
            $searchCriteria->getQueryBuilder(),
            $searchCriteria->page,
            9
        );

        return $this->render('auction/auction.html.twig', [
            'auctions' => $auctions,
            'professions' => $this->professionRepository->findAll(),
            'criteria' => $searchCriteria,
            'mercureUrl' => $_ENV['MERCURE_PUBLIC_URL']
        ]);
    }

    #[Route('/enchere/{id<\d+>}', name: 'app_auction_show')]
    public function show(Request $request, int $id): Response
    {
        try {
            $auction = $this->auctionRepository->findWithDetailsById($id);

            if (!$auction) {
                throw $this->createNotFoundException('Enchère non trouvée');
            }

            // Mise à jour du statut via l'endpoint existant
//            $this->updateStatus($request, $auction);

            // Créer un tableau avec les informations de chaque produit et sa plus haute enchère
            $productsWithBids = [];
            foreach ($auction->getProducts() as $product) {
                $highestBid = $this->bidRepository->findHighestBidForProduct($product);
                $productsWithBids[] = [
                    'product' => $product,
                    'highestBid' => $highestBid ? floatval($highestBid->getAmount()) : floatval($product->getInitialPrice()),
                    'minimumNextBid' => ($highestBid ? floatval($highestBid->getAmount()) : floatval($product->getInitialPrice())) + BidController::MINIMUM_INCREMENT
                ];
            }

            return $this->render('auction/show.html.twig', [
                'userId' => $this->getUser()?->getId(),
                'auction' => $auction,
                'productsWithBids' => $productsWithBids,
                'minimumIncrement' => BidController::MINIMUM_INCREMENT,
                'mercureUrl' => $_ENV['MERCURE_PUBLIC_URL'],
                'subscriptionToken' => $this->jwtProvider->generateToken(['auction/' . $id])
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur dans show auction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    #[Route('/api/auction/{id}/status', name: 'api_auction_status_get', methods: ['GET'])]
    public function getStatus(Auction $auction): JsonResponse
    {
        try {
            $now = new \DateTime();
            $oldStatus = $auction->getStatus();

            // Détermine le nouveau statut
            if ($now < $auction->getStartedAt()) {
                $newStatus = Auction::STATUS_UPCOMING;
            } elseif ($now > $auction->getFinishedAt()) {
                $newStatus = Auction::STATUS_FINISHED;
            } else {
                $newStatus = Auction::STATUS_ACTIVE;
            }

            // Met à jour en BDD si le statut a changé
            if ($oldStatus !== $newStatus) {
                $auction->setStatus($newStatus);
                $this->entityManager->flush();

                // Publier le changement via Mercure
                $update = new Update(
                    sprintf('auction/%d', $auction->getId()),
                    json_encode([
                        'type' => 'status_change',
                        'auctionId' => $auction->getId(),
                        'newStatus' => $newStatus,
                        'startTime' => $auction->getStartedAt()->format('c'),
                        'endTime' => $auction->getFinishedAt()->format('c')
                    ])
                );

                $this->hub->publish($update);
            }

            return $this->json([
                'status' => 'success',
                'currentStatus' => $newStatus,
                'startTime' => $auction->getStartedAt()->format('c'),
                'endTime' => $auction->getFinishedAt()->format('c'),
                'currentTime' => $now->format('c')
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour du statut', [
                'error' => $e->getMessage(),
                'auction_id' => $auction->getId()
            ]);

            return $this->json(['error' => 'Une erreur est survenue'], 500);
        }
    }


}