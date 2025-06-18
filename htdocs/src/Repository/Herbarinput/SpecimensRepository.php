<?php declare(strict_types=1);

namespace App\Repository\Herbarinput;

use App\Entity\Jacq\Herbarinput\Institution;
use App\Entity\Jacq\Herbarinput\Specimens;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class SpecimensRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Specimens::class);
    }

    public function getExampleSpecimenWithImage(Institution $institution): ?Specimens
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s')
            ->join('s.herbCollection', 'c')
            ->join('c.institution', 'i')
            ->where('s.accessibleForPublic = true')
            ->andWhere('s.image = true')
            ->andWhere('i.id = :sourceId')
            ->setParameter('sourceId', $institution->getId())
            ->groupBy('s.id')
            ->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();

    }

    public function findAccessibleForPublic(int $id): ?Specimens
    {
        return $this->findOneBy(["id" => $id, 'accessibleForPublic' => true]);
    }

    public function specimensWithErrors(?int $sourceID): array
    {

        $queryBuilder = $this->createQueryBuilder('s')
            ->select('DISTINCT s')
            ->join('s.stableIdentifiers', 'sid')
            ->where('sid.identifier IS NULL');

        if ($sourceID !== null) {
            $queryBuilder = $queryBuilder
                ->join('s.herbCollection', 'col')
                ->andWhere('col.institution = :sourceID')
                ->setParameter('sourceID', $sourceID);
        }
        return $queryBuilder->getQuery()->getResult();
    }

}
