<?php declare(strict_types=1);

namespace App\Repository\Herbarinput;

use App\Entity\Jacq\Herbarinput\Synonymy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
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

        $qb = $this->createQueryBuilder('a');

        $qb->select('1')
            ->leftJoin('a.classification', 'c', Join::WITH, 'c.parentTaxonId = :taxon')
            ->leftJoin('a.literature', 'lit', Join::WITH, 'lit.id = :reference')
            ->andWhere('a.actualTaxonId IS NULL')
            ->setParameter('reference', $referenceID)
            ->setParameter('taxon', $taxonID)
            ->setMaxResults(1);

        try {
            $qb->getQuery()->getSingleScalarResult();
            return true;
        } catch (NoResultException) {
            $qb = $this->createQueryBuilder('a');
            $qb->select('1')
                ->leftJoin('a.literature', 'lit', Join::WITH, 'lit.id = :reference')
                ->andWhere('a.actualTaxonId = :taxon')
                ->setParameter('reference', $referenceID)
                ->setParameter('taxon', $taxonID)
                ->setMaxResults(1);

            try {
                $qb->getQuery()->getSingleScalarResult();
                return true;
            } catch (NoResultException) {
                return false;
            }
        }

    }

}
