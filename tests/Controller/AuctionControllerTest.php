<?php

namespace App\Tests\Controller;

use App\Entity\Auction;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuctionControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/admin/auction/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(Auction::class);

        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Auction index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'auction[name]' => 'Testing',
            'auction[description]' => 'Testing',
            'auction[status]' => 'Testing',
            'auction[createdAt]' => 'Testing',
            'auction[started_at]' => 'Testing',
            'auction[finishedAt]' => 'Testing',
            'auction[image]' => 'Testing',
            'auction[celebrityName]' => 'Testing',
            'auction[createdBy]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->repository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Auction();
        $fixture->setName('My Title');
        $fixture->setDescription('My Title');
        $fixture->setStatus('My Title');
        $fixture->setCreatedAt('My Title');
        $fixture->setStarted_at('My Title');
        $fixture->setFinishedAt('My Title');
        $fixture->setImage('My Title');
        $fixture->setCelebrityName('My Title');
        $fixture->setCreatedBy('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Auction');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Auction();
        $fixture->setName('Value');
        $fixture->setDescription('Value');
        $fixture->setStatus('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setStarted_at('Value');
        $fixture->setFinishedAt('Value');
        $fixture->setImage('Value');
        $fixture->setCelebrityName('Value');
        $fixture->setCreatedBy('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'auction[name]' => 'Something New',
            'auction[description]' => 'Something New',
            'auction[status]' => 'Something New',
            'auction[createdAt]' => 'Something New',
            'auction[started_at]' => 'Something New',
            'auction[finishedAt]' => 'Something New',
            'auction[image]' => 'Something New',
            'auction[celebrityName]' => 'Something New',
            'auction[createdBy]' => 'Something New',
        ]);

        self::assertResponseRedirects('/admin/auction/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getName());
        self::assertSame('Something New', $fixture[0]->getDescription());
        self::assertSame('Something New', $fixture[0]->getStatus());
        self::assertSame('Something New', $fixture[0]->getCreatedAt());
        self::assertSame('Something New', $fixture[0]->getStarted_at());
        self::assertSame('Something New', $fixture[0]->getFinishedAt());
        self::assertSame('Something New', $fixture[0]->getImage());
        self::assertSame('Something New', $fixture[0]->getCelebrityName());
        self::assertSame('Something New', $fixture[0]->getCreatedBy());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Auction();
        $fixture->setName('Value');
        $fixture->setDescription('Value');
        $fixture->setStatus('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setStarted_at('Value');
        $fixture->setFinishedAt('Value');
        $fixture->setImage('Value');
        $fixture->setCelebrityName('Value');
        $fixture->setCreatedBy('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/admin/auction/');
        self::assertSame(0, $this->repository->count([]));
    }
}
