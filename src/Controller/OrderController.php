<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\CartService;
use App\Service\OrderService;
use App\Service\StripeService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/order')]
class OrderController extends AbstractController
{
    #[Route('', name: 'app_order_index')]
    public function index(OrderService $orderService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('order/index.html.twig', [
            'orders' => $orderService->getOrdersForUser($user),
        ]);
    }

    #[Route('/checkout', name: 'app_order_checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        CartService $cartService,
        OrderService $orderService,
        StripeService $stripeService,
        LoggerInterface $logger,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        // Vérification CSRF: le token attendu est 'checkout'
        $submitted = (string) $request->request->get('_token', '');
        $token = new CsrfToken('checkout', $submitted);
        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_cart_index');
        }
        // 1. On récupère le panier
        $cart = $cartService->getCart();

        // Si le panier est vide, on renvoie à la boutique
        if (empty($cart)) {
            $this->addFlash('warning', 'Ton panier est vide.');
            return $this->redirectToRoute('app_shop_index');
        }

        // 2. On calcule le total
        $total = $cartService->getTotal();

        // 3. On crée la commande en base de données (avec les lignes du panier)
        // On encapsule la création dans un try/catch pour gérer proprement
        // les erreurs (stock insuffisant, erreur DB, etc.) et informer
        // l'utilisateur sans exposer d'informations sensibles.
        try {
            /** @var User $user */
            $user = $this->getUser();
            $order = $orderService->createOrder($user, $total, $cart);
        } catch (\Throwable $e) {
            // QUOI : on logge l'exception remontée lors de la création de la commande
            // POURQUOI : conserver le détail technique (stack trace, message) dans les logs
            //           permet d'investiguer les incidents sans exposer d'infos sensibles
            //           à l'utilisateur final.
            // COMMENT : on utilise le logger PSR-3 injecté pour écrire un message de niveau
            //           `error` avec l'exception en contexte. En production ce log pourra
            //           être collecté par un système de monitoring (Sentry/ELK/etc.).
            $logger->error('Erreur lors de la création de la commande', ['exception' => $e]);

            // Message utilisateur friendly et redirection vers le panier
            $this->addFlash('danger', 'Une erreur est survenue lors de la création de la commande. Veuillez vérifier votre panier et réessayer.');

            return $this->redirectToRoute('app_cart_index');
        }

        // 4. On demande à Stripe de préparer le paiement
        $stripeSession = $stripeService->createCheckoutSession(
            cartItems: $cart,
            orderReference: $order->getReference(),
            successUrl: $this->generateUrl('app_order_success', [
                'reference' => $order->getReference(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            cancelUrl: $this->generateUrl('app_order_cancel', [
                'reference' => $order->getReference(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        );

        // 5. On sauvegarde l'id de session Stripe dans la commande
        // sans changer le statut (toujours 'pending') — le paiement n'est pas encore confirmé
        $orderService->saveStripeSession($order, $stripeSession->id);

        // 6. On redirige le visiteur vers la page Stripe
        $redirectUrl = $stripeSession->url ?? '';
        return $this->redirect((string) $redirectUrl);
    }

    #[Route('/success/{reference}', name: 'app_order_success')]
    public function success(
        string $reference,
        OrderService $orderService,
        CartService $cartService,
    ): Response {
        // On retrouve la commande
        $order = $orderService->findByReference($reference);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        // Vérifier que la commande appartient bien à l'utilisateur connecté
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Cette commande ne vous appartient pas.');
        }

        // On marque comme payée
        $orderService->markAsPaid($order, $order->getStripeSessionId() ?? '');

        // On vide le panier
        $cartService->clear();

        return $this->render('order/success.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/cancel/{reference}', name: 'app_order_cancel')]
    public function cancel(
        string $reference,
        OrderService $orderService,
    ): Response {
        // On retrouve la commande
        $order = $orderService->findByReference($reference);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        // Vérifier que la commande appartient bien à l'utilisateur connecté
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Cette commande ne vous appartient pas.');
        }

        // On marque comme annulée
        $orderService->markAsCancelled($order);

        return $this->render('order/cancel.html.twig', [
            'order' => $order,
        ]);
    }
}
