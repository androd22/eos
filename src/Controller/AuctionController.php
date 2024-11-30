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

            // Vérifier et mettre à jour le statut de l'enchère
            $this->updateAuctionStatus($auction);

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
    #[Route('/api/auction/{id}/status', name: 'api_auction_status_update', methods: ['PUT'])]
    public function updateStatus(Request $request, Auction $auction): JsonResponse
    {
        try {
            $now = new \DateTime;
            $startTime = $auction->getStartedAt();
            $endTime = $auction->getFinishedAt();

            // Déterminer le statut basé sur les dates
            if ($now < $startTime) {
                $newStatus = 'upcoming';
            } elseif ($now >= $startTime && $now < $endTime) {
                $newStatus = 'active';
            } else {
                $newStatus = 'finished';
            }

            // Si le statut a changé
            if ($auction->getStatus() !== $newStatus) {
                $auction->setStatus($newStatus);
                $this->entityManager->flush();

                $this->logger->info('Statut de l\'enchère mis à jour', [
                    'auction_id' => $auction->getId(),
                    'old_status' => $auction->getStatus(),
                    'new_status' => $newStatus,
                    'start_time' => $startTime->format('Y-m-d H:i:s'),
                    'end_time' => $endTime->format('Y-m-d H:i:s'),
                    'current_time' => $now->format('Y-m-d H:i:s')
                ]);
            }

            return $this->json([
                'status' => 'success',
                'newStatus' => $newStatus,
                'startTime' => $startTime->format('c'),
                'endTime' => $endTime->format('c'),
                'currentTime' => $now->format('c')
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour du statut', [
                'error' => $e->getMessage(),
                'auction_id' => $auction->getId()
            ]);

            return $this->json(['error' => 'Une erreur est survenue lors de la mise à jour du statut'], 500);
        }
    }

    private function updateAuctionStatus(Auction $auction): void
    {
        $now = new \DateTime();
        $startTime = $auction->getStartedAt();
        $endTime = $auction->getFinishedAt();

        if ($now < $startTime) {
            $newStatus = 'upcoming';
        } elseif ($now >= $startTime && $now < $endTime) {
            $newStatus = 'active';
        } else {
            $newStatus = 'finished';
        }

        if ($auction->getStatus() !== $newStatus) {
            $auction->setStatus($newStatus);
            $this->entityManager->flush();
        }
    }



}