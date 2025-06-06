<?php declare(strict_types=1);

namespace App\Service;


readonly class InstitutionService extends BaseService
{


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
