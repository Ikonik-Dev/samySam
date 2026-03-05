<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** @extends AbstractCrudController<User> */
class UserCrudController extends AbstractCrudController
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            EmailField::new('email'),
            TextField::new('plainPassword', 'Password')
                ->setFormType(PasswordType::class)
                ->onlyOnForms(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance->getPlainPassword()) {
            $hashed = $this->passwordHasher->hashPassword($entityInstance, $entityInstance->getPlainPassword());
            $entityInstance->setPassword($hashed);
            $entityInstance->setPlainPassword(null);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance->getPlainPassword()) {
            $hashed = $this->passwordHasher->hashPassword($entityInstance, $entityInstance->getPlainPassword());
            $entityInstance->setPassword($hashed);
            $entityInstance->setPlainPassword(null);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
