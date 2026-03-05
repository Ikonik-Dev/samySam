<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */

    /**
     * Produits en stock, avec leur catégorie chargée.
     *
     * @return Product[]
     */
    public function findAvailable(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->addSelect('c')
            ->where('p.stock > 0')
            ->orderBy('p.name', 'ASC')
            ->getQuery();

        /** @var Product[] $results */
        $results = $qb->getResult();

        return $results;
    }

    /**
     * Produits en stock d'une catégorie donnée.
     *
     * @return Product[]
     */
    public function findAvailableByCategory(int $categoryId): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->addSelect('c')
            ->where('p.stock > 0')
            ->andWhere('p.category = :catId')
            ->setParameter('catId', $categoryId)
            ->orderBy('p.name', 'ASC')
            ->getQuery();

        /** @var Product[] $results */
        $results = $qb->getResult();

        return $results;
    }
}
