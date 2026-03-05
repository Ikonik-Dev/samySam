<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?string $price = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $stock = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Category $category = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    /**
     * Retourne le prix sous forme de float (ex: 89.99) pour affichage.
     */
    public function getPrice(): ?float
    {
        return $this->price !== null ? (float) $this->price : null;
    }

    /**
     * Définit le prix. Accepte float ou string. Stocke au format décimal (2 décimales).
     */
    public function setPrice(float|string $price): static
    {
        $this->price = number_format((float) $price, 2, '.', '');

        return $this;
    }

    /**
     * Retourne le prix en centimes (int) pour les API externes (Stripe).
     */
    public function getPriceInCents(): ?int
    {
        if (null === $this->price) {
            return null;
        }

        return (int) round(((float) $this->price) * 100);
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }
}
