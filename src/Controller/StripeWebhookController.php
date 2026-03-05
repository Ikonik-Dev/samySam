<?php

namespace App\Controller;

use App\Service\OrderService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StripeWebhookController extends AbstractController
{
    public function __construct(
        private OrderService $orderService,
        private LoggerInterface $logger
    ) {}

    #[Route('/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        // Récupérer le secret du webhook depuis l'environnement
        $webhookSecretRaw = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? getenv('STRIPE_WEBHOOK_SECRET');
        $webhookSecret = is_string($webhookSecretRaw) ? $webhookSecretRaw : '';

        $payload = (string) $request->getContent();
        $sigHeaderRaw = $request->headers->get('stripe-signature', '');
        $sigHeader = is_string($sigHeaderRaw) ? $sigHeaderRaw : '';

        if (empty($webhookSecret)) {
            // Environnement mal configuré : log et rejette
            $this->logger->error('Stripe webhook secret not configured');
            return new Response('Webhook secret not configured', 500);
        }

        try {
            // Vérification de la signature du webhook ; ceci protège contre
            // les requêtes frauduleuses. Utilise la librairie stripe-php.
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
            /** @var \Stripe\Event $event */
        } catch (\UnexpectedValueException $e) {
            // Payload invalide
            $this->logger->warning('Invalid Stripe payload', ['exception' => $e]);
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Signature invalide
            $this->logger->warning('Invalid Stripe signature', ['exception' => $e]);
            return new Response('Invalid signature', 400);
        }

        $type = $event->type;

        // Traiter seulement les événements pertinents
        switch ($type) {
            case 'checkout.session.completed':
                /** @var \Stripe\StripeObject $session */
                $session = $event->data->object;

                // Récupération sécurisée de la metadata (peut être tableau ou objet)
                $orderReference = null;
                $metadata = $session->metadata ?? null;
                if (is_array($metadata)) {
                    $orderReference = $metadata['order_reference'] ?? null;
                } elseif (is_object($metadata)) {
                    $orderReference = $metadata->order_reference ?? null;
                }

                $sessionId = $session->id ?? null;

                if (!$orderReference) {
                    $this->logger->error('Stripe session missing order_reference in metadata', ['session' => $session]);
                    return new Response('Missing order reference', 400);
                }

                if (!is_string($orderReference)) {
                    $this->logger->error('Stripe session order_reference is not a string', ['session' => $session]);
                    return new Response('Invalid order reference', 400);
                }

                $order = $this->orderService->findByReference($orderReference);
                if (!$order) {
                    $this->logger->error('Order not found for Stripe session', ['order_reference' => $orderReference]);
                    return new Response('Order not found', 404);
                }

                // Idempotence : markAsPaid gère le cas où la commande est déjà payée
                try {
                    $this->orderService->markAsPaid($order, (string) $sessionId);
                } catch (\Throwable $e) {
                    $this->logger->error('Error marking order as paid', ['exception' => $e, 'order' => $orderReference]);
                    return new Response('Error', 500);
                }

                $this->logger->info('Order marked as paid via Stripe webhook', ['order' => $orderReference, 'session' => $sessionId]);
                return new Response('Received', 200);

            case 'checkout.session.expired':
            case 'checkout.session.async_payment_failed':
            case 'payment_intent.payment_failed':
                // Pour ces événements on peut annuler la commande si elle est en pending
                /** @var \Stripe\StripeObject $session */
                $session = $event->data->object;
                $orderReference = null;
                $metadata = $session->metadata ?? null;
                if (is_array($metadata)) {
                    $orderReference = $metadata['order_reference'] ?? null;
                } elseif (is_object($metadata)) {
                    $orderReference = $metadata->order_reference ?? null;
                }

                if ($orderReference) {
                    if (!is_string($orderReference)) {
                        break;
                    }
                    $order = $this->orderService->findByReference($orderReference);
                    if ($order) {
                        try {
                            $this->orderService->markAsCancelled($order);
                            $this->logger->info('Order cancelled via Stripe webhook', ['order' => $orderReference, 'event' => $type]);
                        } catch (\Throwable $e) {
                            $this->logger->error('Error cancelling order via webhook', ['exception' => $e, 'order' => $orderReference]);
                            return new Response('Error', 500);
                        }
                    }
                }

                return new Response('Received', 200);

            default:
                // Ignorer les autres événements
                $this->logger->debug('Unhandled Stripe event', ['type' => $type]);
                return new Response('Ignored', 200);
        }

        // Fallback (PHPStan may not be able to infer that switch covers all paths)
        return new Response('Ignored', 200);
    }
}
