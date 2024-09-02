<?php

namespace App\Entity;

use App\Repository\CheckinoutRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CheckinoutRepository::class)]
class Checkinout
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $userid = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $memoinfo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $checktime = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $sn = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $checktype = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created = null;

    #[ORM\ManyToOne(inversedBy: 'checkinouts')]
    private ?Userinfo $userinfo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserid(): ?int
    {
        return $this->userid;
    }

    public function setUserid(?int $userid): static
    {
        $this->userid = $userid;

        return $this;
    }

    public function getMemoinfo(): ?string
    {
        return $this->memoinfo;
    }

    public function setMemoinfo(?string $memoinfo): static
    {
        $this->memoinfo = $memoinfo;

        return $this;
    }

    public function getChecktime(): ?\DateTimeInterface
    {
        return $this->checktime;
    }

    public function setChecktime(?\DateTimeInterface $checktime): static
    {
        $this->checktime = $checktime;

        return $this;
    }

    public function getSn(): ?string
    {
        return $this->sn;
    }

    public function setSn(?string $sn): static
    {
        $this->sn = $sn;

        return $this;
    }

    public function getChecktype(): ?string
    {
        return $this->checktype;
    }

    public function setChecktype(?string $checktype): static
    {
        $this->checktype = $checktype;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(?\DateTimeInterface $created): static
    {
        $this->created = $created;

        return $this;
    }

    public function getUserinfo(): ?Userinfo
    {
        return $this->userinfo;
    }

    public function setUserinfo(?Userinfo $userinfo): static
    {
        $this->userinfo = $userinfo;

        return $this;
    }
}
