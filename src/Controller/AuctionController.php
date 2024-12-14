<?php

namespace App\Controller;

use App\Entity\Auction;
use App\Entity\Bid;
use App\Repository\AuctionRepository;
use App\Repository\BidRepository;
use App\Repository\CelebrityRepository;
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

    #[Route('/auctions', name: 'app_auctions')]
    public function index(Request $request, CelebrityRepository $celebrityRepository, BidRepository $bidRepository): Response
    {
        $nbCelebrity = $celebrityRepository->countCelebrities();
        $totalRaised = $bidRepository->getTotalWinnerAmount();
        $financialGoal = 3600000;
        $formattedFinancialGoal = number_format($financialGoal, 0, '.', ' ');
        $percentage = $financialGoal > 0 ? ($totalRaised / $financialGoal) * 100 : 0;
        $searchCriteria = $this->auctionRepository->createSearchCriteria($request);

        // Récupérer toutes les enchères avant pagination pour le regroupement
        $allAuctions = $searchCriteria->getQueryBuilder()->getQuery()->getResult();

        // Grouper les enchères par statut
        $groupedAuctions = [
            'upcoming' => [],
            'active' => [],
            'finished' => []
        ];

        foreach ($allAuctions as $auction) {
            if ($auction->isUpcoming()) {
                $groupedAuctions['upcoming'][] = $auction;
            } elseif ($auction->isActive()) {
                $groupedAuctions['active'][] = $auction;
            } else {
                $groupedAuctions['finished'][] = $auction;
            }
        }

        // Paginer le résultat groupé
        $auctions = $this->paginator->paginate(
            $searchCriteria->getQueryBuilder(),
            $searchCriteria->page,
            9
        );

        return $this->render('auction/auction.html.twig', [
            'auctions' => $auctions,
            'groupedAuctions' => $groupedAuctions,
            'professions' => $this->professionRepository->findAll(),
            'criteria' => $searchCriteria,
            'mercureUrl' => $_ENV['MERCURE_PUBLIC_URL'],
            'totalRaised' => $totalRaised,
            "nbCelebrity" => $nbCelebrity,
            'percentage' => $percentage,
            'formattedFinancialGoal' => $formattedFinancialGoal,
        ]);
    }

    #[Route('/auction/{id<\d+>}', name: 'app_auction_show')]
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
            $newStatus = match(true) {
                $now < $auction->getStartedAt() => Auction::STATUS_UPCOMING,
                $now > $auction->getFinishedAt() => Auction::STATUS_FINISHED,
                default => Auction::STATUS_ACTIVE
            };

            // Met à jour en BDD si le statut a changé
            if ($oldStatus !== $newStatus) {
                $auction->setStatus($newStatus);

                // Si l'enchère vient de se terminer, on gère les gagnants
                if ($newStatus === Auction::STATUS_FINISHED) {
                    foreach ($auction->getProducts() as $product) {
                        if ($lastBid = $this->bidRepository->findLastBidByProduct($product)) {
                            $this->logger->info('Dernier bid trouvé pour le produit', [
                                'product_id' => $product->getId(),
                                'bid_id' => $lastBid->getId(),
                                'amount' => $lastBid->getAmount(),
                            ]);

                            $lastBid->setStatus(Bid::STATUS_WINNER);
                            $product->setFinalPrice($lastBid->getAmount());

                            $this->entityManager->persist($lastBid);
                            $this->entityManager->persist($product);
                        } else {
                            $this->logger->warning('Aucun bid trouvé pour le produit', [
                                'product_id' => $product->getId(),
                            ]);
                        }
                    }
                }

                $this->entityManager->flush();
            }

            return $this->json([
                'success' => true,
                'currentStatus' => $newStatus,
                'startTime' => $auction->getStartedAt()->format('c'),
                'endTime' => $auction->getFinishedAt()->format('c'),
                'currentTime' => $now->format('c')
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour du statut', [
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'auction_id' => $auction->getId()
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue lors de la vérification du statut'
            ], 500);
        }
    }



}