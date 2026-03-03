<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        // ! Creer l'admin
        $admin = new User();
        $admin->setEmail('admin@shop.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        // On hash le mot de pass avant de le stocker
        $admin->setPassword(
            $this->hasher->hashPassword($admin, 'admin123')
        );

        $manager->persist($admin);

        // --- Créer des catégories ---
        $cat1 = new Category();
        $cat1->setName('Informatique');
        $manager->persist($cat1);

        $cat2 = new Category();
        $cat2->setName('Mobilier');
        $manager->persist($cat2);

        // --- Créer des produits ---
        $p1 = new Product();
        $p1->setName('Clavier mécanique');
        $p1->setDescription('Clavier ergonomique pour les longues sessions');
        $p1->setPrice(89.99);
        $p1->setStock(15);
        $p1->setCategory($cat1);
        $manager->persist($p1);

        $p2 = new Product();
        $p2->setName('Souris sans fil');
        $p2->setDescription('Précise et silencieuse');
        $p2->setPrice(45.00);
        $p2->setStock(30);
        $p2->setCategory($cat1);
        $manager->persist($p2);

        $p3 = new Product();
        $p3->setName('Chaise de bureau');
        $p3->setDescription('Confortable pour travailler toute la journée');
        $p3->setPrice(320.00);
        $p3->setStock(5);
        $p3->setCategory($cat2);
        $manager->persist($p3);

        $manager->flush();
    }
}
