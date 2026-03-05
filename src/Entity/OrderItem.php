<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column(length: 255)]
    private string $productName;

    /** Prix unitaire en centimes */
    #[ORM\Column]
    private int $unitPriceCents;

    #[ORM\Column]
    private int $quantity = 1;

    /** Total en centimes (unitPriceCents * quantity) */
    #[ORM\Column]
    private int $totalCents;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct(Order $order, Product $product, string $productName, int $unitPriceCents, int $quantity = 1)
    {
        $this->order = $order;
        $this->product = $product;
        $this->productName = $productName;
        $this->unitPriceCents = $unitPriceCents;
        $this->quantity = $quantity;
        $this->totalCents = $unitPriceCents * $quantity;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getUnitPriceCents(): int
    {
        return $this->unitPriceCents;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getTotalCents(): int
    {
        return $this->totalCents;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
