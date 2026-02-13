<?php

namespace App\Entity;

use App\Repository\TournoiRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Entity\Equipe;

#[ORM\Entity(repositoryClass: TournoiRepository::class)]
class Tournoi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?\DateTime $date_debut = null;

    #[ORM\Column]
    private ?\DateTime $date_fin = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'tournois')]
    private ?Jeu $jeu = null;

    #[ORM\ManyToMany(targetEntity: Equipe::class, mappedBy: 'Tournois')]
    private Collection $equipes;

    #[ORM\Column(nullable: true)]
    private ?int $maxParticipants = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $cagnotte = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $dateInscriptionLimite = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $fraisInscription = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function __construct()
    {
        $this->equipes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTime $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->date_fin;
    }

    public function setDateFin(\DateTime $date_fin): static
    {
        $this->date_fin = $date_fin;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getJeu(): ?Jeu
    {
        return $this->jeu;
    }

    public function setJeu(?Jeu $jeu): static
    {
        $this->jeu = $jeu;

        return $this;
    }

    public function getEquipes(): Collection
    {
        return $this->equipes;
    }

    public function addEquipe(Equipe $equipe): static
    {
        if (!$this->equipes->contains($equipe)) {
            $this->equipes->add($equipe);
            $equipe->addTournoi($this);
        }

        return $this;
    }

    public function removeEquipe(Equipe $equipe): static
    {
        if ($this->equipes->removeElement($equipe)) {
            $equipe->removeTournoi($this);
        }

        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(?int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;

        return $this;
    }

    public function getCagnotte(): ?float
    {
        return $this->cagnotte;
    }

    public function setCagnotte(?float $cagnotte): static
    {
        $this->cagnotte = $cagnotte;

        return $this;
    }

    public function getDateInscriptionLimite(): ?\DateTime
    {
        return $this->dateInscriptionLimite;
    }

    public function setDateInscriptionLimite(?\DateTime $dateInscriptionLimite): static
    {
        $this->dateInscriptionLimite = $dateInscriptionLimite;

        return $this;
    }

    public function getFraisInscription(): ?float
    {
        return $this->fraisInscription;
    }

    public function setFraisInscription(?float $fraisInscription): static
    {
        $this->fraisInscription = $fraisInscription;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
