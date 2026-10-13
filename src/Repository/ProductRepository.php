<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findBestSellingProducts(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->select('p as product, COALESCE(SUM(oi.quantity), 0) as totalSales')
            ->leftJoin('App\\Entity\\OrderItem', 'oi', 'WITH', 'oi.product = p')
            ->leftJoin('oi.order', 'o')
            ->where('o.status = :status')
            ->setParameter('status', 'Completed')
            ->groupBy('p.id')
            ->orderBy('totalSales', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
