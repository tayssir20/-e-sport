<?php

namespace App\Entity;

use App\Repository\StreamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute\UploadableField;
use Vich\UploaderBundle\Mapping\Attribute\Uploadable;

#[ORM\Entity(repositoryClass: StreamRepository::class)]
#[Uploadable] // version moderne à utiliser, plus de dépréciation
class Stream
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, StreamReaction>
     */
    #[ORM\OneToMany(mappedBy: 'stream', targetEntity: StreamReaction::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
private Collection $reactions;

    // Champ pour VichUploader
    #[UploadableField(mapping: 'stream_video', fileNameProperty: 'url')]
    private ?File $videoFile = null;

    public function __construct()
    {
        $this->reactions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    // ---------------- Getters & Setters ----------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

   public function setUrl(?string $url): static
{
    $this->url = $url;
    return $this;
}

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, StreamReaction>
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function addReaction(StreamReaction $reaction): static
    {
        if (!$this->reactions->contains($reaction)) {
            $this->reactions->add($reaction);
            $reaction->setStream($this);
        }

        return $this;
    }

    public function removeReaction(StreamReaction $reaction): static
    {
        if ($this->reactions->removeElement($reaction)) {
            if ($reaction->getStream() === $this) {
                $reaction->setStream(null);
            }
        }

        return $this;
    }

    // ---------------- VichUploader File ----------------

    public function setVideoFile(?File $videoFile = null): void
    {
        $this->videoFile = $videoFile;

        if ($videoFile) {
            $this->createdAt = new \DateTimeImmutable(); // déclenchement de l'événement Doctrine
        }
    }

    public function getVideoFile(): ?File
    {
        return $this->videoFile;
    }
}