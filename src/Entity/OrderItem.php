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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $productName = null;

   #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
private ?string $productPrice = null;

    #[ORM\Column(nullable: true)]
    private ?int $quantity = null;

    public function getId(): ?int { return $this->id; }

    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): static { $this->order = $order; return $this; }

    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(?Product $product): static { $this->product = $product; return $this; }

    public function getProductName(): ?string { return $this->productName; }
    public function setProductName(string $v): static { $this->productName = $v; return $this; }
public function getProductPrice(): ?string { return $this->productPrice; }
public function setProductPrice(string|float|int $v): static { $this->productPrice = (string) $v; return $this; }

public function getSubtotal(): string
{
    return (string) (((float) $this->productPrice) * $this->quantity);
}

    public function getQuantity(): ?int { return $this->quantity; }
    public function setQuantity(int $v): static { $this->quantity = $v; return $this; }

    
}
