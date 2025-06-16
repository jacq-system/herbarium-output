<?php declare(strict_types=1);

namespace App\Repository\Herbarinput;

use App\Entity\Jacq\Herbarinput\Institution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class InstitutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Institution::class);
    }

    public function getAllPairsCodeName(): array
    {

        $qb = $this->createQueryBuilder('i')
            ->select('DISTINCT i.id AS id, CONCAT(i.code, \' - \', i.name2) AS name')
            ->join('i.collections', 'c')
            ->join('c.specimens', 's')
            ->orderBy('name');
        $results = $qb->getQuery()->getArrayResult();
        return array_column($results, 'name', 'id');

    }

}
