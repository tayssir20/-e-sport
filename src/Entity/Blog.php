<?php

namespace App\Entity;

use App\Repository\BlogRepository;
use Doctrine\Common\Collections\ArrayCollection; // ⚡ Added
use Doctrine\Common\Collections\Collection;     // ⚡ Added
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Comment;                           // ⚡ Added

#[ORM\Entity(repositoryClass: BlogRepository::class)]
class Blog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column]
    private ?int $commentCount = 0;

    // ------------------- RELATION TO COMMENTS -------------------
    #[ORM\OneToMany(mappedBy: 'blog', targetEntity: Comment::class, cascade: ['remove'])]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection(); // ⚡ Added
    }

    // --- GETTERS & SETTERS ---
    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): self { $this->content = $content; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): self { $this->category = $category; return $this; }
    public function getImageName(): ?string { return $this->imageName; }
    public function setImageName(?string $imageName): self { $this->imageName = $imageName; return $this; }
    public function getCommentCount(): ?int { return $this->commentCount; }
    public function setCommentCount(int $commentCount): self { $this->commentCount = $commentCount; return $this; }

    // ⚡ Added: getter for comments
    public function getComments(): Collection
    {
        return $this->comments;
    }
}
