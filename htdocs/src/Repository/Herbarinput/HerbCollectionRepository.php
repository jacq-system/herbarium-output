<?php declare(strict_types=1);

namespace App\Repository\Herbarinput;

use App\Entity\Jacq\Herbarinput\HerbCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class HerbCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HerbCollection::class);
    }

    public function getAllAsPairs(): array
    {
        $results = $this->createQueryBuilder('h')
            ->select('h.id, h.name')
            ->join('h.specimens', 's')
            ->groupBy('h.id')
            ->orderBy('h.name')
            ->getQuery()->getArrayResult();

        return array_column($results, 'name', 'id');

    }

    public function getAllFromHerbariumAsPairs(?int $herbariumAbbreviation): array
    {
        $qb = $this->createQueryBuilder('h')
            ->select('h.id, h.name')
            ->join('h.specimens', 's')
            ->groupBy('h.id')
            ->orderBy('h.name');

        if ($herbariumAbbreviation !== null) {
            $qb->join('h.institution', 'i')
                ->where('i.id = :herbarium')
                ->setParameter(':herbarium', $herbariumAbbreviation);
        }

        return $qb->getQuery()->getResult();
    }


}
