<?php declare(strict_types=1);

namespace App\Repository\Herbarinput;

use App\Entity\Jacq\Herbarinput\Synonymy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;


class SynonymyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Synonymy::class);
    }

    /**
     * check if there are any classification children of the taxonID according to this reference
     */
    public function hasClassificationChildren(int $taxonID, int $referenceID): bool
    {

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('a.id')
            ->leftJoin('a.classification', 'c', Join::WITH, 'c.parentTaxonId = :taxon')
            ->leftJoin('a.literature', 'lit', Join::WITH, 'lit.id = :reference')
            ->andWhere('a.actualTaxonId IS NULL')
            ->setParameter('reference', $referenceID)
            ->setParameter('taxon', $taxonID);

        $child = $qb->getQuery()->getOneOrNullResult();

        if ($child !== false) {
            return true;
        } else {
            $qb->select('a.id')
                ->leftJoin('a.literature', 'lit', Join::WITH, 'lit.id = :reference')
                ->andWhere('a.actualTaxonId = :taxon')
                ->setParameter('reference', $referenceID)
                ->setParameter('taxon', $taxonID);

            $child = $qb->getQuery()->getOneOrNullResult();
            return (bool)$child;
        }
    }

}
