<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\OrderItem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class OrderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Crée une nouvelle commande en base de données avec ses OrderItem
     * et décrémente le stock des produits concernés.
     *
     * @param User $user l'utilisateur qui passe la commande
     * @param int $totalAmount total en centimes
     * @param \App\Model\CartItem[] $cartItems tableau de App\Model\CartItem
     *
     * @throws \RuntimeException si stock insuffisant ou erreur DB
     */
    public function createOrder(User $user, int $totalAmount, array $cartItems): Order
    {
        // On démarre explicitement une transaction SQL pour garantir
        // l'atomicité : soit toutes les opérations (création de la
        // commande, lignes, décrémentation des stocks) réussissent,
        // soit aucune n'est appliquée (rollback en cas d'erreur).
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            $order = new Order();
            $order->setUser($user);
            $order->setTotalAmount($totalAmount);

            $this->entityManager->persist($order);

            // Pour chaque élément du panier :
            // - on récupère le produit en base
            // - on vérifie que le stock est suffisant
            // - on décrémente le stock (mutation persistée)
            // - on crée un OrderItem (snapshot du nom et du prix en centimes)
            // Cette logique doit être atomique pour éviter les conditions
            // de concurrence et l'incohérence du stock.
            //
            // Remarque : en environnement à fort trafic il vaut mieux
            // utiliser un verrou au niveau DB (SELECT ... FOR UPDATE) ou
            // une stratégie optimistic/pessimistic locking selon le SGBD.
            // Ici on se contente d'une transaction simple.
            //
            // Pour chaque item :
            foreach ($cartItems as $cartItem) {
                // Récupération du produit avec verrou pessimiste en écriture
                // pour éviter les conditions de concurrence (oversell).
                // Utilisation de EntityManager::find avec lock mode PESSIMISTIC_WRITE
                // nécessite d'être dans une transaction (on l'a démarrée ci‑dessus).
                $product = $this->entityManager->find(
                    Product::class,
                    $cartItem->getProductId(),
                    \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE
                );

                if (!$product) {
                    throw new \RuntimeException(sprintf('Produit introuvable (id: %s)', $cartItem->getProductId()));
                }

                $quantity = $cartItem->getQuantity();

                // Vérification du stock disponible (évite les ventes négatives)
                if ($product->getStock() < $quantity) {
                    throw new \RuntimeException(sprintf('Stock insuffisant pour le produit %s', $product->getName()));
                }

                // Décrémentation du stock : on met à jour la valeur en mémoire
                // puis on persiste l'entité afin que Doctrine génère l'UPDATE
                // correspondant lors du flush. Cette opération fait partie de
                // la transaction commencée plus haut.
                $product->setStock($product->getStock() - $quantity);
                $this->entityManager->persist($product);

                // Créer la ligne de commande (snapshot du nom et du prix en centimes)
                $orderItem = new OrderItem(
                    $order,
                    $product,
                    (string) $product->getName(),
                    (int) $product->getPriceInCents(),
                    $quantity
                );

                $this->entityManager->persist($orderItem);
                $order->addOrderItem($orderItem);
            }

            $this->entityManager->flush();
            $conn->commit();

            return $order;
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * Trouve une commande par sa référence
     */
    public function findByReference(string $reference): ?Order
    {
        return $this->entityManager
            ->getRepository(Order::class)
            ->findOneBy(['reference' => $reference]);
    }

    /**
     * Récupère toutes les commandes d'un utilisateur, triées par date décroissante.
     *
     * @return Order[]
     */
    public function getOrdersForUser(User $user): array
    {
        return $this->entityManager
            ->getRepository(Order::class)
            ->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    /**
     * Sauvegarde l'id de session Stripe sans changer le statut.
     * À appeler juste après la création de la session Stripe,
     * AVANT que le visiteur ait payé.
     */
    public function saveStripeSession(Order $order, string $stripeSessionId): void
    {
        $order->setStripeSessionId($stripeSessionId);
        $this->entityManager->flush();
    }

    /**
     * Marque une commande comme payée
     */
    public function markAsPaid(Order $order, string $stripeSessionId): void
    {
        // Idempotence : si la commande est déjà marquée comme payée, ne rien faire
        if ($order->getStatus() === 'paid') {
            return;
        }

        // On met à jour le statut et on stocke l'id de session Stripe pour
        // permettre la corrélation et l'idempotence côté webhook.
        $order->setStatus('paid');
        $order->setStripeSessionId($stripeSessionId);

        $this->entityManager->flush();
    }

    /**
     * Marque une commande comme annulée
     */
    public function markAsCancelled(Order $order): void
    {
        // Si la commande est déjà annulée, on ne fait rien (idempotence)
        if ($order->getStatus() === 'cancelled') {
            return;
        }

        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            // Restaurer le stock pour chaque ligne de commande
            foreach ($order->getOrderItems() as $item) {
                $product = $item->getProduct();
                if (!$product) {
                    // Si le produit n'existe plus, on ignore mais on continue
                    continue;
                }

                // Incrémentation du stock selon la quantité commandée
                $product->setStock($product->getStock() + $item->getQuantity());
                $this->entityManager->persist($product);
            }

            // Met à jour le statut de la commande
            $order->setStatus('cancelled');
            $this->entityManager->persist($order);

            $this->entityManager->flush();
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}
