<?php

namespace App\Tests\Entity;

use App\Entity\Auction;
use PHPUnit\Framework\TestCase;

class AuctionTest extends TestCase
{
    private Auction $auction;

    protected function setUp(): void
    {
        $this->auction = new Auction();
    }

    public function testAuctionCreation(): void
    {
        $this->assertInstanceOf(Auction::class, $this->auction);
    }

    public function testAuctionInitialValues(): void
    {
        $this->assertNull($this->auction->getName());
        $this->assertNull($this->auction->getDescription());
        $this->assertNull($this->auction->getCreatedBy());
    }

    public function testAuctionSettersAndGetters(): void
    {
        $name = "Test Auction";
        $description = "Test Description";
        $status = "active";

        $this->auction->setName($name);
        $this->auction->setDescription($description);
        $this->auction->setStatus($status);

        $this->assertEquals($name, $this->auction->getName());
        $this->assertEquals($description, $this->auction->getDescription());
        $this->assertEquals($status, $this->auction->getStatus());
    }

    public function testAuctionDates(): void
    {
        $startDate = new \DateTimeImmutable();
        $endDate = new \DateTime('+1 week');

        $this->auction->setStartedAt($startDate);
        $this->auction->setFinishedAt($endDate);

        $this->assertEquals($startDate, $this->auction->getStartedAt());
        $this->assertNotNull($this->auction->getFinishedAt());
    }
}