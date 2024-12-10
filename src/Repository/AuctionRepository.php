<?php

namespace App\Repository;

use App\DTO\AuctionSearchCriteria;
use App\Entity\Auction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends ServiceEntityRepository<Auction>
 */
class AuctionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Auction::class);
    }

//    /**
//     * @return Auction[] Returns an array of Auction objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Auction
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function createSearchCriteria(Request $request): AuctionSearchCriteria
    {
        return AuctionSearchCriteria::create(
            auctionRepository: $this,
            searchTerm: $this->findBySearchTerm($request->query->get('q')),
            professionId: $this->findByProfessionId($request->query->get('profession')),
            status: $this->findByStatus($request->query->get('status', 'active')),
            priceOrder: $request->query->get('price'),
            page: $request->query->getInt('page', 1)
        );
    }

//    public function findActiveAuctions(): QueryBuilder
//    {
//        return $this->createQueryBuilder('a')
//            ->leftJoin('a.celebrity', 'c')
//            ->leftJoin('c.profession', 'p')
//            ->leftJoin('a.products', 'prod')
//            ->addSelect('c', 'p', 'prod')
//            ->where('a.status = :status')
//            ->andWhere('a.finishedAt > :now')
//            ->setParameter('status', 'active')
//            ->setParameter('now', new \DateTime())
//            ->orderBy('a.createdAt', 'DESC');
//    }

    public function findActiveAuctions(): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.celebrity', 'c')
            ->leftJoin('c.profession', 'p')
            ->leftJoin('a.products', 'prod')
            ->addSelect('c', 'p', 'prod')
            ->orderBy('a.createdAt', 'DESC');
    }

    public function findUpcomingAuctions(): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.celebrity', 'c')
            ->leftJoin('c.profession', 'p')
            ->leftJoin('a.products', 'prod')
            ->addSelect('c', 'p', 'prod')
            ->where('a.startedAt > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('a.startedAt', 'ASC');
    }

    public function findFinishedAuctions(): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.celebrity', 'c')
            ->leftJoin('c.profession', 'p')
            ->leftJoin('a.products', 'prod')
            ->addSelect('c', 'p', 'prod')
            ->where('a.finishedAt < :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('a.finishedAt', 'DESC');
    }

    public function findBySearchCriteria(?string $searchTerm, ?int $professionId, ?string $priceOrder): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.celebrity', 'c')
            ->leftJoin('c.profession', 'p')
            ->leftJoin('a.products', 'prod')
            ->addSelect('c', 'p', 'prod');

        if ($searchTerm) {
            $qb->andWhere('a.name LIKE :search OR a.description LIKE :search OR c.stageName LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        if ($professionId) {
            $qb->andWhere('p.id = :professionId')
                ->setParameter('professionId', $professionId);
        }

        if ($priceOrder) {
            $direction = $priceOrder === 'asc' ? 'ASC' : 'DESC';
            $qb->leftJoin('prod.bids', 'b')
                ->orderBy('COALESCE(MAX(b.amount), prod.initialPrice)', $direction)
                ->groupBy('a.id, c.id, p.id, prod.id');
        } else {
            $qb->orderBy('a.createdAt', 'DESC');
        }

        return $qb;
    }

    public function findWithDetailsById(int $id): ?Auction
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.celebrity', 'c')
            ->leftJoin('c.profession', 'p')
            ->leftJoin('a.products', 'prod')
            ->leftJoin('prod.bids', 'b')
            ->leftJoin('b.bidder', 'u')
            ->addSelect('c', 'p', 'prod', 'b', 'u')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findBySearchTerm(?string $term): ?string
    {
        if (!$term) {
            return null;
        }
        return $term;
    }

    public function findByProfessionId(?string $professionId): ?int
    {
        if (!$professionId) {
            return null;
        }
        return (int) $professionId;
    }

    public function findByStatus(?string $status): string
    {
        return $status ?? 'active';
    }
}
