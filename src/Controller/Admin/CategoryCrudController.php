<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
        // Dit à Easyadmin: ce controller gere l'entité Category
    }

    
    public function configureFields(string $pageName): iterable
    {
        // Quels champs afficher dans les listes et formulaires ?
        yield IdField::new('id')->hideOnForm();
        // hideform() -> l'ID s'affiche dans la liste mais pas dans le formulaire.
        yield TextField::new('name', 'nom de la categorie');
    }
    
}
