<?php declare(strict_types=1);

namespace App\Repository\Herbarinput;

use App\Entity\Jacq\Herbarinput\Literature;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class LiteratureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Literature::class);
    }

    public function getProtolog(int $id)
    {
        return $this->createQueryBuilder('a')
            ->select('GetProtolog(a.id) as protolog')
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()->getSingleScalarResult();
    }


}
