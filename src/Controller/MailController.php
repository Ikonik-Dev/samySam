<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MailController extends AbstractController
{
    public function __construct(
        private MailerService $mailerService
    ) {}

    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // DEBUG
            if (!$form->isValid()) {
                dd($form->getErrors(true));
            }

            $data = $form->getData();

            $this->mailerService->sendContactEmail(
                to: 'admin@shop.fr',
                subject: 'Nouveau message de ' . $data['name'],
                context: [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'message' => $data['message'],
                ]
            );

            $this->addFlash('success', 'Votre message a bien été envoyé !');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
