<?php declare(strict_types=1);

namespace App\Repository\Herbarinput;

use App\Entity\Jacq\Herbarinput\Collector;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class CollectorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Collector::class);
    }

    public function getBloodhoundId(Collector $collector): ?string
    {
        return $this->createQueryBuilder('p')
            ->select('p.bloodHoundId')
            ->where('p.bloodHoundId LIKE :name')
            ->andWhere('p.id = :collector')
            ->setParameter('name', 'h%')
            ->setParameter('collector', $collector->getId())
            ->getQuery()->getSingleScalarResult();
    }

}
