<?php

namespace App\DataFixtures;

use App\Entity\Auction;
use App\Entity\Product;
use App\Entity\Bid;
use App\Entity\Image;
use App\Entity\User;
use App\Entity\Celebrity;
use App\Entity\Profession;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Création d'une profession
        $profession = new Profession();
        $profession->setName('Artiste');
        $manager->persist($profession);

        // Création des célébrités
        $celebrities = [];
        for ($i = 0; $i < 5; $i++) {
            $celebrity = new Celebrity();
            $celebrity
                ->setStageName($faker->name())
                ->setRealFirstName($faker->firstName())
                ->setRealLastName($faker->lastName())
                ->setBiography($faker->paragraphs(3, true))
                ->setImage($faker->imageUrl())
                ->setImageAlt($faker->words(3, true))
                ->setProfession($profession);

            $manager->persist($celebrity);
            $celebrities[] = $celebrity;
        }

        // Création des utilisateurs
        $admin = new User();
        $admin->setEmail('androd@eos.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setFirstName('Andy')
            ->setLastName('Rod')
            ->setTelephone('0102030405')
            ->setIsKycVerified(true)
            ->setVerified(true);

        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'password123!');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        $user = new User();
        $user->setEmail('first@mail.com')
            ->setRoles(['ROLE_USER'])
            ->setFirstName('First')
            ->setLastName('Test')
            ->setTelephone('0605040302')
            ->setIsKycVerified(true)
            ->setVerified(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123!');
        $user->setPassword($hashedPassword);
        $manager->persist($user);

        $users = [$admin, $user];

        // Création des enchères (auctions)
        $auctions = [];
        for ($i = 0; $i < 10; $i++) {
            $auction = new Auction();
            $startedAt = \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 month', 'now'));
            $finishedAt = $faker->dateTimeBetween('+1 week', '+2 months');

            $auction->setName($faker->words(3, true))
                ->setDescription($faker->paragraph(3))
                ->setCreatedBy($faker->randomElement($users))
                ->setFinishedAt($finishedAt)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setStatus($faker->randomElement(['draft', 'active', 'closed']))
                ->setStartedAt($startedAt)
                ->setCelebrity($faker->randomElement($celebrities));

            $manager->persist($auction);
            $auctions[] = $auction;
        }

        // Création des produits
        foreach ($auctions as $auction) {
            $numProducts = rand(1, 5);
            for ($i = 0; $i < $numProducts; $i++) {
                $product = new Product();
                $initialPrice = $faker->randomFloat(2, 100, 10000);

                $product->setName($faker->words(3, true))
                    ->setDescription($faker->paragraph())
                    ->setBatchNumber($faker->unique()->numberBetween(1000, 9999))
                    ->setWeight($faker->optional(0.7)->numberBetween(100, 5000))
                    ->setRegister(new \DateTimeImmutable())
                    ->setInitialPrice($initialPrice)
                    ->setFinalPrice($faker->optional(0.3)->randomFloat(2, $initialPrice, $initialPrice * 1.5))
                    ->setAuction($auction);

                $manager->persist($product);

                // Création des images pour chaque produit
                $numImages = rand(1, 4);
                for ($j = 0; $j < $numImages; $j++) {
                    $image = new Image();
                    $image->setSrc($faker->imageUrl(640, 480, 'product'))
                        ->setAlt($faker->words(3, true))
                        ->setTypeImage($faker->randomElement(['principal', 'gallery', 'thumbnail']))
                        ->setProduct($product);

                    $manager->persist($image);
                }

                // Création des enchères (bids) pour chaque produit
                if ($auction->getStatus() === 'active') {
                    $numBids = rand(0, 8);
                    $currentAmount = $initialPrice;
                    $bids = [];

                    for ($k = 0; $k < $numBids; $k++) {
                        $bid = new Bid();
                        $currentAmount += $faker->randomFloat(2, 50, 500);

                        $bid->setAmount((string)$currentAmount)
                            ->setCreatedAt($faker->dateTimeBetween($auction->getStartedAt()->format('Y-m-d H:i:s'), 'now'))
                            ->setStatus(Bid::STATUS_ACTIVE)  // Tous les bids en 'active' par défaut
                            ->setIpAddress($faker->ipv6)
                            ->setBidder($faker->randomElement($users))
                            ->setProduct($product);

                        $manager->persist($bid);
                        $bids[] = $bid;
                    }

                    // Si on a des bids, on définit le dernier comme gagnant
                    if (!empty($bids)) {
                        $lastBid = end($bids);
                        $lastBid->setStatus(Bid::STATUS_WINNER);
                    }
                }
            }
        }

        $manager->flush();
    }
}