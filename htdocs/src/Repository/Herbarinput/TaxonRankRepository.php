<?php declare(strict_types=1);

namespace App\Repository\Herbarinput;

use App\Entity\Jacq\Herbarinput\TaxonRank;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class TaxonRankRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaxonRank::class);
    }

    public function getRankHierarchies(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.name, r.hierarchy')
            ->orderBy('r.hierarchy')
            ->getQuery()->getResult();
    }


}
