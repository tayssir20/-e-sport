<?php

namespace App\Entity;

use App\Repository\MatchGameRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MatchGameRepository::class)]
class MatchGame
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateMatch = null;

    #[ORM\ManyToOne(targetEntity: Equipe::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Veuillez sélectionner l\'équipe 1.')]
    private ?Equipe $equipe1 = null;

    #[ORM\ManyToOne(targetEntity: Equipe::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Veuillez sélectionner l\'équipe 2.')]
    private ?Equipe $equipe2 = null;

    #[ORM\Column]
    private ?int $scoreTeam1 = null;

    #[ORM\Column]
    private ?int $scoreTeam2 = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    // 🔥 relation avec Tournoi
    #[ORM\ManyToOne(targetEntity: Tournoi::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Veuillez sélectionner un tournoi.')]
    private ?Tournoi $Tournoi = null;
public function __construct()
    {
        $this->statut = 'scheduled';
    }

    // getters setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateMatch(): ?\DateTimeInterface
    {
        return $this->dateMatch;
    }

    public function setDateMatch(\DateTimeInterface $dateMatch): self
    {
        $this->dateMatch = $dateMatch;
        return $this;
    }

    public function getEquipe1(): ?Equipe
    {
        return $this->equipe1;
    }

    public function setEquipe1(?Equipe $equipe1): self
    {
        $this->equipe1 = $equipe1;
        return $this;
    }

    public function getEquipe2(): ?Equipe
    {
        return $this->equipe2;
    }

    public function setEquipe2(?Equipe $equipe2): self
    {
        $this->equipe2 = $equipe2;
        return $this;
    }

    public function getScoreTeam1(): ?int
    {
        return $this->scoreTeam1;
    }

    public function setScoreTeam1(int $scoreTeam1): self
    {
        $this->scoreTeam1 = $scoreTeam1;
        return $this;
    }

    public function getScoreTeam2(): ?int
    {
        return $this->scoreTeam2;
    }

    public function setScoreTeam2(int $scoreTeam2): self
    {
        $this->scoreTeam2 = $scoreTeam2;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getTournoi(): ?Tournoi
    {
        return $this->Tournoi;
    }

    public function setTournoi(?Tournoi $Tournoi): self
    {
        $this->Tournoi = $Tournoi;
        return $this;
    }
}
