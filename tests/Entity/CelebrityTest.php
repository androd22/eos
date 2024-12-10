<?php

namespace App\Tests\Entity;

use App\Entity\Celebrity;
use App\Entity\Profession;
use App\Entity\Auction;
use PHPUnit\Framework\TestCase;

class CelebrityTest extends TestCase
{
    private Celebrity $celebrity;

    protected function setUp(): void
    {
        $this->celebrity = new Celebrity();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->celebrity->getId());
        $this->assertNull($this->celebrity->getStageName());
        $this->assertEmpty($this->celebrity->getAuctions());
    }

    public function testSetAndGetStageName(): void
    {
        $stageName = "Lady Gaga";
        $this->celebrity->setStageName($stageName);
        $this->assertEquals($stageName, $this->celebrity->getStageName());
    }

    public function testSetAndGetRealName(): void
    {
        $firstName = "Stefani";
        $lastName = "Germanotta";

        $this->celebrity->setRealFirstName($firstName);
        $this->celebrity->setRealLastName($lastName);

        $this->assertEquals($firstName, $this->celebrity->getRealFirstName());
        $this->assertEquals($lastName, $this->celebrity->getRealLastName());
    }

    public function testSetAndGetBiography(): void
    {
        $biography = "Une biographie test";
        $this->celebrity->setBiography($biography);
        $this->assertEquals($biography, $this->celebrity->getBiography());
    }

    public function testSetAndGetImage(): void
    {
        $image = "celebrity.jpg";
        $imageAlt = "Photo de la célébrité";

        $this->celebrity->setImage($image);
        $this->celebrity->setImageAlt($imageAlt);

        $this->assertEquals($image, $this->celebrity->getImage());
        $this->assertEquals($imageAlt, $this->celebrity->getImageAlt());
    }

    public function testAddAndRemoveAuction(): void
    {
        $auction = new Auction();

        $this->celebrity->addAuction($auction);
        $this->assertTrue($this->celebrity->getAuctions()->contains($auction));
        $this->assertSame($this->celebrity, $auction->getCelebrity());

        $this->celebrity->removeAuction($auction);
        $this->assertFalse($this->celebrity->getAuctions()->contains($auction));
        $this->assertNull($auction->getCelebrity());
    }

    public function testSetAndGetProfession(): void
    {
        $profession = new Profession();
        $this->celebrity->setProfession($profession);
        $this->assertSame($profession, $this->celebrity->getProfession());
    }

    public function testSetAndGetVideos(): void
    {
        $videoPres = "presentation.mp4";
        $videoPresAlt = "Vidéo de présentation";
        $videoThanks = "merci.mp4";
        $videoThanksAlt = "Vidéo de remerciement";

        $this->celebrity->setVideoPres($videoPres);
        $this->celebrity->setVideoPresAlt($videoPresAlt);
        $this->celebrity->setVideoThanks($videoThanks);
        $this->celebrity->setVideoThanksAlt($videoThanksAlt);

        $this->assertEquals($videoPres, $this->celebrity->getVideoPres());
        $this->assertEquals($videoPresAlt, $this->celebrity->getVideoPresAlt());
        $this->assertEquals($videoThanks, $this->celebrity->getVideoThanks());
        $this->assertEquals($videoThanksAlt, $this->celebrity->getVideoThanksAlt());
    }
}