<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Celebrity;
use App\Entity\User;
use App\Repository\CelebrityRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CelebrityControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testAccessDeniedForNonAdmin(): void
    {
        // Test l'accès sans être connecté
        $this->client->request('GET', '/admin/celebrity');
        $this->assertResponseRedirects('/login');

        // Connexion en tant qu'utilisateur non admin
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('user@example.com');
        $this->client->loginUser($testUser);

        // Test l'accès en tant qu'utilisateur non admin
        $this->client->request('GET', '/admin/celebrity');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testIndexPageForAdmin(): void
    {
        // Connexion en tant qu'admin
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testAdmin = $userRepository->findOneByEmail('admin@example.com');
        $this->client->loginUser($testAdmin);

        // Test l'accès à la page index
        $this->client->request('GET', '/admin/celebrity');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Célébrités');
    }

    public function testNewCelebrity(): void
    {
        // Connexion en tant qu'admin
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testAdmin = $userRepository->findOneByEmail('admin@example.com');
        $this->client->loginUser($testAdmin);

        // Accès au formulaire de création
        $crawler = $this->client->request('GET', '/admin/celebrity/new');
        $this->assertResponseIsSuccessful();

        // Simulation de soumission du formulaire
        $form = $crawler->selectButton('Sauvegarder')->form([
            'celebrity_auction[celebrity][stageName]' => 'Test Celebrity',
            'celebrity_auction[celebrity][realFirstName]' => 'John',
            'celebrity_auction[celebrity][realLastName]' => 'Doe',
            'celebrity_auction[celebrity][biography]' => 'Test biography',
            'celebrity_auction[celebrity][image]' => 'test.jpg',
            // Ajoutez les autres champs nécessaires
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/dashboard');
    }

    public function testEditCelebrity(): void
    {
        // Connexion en tant qu'admin
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testAdmin = $userRepository->findOneByEmail('admin@example.com');
        $this->client->loginUser($testAdmin);

        // Récupération d'une célébrité existante
        $celebrityRepository = static::getContainer()->get(CelebrityRepository::class);
        $celebrity = $celebrityRepository->findOneBy([]);

        if ($celebrity) {
            $this->client->request('GET', sprintf('/admin/celebrity/%s/edit', $celebrity->getId()));
            $this->assertResponseIsSuccessful();

            // Test de modification
            $this->client->submitForm('Sauvegarder', [
                'celebrity[stageName]' => 'Updated Name',
            ]);

            $this->assertResponseRedirects('/admin/celebrity');
            $this->client->followRedirect();
            $this->assertSelectorTextContains('body', 'Updated Name');
        }
    }

    public function testDeleteCelebrity(): void
    {
        // Connexion en tant qu'admin
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testAdmin = $userRepository->findOneByEmail('admin@example.com');
        $this->client->loginUser($testAdmin);

        // Création d'une célébrité pour le test
        $celebrity = new Celebrity();
        $celebrity->setStageName('To Delete');
        $celebrity->setRealFirstName('John');
        $celebrity->setRealLastName('Doe');
        $celebrity->setBiography('Test');
        $celebrity->setImage('test.jpg');

        $this->entityManager->persist($celebrity);
        $this->entityManager->flush();

        // Test de suppression
        $this->client->request('POST', sprintf('/admin/celebrity/%s', $celebrity->getId()), [
            '_token' => $this->client->getContainer()->get('security.csrf.token_manager')->getToken('delete'.$celebrity->getId())->getValue(),
        ]);

        $this->assertResponseRedirects('/admin/celebrity');
        $this->assertNull($this->entityManager->getRepository(Celebrity::class)->find($celebrity->getId()));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}