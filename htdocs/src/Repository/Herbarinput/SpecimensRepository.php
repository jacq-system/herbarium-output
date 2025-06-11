<?php declare(strict_types=1);

namespace App\Repository\Herbarinput;

use App\Entity\Jacq\Herbarinput\Specimens;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class SpecimensRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Specimens::class);
    }


    public function findSpecimenWithEagerLoadedRelations(int $id): ?Specimens
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('s', 'r1', 'r2', 'lq1', 'lq2')
            ->from(Specimens::class, 's')
            ->leftJoin('s.outgoingRelations', 'r1')
            ->leftJoin('s.incomingRelations', 'r2')
            ->leftJoin('r1.specimen2', 's1')
            ->leftJoin('r2.specimen1', 's2')
            ->leftJoin('r1.linkQualifier', 'lq1')
            ->leftJoin('r2.linkQualifier', 'lq2')
            ->where('s.id = :id')
            ->andWhere('s.accessibleForPublic = true')
            ->setParameter('id', $id);

       return $qb->getQuery()->getOneOrNullResult();
    }

}
