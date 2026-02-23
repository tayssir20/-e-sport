<?php

namespace App\Entity;

use App\Repository\RankingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RankingRepository::class)]
class Ranking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nm = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNm(): ?string
    {
        return $this->nm;
    }

    public function setNm(string $nm): static
    {
        $this->nm = $nm;

        return $this;
    }
}
