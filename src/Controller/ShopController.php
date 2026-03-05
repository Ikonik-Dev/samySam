<?php

namespace App\Controller;

use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShopController extends AbstractController
{
    public function __construct(
        private ProductService $productService
    ) {}

    #[Route('/shop', name: 'app_shop_index')]
    public function index(Request $request): Response
    {
        $categoryId = $request->query->getInt('category');

        $products = $categoryId
            ? $this->productService->getProductsByCategory($categoryId)
            : $this->productService->getAvailableProducts();

        return $this->render('shop/index.html.twig', [
            'products' => $products,
            'categories' => $this->productService->getAllCategories(),
            'currentCategory' => $categoryId,
        ]);
    }

    #[Route('/shop/product/{id}', name: 'app_shop_product')]
    public function show(int $id): Response
    {
        $product = $this->productService->getProductById($id);

        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        return $this->render('shop/product.html.twig', [
            'product' => $product,
        ]);
    }
}
