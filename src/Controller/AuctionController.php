<?php

namespace App\Controller;

use App\Repository\AuctionRepository;
use App\Repository\BidRepository;
use App\Repository\ProfessionRepository;
use App\Service\JwtProvider;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        private readonly LoggerInterface $logger
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
            'criteria' => $searchCriteria
        ]);
    }

    #[Route('/enchere/{id<\d+>}', name: 'app_auction_show')]
    public function show(Request $request, int $id, JwtProvider $jwtProvider): Response
    {
        try {
            $auction = $this->auctionRepository->findWithDetailsById($id);

            if (!$auction) {
                throw $this->createNotFoundException('Enchère non trouvée');
            }

            $highestBid = $this->bidRepository->findHighestBidForAuction($auction);
            $currentAmount = $highestBid ? floatval($highestBid->getAmount()) : floatval($auction->getProducts()->first()->getInitialPrice());

            // Configuration Mercure
            $mercurePublicUrl = $_ENV['MERCURE_PUBLIC_URL'];  // Utilisation directe de la variable d'environnement
            // ou
            // $mercurePublicUrl = $this->getParameter('mercure.public_url');  // Si configuré dans services.yaml

            $topic = 'auction/' . $id;  // Même format
//            $subscriptionToken = $jwtProvider->generateToken([$topic]);

            // Log pour debug
            $this->logger->info('Configuration Mercure', [
                'mercureUrl' => $mercurePublicUrl,
                'topic' => $topic,
                'user' => $this->getUser()?->getUserIdentifier()
            ]);

            // Ajout du lien Mercure
            $this->addLink($request, new Link('mercure', $mercurePublicUrl));

            // Génération du token avec le topic spécifique
            $subscriptionToken = $jwtProvider->generateToken([$topic]);

            return $this->render('auction/show.html.twig', [
                'userId' => $this->getUser()?->getId(),
                'auction' => $auction,
                'highestBid' => $currentAmount,
                'minimumIncrement' => BidController::MINIMUM_INCREMENT,
                'minimumNextBid' => $currentAmount + BidController::MINIMUM_INCREMENT,
                'mercureUrl' => $mercurePublicUrl,
                'subscriptionToken' => $subscriptionToken,
                'topic' => $topic
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur dans show auction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}