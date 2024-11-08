<?php

namespace App\Entity;

use App\Repository\ProfessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfessionRepository::class)]
class Profession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    /**
     * @var Collection<int, Celebrity>
     */
    #[ORM\OneToMany(targetEntity: Celebrity::class, mappedBy: 'profession')]
    private Collection $celebrities;

    public function __construct()
    {
        $this->celebrities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Celebrity>
     */
    public function getCelebrities(): Collection
    {
        return $this->celebrities;
    }

    public function addCelebrity(Celebrity $celebrity): static
    {
        if (!$this->celebrities->contains($celebrity)) {
            $this->celebrities->add($celebrity);
            $celebrity->setProfession($this);
        }

        return $this;
    }

    public function removeCelebrity(Celebrity $celebrity): static
    {
        if ($this->celebrities->removeElement($celebrity)) {
            // set the owning side to null (unless already changed)
            if ($celebrity->getProfession() === $this) {
                $celebrity->setProfession(null);
            }
        }

        return $this;
    }
}
