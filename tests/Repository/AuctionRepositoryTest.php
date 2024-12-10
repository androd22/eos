<?php

namespace App\Tests\Repository;

use App\Repository\AuctionRepository;
use App\Entity\Auction;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class AuctionRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?AuctionRepository $auctionRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->auctionRepository = $this->entityManager->getRepository(Auction::class);
    }

    public function testFindByStatus(): void
    {
        $activeAuctions = $this->auctionRepository->findBy(['status' => 'active']);

        foreach ($activeAuctions as $auction) {
            $this->assertEquals('active', $auction->getStatus());
        }
    }

    public function testFindOneByWithProducts(): void
    {
        $auction = $this->auctionRepository->findOneBy([]);

        if (!$auction) {
            $this->markTestSkipped('Aucune enchère dans la base de test');
        }

        $this->assertInstanceOf(Auction::class, $auction);
        // Vérifier que nous pouvons accéder aux produits (si la relation est configurée)
        $this->assertIsArray($auction->getProducts()->toArray());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Fermer l'EntityManager
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }
}