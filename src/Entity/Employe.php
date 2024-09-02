<?php

namespace App\Entity;

use App\Repository\EmployeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployeRepository::class)]
class Employe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $prenom = null;

    #[ORM\OneToOne(mappedBy: 'street', cascade: ['persist', 'remove'])]
    private ?Userinfo $userinfo = null;

    #[ORM\OneToMany(mappedBy: 'employe', targetEntity: Contract::class)]
    private Collection $contracts;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(nullable: true)]
    private ?float $active = null;

    public function __construct()
    {
        $this->contracts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getUserinfo(): ?Userinfo
    {
        return $this->userinfo;
    }

    public function setUserinfo(?Userinfo $userinfo): static
    {
        // unset the owning side of the relation if necessary
        if ($userinfo === null && $this->userinfo !== null) {
            $this->userinfo->setStreet(null);
        }

        // set the owning side of the relation if necessary
        if ($userinfo !== null && $userinfo->getStreet() !== $this) {
            $userinfo->setStreet($this);
        }

        $this->userinfo = $userinfo;

        return $this;
    }

    /**
     * @return Collection<int, Contract>
     */
    public function getContracts(): Collection
    {
        return $this->contracts;
    }

    public function addContract(Contract $contract): static
    {
        if (!$this->contracts->contains($contract)) {
            $this->contracts->add($contract);
            $contract->setEmploye($this);
        }

        return $this;
    }

    public function removeContract(Contract $contract): static
    {
        if ($this->contracts->removeElement($contract)) {
            // set the owning side to null (unless already changed)
            if ($contract->getEmploye() === $this) {
                $contract->setEmploye(null);
            }
        }

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getActive(): ?float
    {
        return $this->active;
    }

    public function setActive(?float $active): static
    {
        $this->active = $active;

        return $this;
    }
}
