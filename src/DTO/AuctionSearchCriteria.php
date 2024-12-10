<?php

namespace App\DTO;

use App\Repository\AuctionRepository;
use Doctrine\ORM\QueryBuilder;

class AuctionSearchCriteria
{
    public function __construct(
        private readonly AuctionRepository $auctionRepository,
        public readonly ?string $searchTerm = null,
        public readonly ?int $professionId = null,
        public readonly ?string $status = 'active',
        public readonly ?string $priceOrder = null,
        public readonly int $page = 1
    ) {}

    public static function create(
        AuctionRepository $auctionRepository,
        ?string $searchTerm = null,
        ?int $professionId = null,
        ?string $status = 'active',
        ?string $priceOrder = null,
        int $page = 1
    ): self {
        return new self(
            auctionRepository: $auctionRepository,
            searchTerm: $searchTerm,
            professionId: $professionId,
            status: $status,
            priceOrder: $priceOrder,
            page: $page
        );
    }

    public function getQueryBuilder(): QueryBuilder
    {
        if ($this->hasFilters()) {
            return $this->auctionRepository->findBySearchCriteria(
                searchTerm: $this->searchTerm,
                professionId: $this->professionId,
                priceOrder: $this->priceOrder
            );
        }

        return match($this->status) {
            'upcoming' => $this->auctionRepository->findUpcomingAuctions(),
            'finished' => $this->auctionRepository->findFinishedAuctions(),
            default => $this->auctionRepository->findActiveAuctions(),
        };
    }

    private function hasFilters(): bool
    {
        return $this->searchTerm !== null
            || $this->professionId !== null
            || $this->priceOrder !== null;
    }
}