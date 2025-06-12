<?php declare(strict_types=1);

namespace App\Repository\Herbarinput;

use App\Entity\Jacq\Herbarinput\ImageDefinition;
use App\Entity\Jacq\Herbarinput\Institution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class ImageDefinitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImageDefinition::class);
    }

    /**
     * @param string $institutionID
     * @return ImageDefinition[]
     */
    public function getDjatokaServers(?int $institutionID): array
    {
        $qb = $this->createQueryBuilder('img')
            ->select('img')
            ->join('img.institution', 'i')
            ->andWhere('img.serverType = :djatoka')
            ->andWhere('img.iiifCapable != true') //iiif-servers are not checked
            ->andWhere('i.id != :wu') // wu need special treatment
            ->orderBy('img.abbreviation', 'ASC')
            ->setParameter('djatoka', 'djatoka')
            ->setParameter('wu', Institution::WU);

        if ($institutionID !== null) {
            $qb->andWhere('i.id = :institutionID')
                ->setParameter('institutionID', $institutionID);
        }

        return $qb->getQuery()->getResult();

    }


}
