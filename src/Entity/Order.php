<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $orderNumber = null;

    #[ORM\Column]
    private ?float $totalPrice = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\Column(length: 20)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 20)]
    private ?string $phone = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'pending';
        $this->orderNumber = 'ORD-' . strtoupper(uniqid());
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getOrderNumber(): ?string { return $this->orderNumber; }
    public function setOrderNumber(string $v): static { $this->orderNumber = $v; return $this; }

    public function getTotalPrice(): ?float { return $this->totalPrice; }
    public function setTotalPrice(float $v): static { $this->totalPrice = $v; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }

    public function getStripeSessionId(): ?string { return $this->stripeSessionId; }
    public function setStripeSessionId(?string $v): static { $this->stripeSessionId = $v; return $this; }

    public function getStripePaymentIntentId(): ?string { return $this->stripePaymentIntentId; }
    public function setStripePaymentIntentId(?string $v): static { $this->stripePaymentIntentId = $v; return $this; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $v): static { $this->firstName = $v; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $v): static { $this->lastName = $v; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $v): static { $this->email = $v; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(string $v): static { $this->address = $v; return $this; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(string $v): static { $this->city = $v; return $this; }

    public function getPostalCode(): ?string { return $this->postalCode; }
    public function setPostalCode(string $v): static { $this->postalCode = $v; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(string $v): static { $this->phone = $v; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $v): static { $this->createdAt = $v; return $this; }

    public function getPaidAt(): ?\DateTimeImmutable { return $this->paidAt; }
    public function setPaidAt(?\DateTimeImmutable $v): static { $this->paidAt = $v; return $this; }

    public function getItems(): Collection { return $this->items; }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }
        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }
        return $this;
    }
}
