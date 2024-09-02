<?php

namespace App\Entity;

use App\Repository\MouchardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MouchardRepository::class)]
class Mouchard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    private $userCreated;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $Action;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $fromTable;

    #[ORM\Column(type: 'json', nullable: true)]
    private $observation = [];

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $created;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserCreated(): ?Users
    {
        return $this->userCreated;
    }

    public function setUserCreated(?Users $userCreated): self
    {
        $this->userCreated = $userCreated;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->Action;
    }

    public function setAction(?string $Action): self
    {
        $this->Action = $Action;

        return $this;
    }

    public function getFromTable(): ?string
    {
        return $this->fromTable;
    }

    public function setFromTable(?string $fromTable): self
    {
        $this->fromTable = $fromTable;

        return $this;
    }

    public function getObservation(): ?array
    {
        return $this->observation;
    }

    public function setObservation(?array $observation): self
    {
        $this->observation = $observation;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(?\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }
}
