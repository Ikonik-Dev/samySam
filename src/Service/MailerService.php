<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {}

    public function sendContactEmail(
        string $to,
        string $subject,
        array $context = []
    ): void {
        $this->send('mail/contact.html.twig', $to, $subject, $context);
    }

    /**
     * Email de bienvenue envoyé à l'utilisateur après son inscription.
     */
    public function sendWelcomeEmail(string $to): void
    {
        $this->send('mail/welcome.html.twig', $to, 'Bienvenue sur Shop !', [
            'email' => $to,
        ]);
    }

    /**
     * Email récapitulatif de commande envoyé à l'utilisateur.
     */
    public function sendOrderConfirmationEmail(Order $order): void
    {
        $user = $order->getUser();
        if (!$user) {
            return;
        }

        $this->send(
            'mail/order_confirmation.html.twig',
            $user->getEmail(),
            'Confirmation de commande ' . $order->getReference(),
            ['order' => $order]
        );
    }

    private function send(string $template, string $to, string $subject, array $context): void
    {
        $htmlContent = $this->twig->render($template, $context);

        $email = (new Email())
            ->from('noreply@shop.fr')
            ->to($to)
            ->subject($subject)
            ->html($htmlContent)
            ->text(strip_tags($htmlContent));

        $this->mailer->send($email);
    }
}
