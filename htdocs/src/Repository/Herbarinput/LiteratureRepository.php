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
        return $this->createQueryBuilder('l')
            ->select('GetProtolog(l.id) as protolog')
            ->andWhere('l.id = :id')
            ->setParameter('id', $id)
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * get all citations which belong to the given periodical
     */
    public function getChildrenReferences(int $referenceID): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('GetProtolog(l.id) AS referenceName')
            ->addSelect('l.id AS referenceID')
            ->join('l.synonymies', 's')
            ->join('s.classification', 'c')
            ->where('l.periodical = :referenceID')
            ->groupBy('l.id')
            ->orderBy('referenceName')
            ->setParameter('referenceID', $referenceID);
        return $qb->getQuery()->getArrayResult();

    }
}
