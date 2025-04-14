<?php declare(strict_types=1);

namespace App\Repository\Herbarinput;

use App\Entity\Jacq\Herbarinput\Literature;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
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

        $sql = "SELECT `herbar_view`.GetProtolog(l.citationID) AS referenceName, l.citationID AS referenceID
                    FROM tbl_lit l
                     LEFT JOIN tbl_tax_synonymy ts ON ts.source_citationID = l.citationID
                     LEFT JOIN tbl_tax_classification tc ON tc.tax_syn_ID = ts.tax_syn_ID
                    WHERE ts.tax_syn_ID IS NOT NULL
                     AND tc.classification_id IS NOT NULL
                     AND l.periodicalID = :referenceID
                    GROUP BY ts.source_citationID
                    ORDER BY referenceName";
        return $this->getEntityManager()->getConnection()->executeQuery($sql, ['referenceID' => $referenceID])->fetchAllAssociative();

    }
}
