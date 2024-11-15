<?php

namespace App\Repository;

use App\Entity\Auction;
use App\Entity\Bid;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BidRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bid::class);
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
}