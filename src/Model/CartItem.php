<?php

namespace App\Model;

class CartItem
{
    private int $productId;
    private string $productName;
    /**
     * Prix stocké en centimes (int). Exemple: 8999 pour 89,99€
     */
    private int $price;
    private int $quantity;

    public function __construct(int $productId, string $productName, int $price)
    {
        $this->productId = $productId;
        $this->productName = $productName;
        $this->price = $price;
        $this->quantity = 1;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    /**
     * Retourne le prix unitaire en centimes (int)
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * Total en centimes
     */
    public function getTotal(): int
    {
        return $this->price * $this->quantity;
    }
}
