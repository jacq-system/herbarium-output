<?php declare(strict_types=1);

namespace App\Service;

readonly class CollectionService extends BaseService
{

    public function getAllAsPairs(): array
    {
        $sql = "SELECT `collectionID`, `collection`
                FROM `tbl_management_collections`
                WHERE `collectionID`
                IN (
                    SELECT DISTINCT `collectionID`
                    FROM `tbl_specimens`
                )
                ORDER BY `collection`";

        return $this->query($sql)->fetchAllKeyValue();
    }

    public function getAllFromHerbariumAsPairs(int $herbariumID): array
    {
        //TODO purpose of the meta table??
        $sql = "SELECT collectionID, collection
                FROM tbl_management_collections as collections, meta
                WHERE collections.source_id = meta.source_id
                 AND collections.source_id = :herbariumID
                ORDER BY collection;";

        return $this->query($sql, ['herbariumID' => $herbariumID])->fetchAllKeyValue();
    }

}
