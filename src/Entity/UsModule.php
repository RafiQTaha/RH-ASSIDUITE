<?php

namespace App\Entity;

use App\Repository\UsModuleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UsModuleRepository::class)]
class UsModule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $designation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prefix = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\OneToMany(mappedBy: 'module', targetEntity: UsSousModule::class)]
    private Collection $sousModules;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $ordre;

    public function __construct()
    {
        $this->sousModules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(string $designation): self
    {
        $this->designation = $designation;

        return $this;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function setPrefix(?string $prefix): self
    {
        $this->prefix = $prefix;

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

    /**
     * @return Collection<int, UsSousModule>
     */
    public function getSousModules(): Collection
    {
        return $this->sousModules;
    }

    public function addSousModule(UsSousModule $sousModule): self
    {
        if (!$this->sousModules->contains($sousModule)) {
            $this->sousModules->add($sousModule);
            $sousModule->setModule($this);
        }

        return $this;
    }

    public function removeSousModule(UsSousModule $sousModule): self
    {
        if ($this->sousModules->removeElement($sousModule)) {
            // set the owning side to null (unless already changed)
            if ($sousModule->getModule() === $this) {
                $sousModule->setModule(null);
            }
        }

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
}
