<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;

class ProductService
{
    // Le service de gestion des produits, utilisé pour récupérer les produits disponibles et les catégories
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository
    ) {}

    // Récupère tous les produits disponibles (stock > 0)
    /**
     * @return \App\Entity\Product[]
     */
    public function getAvailableProducts(): array
    {
        return $this->productRepository->findAvailable();
    }

    // Récupère les produits d'une catégorie spécifique
    /**
     * @return \App\Entity\Product[]
     */
    public function getProductsByCategory(int $categoryId): array
    {
        return $this->productRepository->findAvailableByCategory($categoryId);
    }

    // Récupère toutes les catégories
    /**
     * @return \App\Entity\Category[]
     */
    public function getAllCategories(): array
    {
        return $this->categoryRepository->findAll();
    }

    // Récupère un produit par son ID
    public function getProductById(int $id): ?Product
    {
        return $this->productRepository->find($id);
    }
}
