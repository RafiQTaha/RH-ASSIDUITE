<?php

namespace App\Entity;

use App\Repository\UsOperationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UsOperationRepository::class)]
class UsOperation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?UsSousModule $sousModule = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $designation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $classTag = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idTag = null;

    #[ORM\Column(nullable: true)]
    private ?int $ordre = null;

    #[ORM\Column(nullable: true)]
    private ?bool $align = null;

    #[ORM\ManyToMany(targetEntity: Users::class, inversedBy: 'operations')]
    private Collection $users;

    
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }
 
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSousModule(): ?UsSousModule
    {
        return $this->sousModule;
    }

    public function setSousModule(?UsSousModule $sousModule): self
    {
        $this->sousModule = $sousModule;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(?string $designation): self
    {
        $this->designation = $designation;

        return $this;
    }

    public function getClassTag(): ?string
    {
        return $this->classTag;
    }

    public function setClassTag(?string $classTag): self
    {
        $this->classTag = $classTag;

        return $this;
    }

    public function getIdTag(): ?string
    {
        return $this->idTag;
    }

    public function setIdTag(?string $idTag): self
    {
        $this->idTag = $idTag;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(?int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function isAlign(): ?bool
    {
        return $this->align;
    }

    public function setAlign(?bool $align): self
    {
        $this->align = $align;

        return $this;
    }

    /**
     * @return Collection<int, users>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(Users $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(Users $user): static
    {
        $this->users->removeElement($user);

        return $this;
    }

}