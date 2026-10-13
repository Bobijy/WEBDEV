<?php

namespace App\Repository;

use App\Entity\UserAddress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserAddress>
 *
 * @method UserAddress|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserAddress|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserAddress[]    findAll()
 * @method UserAddress[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserAddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAddress::class);
    }
}
