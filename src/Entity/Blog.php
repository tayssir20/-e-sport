<?php

namespace App\Entity;

use App\Repository\BlogRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Comment;
use App\Entity\Rating;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BlogRepository::class)]
class Blog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(min: 3, max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[Assert\NotBlank(message: "Le contenu (URL de l'image) est obligatoire")]
    #[Assert\Length(min: 10)]
    #[Assert\Url(message: "Le contenu doit être une URL valide")]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column]
    private int $commentCount = 0;

    #[ORM\OneToMany(mappedBy: 'blog', targetEntity: Comment::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $comments;

    /**
 * @ORM\OneToMany(mappedBy="blog", targetEntity=Rating::class, cascade={"persist","remove"}, orphanRemoval=true)
 */
#[ORM\OneToMany(mappedBy: 'blog', targetEntity: Rating::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
private Collection $ratings;
    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->ratings = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    // --- Getters & Setters ---
    public function getId(): ?int 
    { 
        return $this->id; 
    }
    
    public function getTitle(): ?string 
    { 
        return $this->title; 
    }
    
    public function setTitle(string $title): self 
    { 
        $this->title = $title; 
        return $this; 
    }
    
    public function getContent(): ?string 
    { 
        return $this->content; 
    }
    
    public function setContent(string $content): self 
    { 
        $this->content = $content; 
        return $this; 
    }
    
    public function getCreatedAt(): ?\DateTimeImmutable 
    { 
        return $this->createdAt; 
    }
    
    public function setCreatedAt(\DateTimeImmutable $createdAt): self 
    { 
        $this->createdAt = $createdAt; 
        return $this; 
    }
    
    public function getCategory(): ?string 
    { 
        return $this->category; 
    }
    
    public function setCategory(?string $category): self 
    { 
        $this->category = $category; 
        return $this; 
    }
    
    public function getImageName(): ?string 
    { 
        return $this->imageName; 
    }
    
    public function setImageName(?string $imageName): self 
    { 
        $this->imageName = $imageName; 
        return $this; 
    }
    
    public function getCommentCount(): ?int 
    { 
        return $this->commentCount; 
    }
    
    public function setCommentCount(int $commentCount): self 
    { 
        $this->commentCount = $commentCount; 
        return $this; 
    }
    
    public function getComments(): Collection 
    { 
        return $this->comments; 
    }

    // ======================= ⭐ RATING SYSTEM WITH ENTITY =======================
    
    /**
     * @return Collection<int, Rating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function addRating(Rating $rating): self
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setBlog($this);
        }
        return $this;
    }

    public function removeRating(Rating $rating): self
    {
        if ($this->ratings->removeElement($rating)) {
            if ($rating->getBlog() === $this) {
                $rating->setBlog(null);
            }
        }
        return $this;
    }

    /**
     * Get the average rating for this blog
     */
    public function getAverageRating(): float
    {
        if ($this->ratings->isEmpty()) {
            return 0.0;
        }

        $total = 0;
        foreach ($this->ratings as $rating) {
            $total += $rating->getValue();
        }

        return round($total / $this->ratings->count(), 1);
    }

    /**
     * Get the total number of ratings for this blog
     */
    public function getRatingCount(): int
    {
        return $this->ratings->count();
    }

    /**
     * Get rating value for a specific user
     */
    public function getUserRating(int $userId): ?int
    {
        foreach ($this->ratings as $rating) {
            if ($rating->getUser() && $rating->getUser()->getId() === $userId) {
                return $rating->getValue();
            }
        }
        return null;
    }

    /**
     * Check if a user has already rated this blog
     */
    public function hasUserRated(int $userId): bool
    {
        foreach ($this->ratings as $rating) {
            if ($rating->getUser() && $rating->getUser()->getId() === $userId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get rating distribution (for statistics)
     */
    public function getRatingDistribution(): array
    {
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        
        foreach ($this->ratings as $rating) {
            $value = $rating->getValue();
            if (isset($distribution[$value])) {
                $distribution[$value]++;
            }
        }
        
        return $distribution;
    }

    /**
     * Get all ratings with user details
     */
    public function getRatingsWithUsers(): array
    {
        $ratingsData = [];
        foreach ($this->ratings as $rating) {
            $ratingsData[] = [
                'user' => $rating->getUser(),
                'value' => $rating->getValue(),
                'createdAt' => $rating->getCreatedAt(),
                'updatedAt' => $rating->getUpdatedAt()
            ];
        }
        return $ratingsData;
    }

    /**
     * Get the percentage of each rating value
     */
    public function getRatingPercentages(): array
    {
        $total = $this->getRatingCount();
        if ($total === 0) {
            return [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        }

        $distribution = $this->getRatingDistribution();
        $percentages = [];
        
        foreach ($distribution as $stars => $count) {
            $percentages[$stars] = round(($count / $total) * 100, 1);
        }
        
        return $percentages;
    }
}