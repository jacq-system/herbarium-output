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
        $sql = "SELECT MetadataID as id, CONCAT(SourceInstitutionID,' - ',SourceID) as name
                FROM metadata
                WHERE MetadataID
                IN (
                  SELECT `source_id`
                  FROM `tbl_management_collections`
                  WHERE `collectionID`
                  IN (
                    SELECT DISTINCT `collectionID`
                    FROM `tbl_specimens`
                  )
                )
                ORDER BY name";

        return $this->query($sql)->fetchAllKeyValue();
    }

}
