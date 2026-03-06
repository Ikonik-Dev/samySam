<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 2, max: 50, minMessage: 'Le nom doit contenir au moins 2 caractères', maxMessage: 'Le nom ne doit pas dépasser 50 caractères'),
                ]
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ]
            ])
            ->add('message', TextareaType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 10, max: 1000, minMessage: 'Le message doit contenir au moins 10 caractères', maxMessage: 'Le message ne doit pas dépasser 1000 caractères'),
                ]
            ]);
    }

    // je n'ai pas besoin de lier ce formulaire à une entité spécifique, donc je ne vais pas définir de classe de données dans les options du formulaire
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => Contact::class, // si j'avais une entité Contact, je pourrais la lier ici
        ]);
    }
}
