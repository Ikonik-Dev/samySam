<?php

namespace App\Service;

use App\Model\CartItem;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    // Clé de session pour stocker le panier
    private const CART_KEY = 'shopping_cart';

    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepository
    ) {}



    public function addItem(int $productId): void
    {
        $cart = $this->getCart();

        $product = $this->productRepository->find($productId);

        if (!$product || $product->getStock() <= 0) {
            return;
        }

        if (isset($cart[$productId])) {
            // Déjà dans le panier → on augmente la quantité
            $cart[$productId]->setQuantity(
                $cart[$productId]->getQuantity() + 1
            );
        } else {
            // Nouveau → on crée une ligne (on fournit le prix en centimes)
            $cart[$productId] = new CartItem(
                $productId,
                (string) $product->getName(),
                (int) $product->getPriceInCents()
            );
        }
        $this->save($cart);
    }

    /**
     * @return \App\Model\CartItem[]|array<int, \App\Model\CartItem>
     */
    public function getCart(): array
    {
        $cart = $this->requestStack
            ->getSession()
            // Retourne un tableau de CartItem ou un tableau vide si le panier n'existe pas encore
            ->get(self::CART_KEY, []);

        if (!is_array($cart)) {
            return [];
        }

        $filtered = [];
        foreach ($cart as $k => $v) {
            if ($v instanceof CartItem) {
                $filtered[(int) $k] = $v;
            }
        }

        return $filtered;
    }

    public function removeItem(int $productId): void
    {
        $cart = $this->getCart();
        unset($cart[$productId]);
        $this->save($cart);
    }

    public function clear(): void
    {
        $this->save([]);
    }

    public function getTotal(): int
    {
        $total = 0;
        foreach ($this->getCart() as $item) {
            $total += $item->getTotal();
        }
        return $total;
    }

    public function getCount(): int
    {
        $count = 0;
        foreach ($this->getCart() as $item) {
            $count += $item->getQuantity();
        }
        return $count;
    }

    /**
     * @param \App\Model\CartItem[]|array<int, \App\Model\CartItem> $cart
     */
    private function save(array $cart): void
    {
        // Normaliser les clés en entiers pour satisfaire PHPStan
        $normalized = [];
        foreach ($cart as $k => $v) {
            $normalized[(int) $k] = $v;
        }

        $this->requestStack
            ->getSession()
            ->set(self::CART_KEY, $normalized);
    }
};
