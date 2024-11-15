<?php

namespace App\EventListener;

use App\Entity\Auction;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::prePersist, entity: Auction::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Auction::class)]
class AuctionStatusListener
{
    public function prePersist(Auction $auction, LifecycleEventArgs $event): void
    {
        $auction->updateStatus();
    }

    public function preUpdate(Auction $auction, LifecycleEventArgs $event): void
    {
        $auction->updateStatus();
    }
}