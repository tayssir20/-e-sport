<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column(length: 255)]
    private ?string $productName = null;

    #[ORM\Column]
    private ?float $productPrice = null;

    #[ORM\Column]
    private ?int $quantity = null;

    public function getId(): ?int { return $this->id; }

    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): static { $this->order = $order; return $this; }

    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(?Product $product): static { $this->product = $product; return $this; }

    public function getProductName(): ?string { return $this->productName; }
    public function setProductName(string $v): static { $this->productName = $v; return $this; }

    public function getProductPrice(): ?float { return $this->productPrice; }
    public function setProductPrice(float $v): static { $this->productPrice = $v; return $this; }

    public function getQuantity(): ?int { return $this->quantity; }
    public function setQuantity(int $v): static { $this->quantity = $v; return $this; }

    public function getSubtotal(): float
    {
        return $this->productPrice * $this->quantity;
    }
}
