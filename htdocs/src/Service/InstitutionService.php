<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Jacq\Herbarinput\Institution;

readonly class InstitutionService extends BaseService
{

    public function getAllAsPairs(): array
    {
        $sql = "SELECT MetadataID, CONCAT(SourceInstitutionID,' - ',SourceID) herbname
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
                ORDER BY herbname";

        return $this->query($sql)->fetchAllKeyValue();
    }

    public function findByCode(string $code): Institution
    {
        return $this->entityManager->getRepository(Institution::class)->findOneBy(['code' => $code]);
    }


}
