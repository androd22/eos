<?php

namespace App\Entity;

use App\Repository\CelebrityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CelebrityRepository::class)]
class Celebrity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $stageName = null;

    #[ORM\Column(length: 100)]
    private ?string $realFirstName = null;

    #[ORM\Column(length: 100)]
    private ?string $realLastName = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $biography = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\ManyToOne(inversedBy: 'celebrities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Profession $profession = null;

    /**
     * @var Collection<int, Auction>
     */
    #[ORM\OneToMany(targetEntity: Auction::class, mappedBy: 'celebrity')]
    private Collection $auctions;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $video_pres = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $video_pres_alt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $video_thanks = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $video_thanks_alt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $image_alt = null;

    public function __construct()
    {
        $this->auctions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStageName(): ?string
    {
        return $this->stageName;
    }

    public function setStageName(string $stageName): static
    {
        $this->stageName = $stageName;

        return $this;
    }

    public function getRealFirstName(): ?string
    {
        return $this->realFirstName;
    }

    public function setRealFirstName(string $realFirstName): static
    {
        $this->realFirstName = $realFirstName;

        return $this;
    }

    public function getRealLastName(): ?string
    {
        return $this->realLastName;
    }

    public function setRealLastName(string $realLastName): static
    {
        $this->realLastName = $realLastName;

        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(string $biography): static
    {
        $this->biography = $biography;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getProfession(): ?Profession
    {
        return $this->profession;
    }

    public function setProfession(?Profession $profession): static
    {
        $this->profession = $profession;

        return $this;
    }

    /**
     * @return Collection<int, Auction>
     */
    public function getAuctions(): Collection
    {
        return $this->auctions;
    }

    public function addAuction(Auction $auction): static
    {
        if (!$this->auctions->contains($auction)) {
            $this->auctions->add($auction);
            $auction->setCelebrity($this);
        }

        return $this;
    }

    public function removeAuction(Auction $auction): static
    {
        if ($this->auctions->removeElement($auction)) {
            // set the owning side to null (unless already changed)
            if ($auction->getCelebrity() === $this) {
                $auction->setCelebrity(null);
            }
        }

        return $this;
    }

    public function getVideoPres(): ?string
    {
        return $this->video_pres;
    }

    public function setVideoPres(?string $video_pres): static
    {
        $this->video_pres = $video_pres;

        return $this;
    }

    public function getVideoPresAlt(): ?string
    {
        return $this->video_pres_alt;
    }

    public function setVideoPresAlt(?string $video_pres_alt): static
    {
        $this->video_pres_alt = $video_pres_alt;

        return $this;
    }

    public function getVideoThanks(): ?string
    {
        return $this->video_thanks;
    }

    public function setVideoThanks(?string $video_thanks): static
    {
        $this->video_thanks = $video_thanks;

        return $this;
    }

    public function getVideoThanksAlt(): ?string
    {
        return $this->video_thanks_alt;
    }

    public function setVideoThanksAlt(?string $video_thanks_alt): static
    {
        $this->video_thanks_alt = $video_thanks_alt;

        return $this;
    }

    public function getImageAlt(): ?string
    {
        return $this->image_alt;
    }

    public function setImageAlt(?string $image_alt): static
    {
        $this->image_alt = $image_alt;

        return $this;
    }
}
