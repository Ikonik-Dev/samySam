<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(
        private CartService $cartService
    ){}

    #[Route('/cart', name: 'app_cart_index')]
    public function index(): Response
    {

        return $this->render('cart/index.html.twig', [
            'items' => $this->cartService->getCart(),
            'total' => $this->cartService->getTotal(),
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(int $id): Response
    {
        $this->cartService->addItem($id);
        $this->addFlash('success', 'Produit ajouté au panier !');

        return $this->redirectToRoute('app_shop_index');
    }

     #[Route('/cart/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $id): Response
    {
        $this->cartService->removeItem($id);
        $this->addFlash('info', 'Produit retiré du panier.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/cart/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(): Response
    {
        $this->cartService->clear();
        $this->addFlash('warning', 'Panier vidé.');

        return $this->redirectToRoute('app_cart_index');
    }
}
