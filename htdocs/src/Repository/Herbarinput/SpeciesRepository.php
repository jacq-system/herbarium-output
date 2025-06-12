<?php declare(strict_types=1);

namespace App\Repository\Herbarinput;

use App\Entity\Jacq\Herbarinput\Species;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class SpeciesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Species::class);
    }

    public function autocompleteStartsWith(string $term): array
    {
        $words = preg_split('/\s+/', $term, 2);
        if (empty($words)) {
            return [];
        }

        $baseQueryBuilder = $this->createQueryBuilder('s')
            ->join('s.genus', 'genus')
            ->select('s.id, GetScientificName(s.id, 0) AS scientificName')
            ->where('s.external = 0')
            ->andWhere('genus.name like :genus')
            ->setParameter('genus', $words[0] . '%')
            ->having('scientificName != \'\'')
            ->orderBy('scientificName');

        if (count($words) === 2) {
            $result = $baseQueryBuilder
                ->leftJoin('s.epithetSpecies', 'epithet')
                ->andWhere('epithet.name like :epithet')
                ->setParameter('epithet', $words[1] . '%')
                ->getQuery()->getResult();
            if (empty($result)) {
                $result = $baseQueryBuilder
                    ->leftJoin('s.epithetSubspecies', 'epithetSubspecies')
                    ->leftJoin('s.epithetVariety', 'epithetVariety')
                    ->leftJoin('s.epithetSubvariety', 'epithetSubvariety')
                    ->leftJoin('s.epithetForma', 'epithetForma')
                    ->leftJoin('s.epithetSubforma', 'epithetSubforma')
                    ->andWhere(
                        $baseQueryBuilder->expr()->orX(
                            'epithetSubspecies.name like :epithet',
                            'epithetVariety.name like :epithet',
                            'epithetSubvariety.name like :epithet',
                            'epithetForma.name like :epithet',
                            'epithetSubforma.name like :epithet'
                        )
                    )
                    ->setParameter('epithet', $words[1] . '%')
                    ->getQuery()->getResult();
            }
            return $result;
        } else {
            return $baseQueryBuilder
                ->andWhere('s.epithetSpecies IS NULL')
                ->getQuery()->getResult();
        }

    }


}
