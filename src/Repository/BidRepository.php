<?php

namespace App\Repository;

use App\Entity\Auction;
use App\Entity\Bid;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BidRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bid::class);
    }

    public function findHighestBidForProduct(Product $product): ?Bid
    {
        return $this->createQueryBuilder('b')
            ->where('b.product = :product')
            ->setParameter('product', $product)
            ->orderBy('b.amount', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findHighestBidForAuction(Auction $auction): ?Bid
    {
        return $this->createQueryBuilder('b')
            ->join('b.product', 'p')
            ->where('p.auction = :auction')
            ->setParameter('auction', $auction)
            ->orderBy('b.amount', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getHighestBidsTotal(Auction $auction): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return $qb
            ->select('p.id as productId, 
                  COALESCE(MAX(b.amount), p.initialPrice) as highestBid')
            ->from('App\Entity\Product', 'p')
            ->leftJoin('App\Entity\Bid', 'b', 'WITH', 'b.product = p.id')
            ->where('p.auction = :auction')
            ->setParameter('auction', $auction)
            ->groupBy('p.id')
            ->getQuery()
            ->getResult();
    }


    public function findBidsForAuction(Auction $auction): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.product', 'p')
            ->join('b.user', 'u')
            ->where('p.auction = :auction')
            ->setParameter('auction', $auction)
            ->orderBy('b.amount', 'DESC')
            ->addOrderBy('b.createdAt', 'DESC')
            ->select('b', 'u.username')
            ->getQuery()
            ->getResult();
    }

    public function calculateAuctionTotal(Auction $auction): float
    {
        $queryBuilder = $this->createQueryBuilder('b')
            ->join('b.product', 'p')
            ->where('p.auction = :auction')
            ->setParameter('auction', $auction);

        $queryBuilder
            ->select('SUM(b.amount) as total')
            ->groupBy('p.auction')
            ->having('p.auction = :auction');

        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        return floatval($result['total'] ?? 0);
    }

    public function findLastBidByProduct(Product $product): ?Bid
    {
        return $this->createQueryBuilder('b')
            ->where('b.product = :product')
            ->setParameter('product', $product)
            ->orderBy('b.id', 'DESC') // On prend le plus grand ID
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getTotalWinnerAmount(): float
    {
        return (float) $this->createQueryBuilder('b')
            ->select('SUM(b.amount)')
            ->where('b.status = :status') // Suppose que "winner" est un statut ou une condition identifiable
            ->setParameter('status', 'winner')
            ->getQuery()
            ->getSingleScalarResult();
    }


}