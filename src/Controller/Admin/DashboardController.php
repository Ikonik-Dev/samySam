<?php

namespace App\Controller\Admin;

use App\Controller\Admin\CategoryCrudController;
use App\Controller\Admin\ProductCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        // La page d'accueil de l'admin
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SamySam dashboard');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Categories', 'fa fa-tag');
        yield MenuItem::linkTo(ProductCrudController::class, 'Produits', 'fa fa-box');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');
        // Lien de redirection vers la page d'accueil du site
        yield MenuItem::linkToRoute('retour au site', 'fa fa-undo', 'app_shop_index');
        // Lien de déconnexion
        yield MenuItem::linkToLogout('Se déconnecter', 'fa fa-sign-out');
    }
}
