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
                ORDER BY collection";

        return $this->query($sql)->fetchAllKeyValue();
    }

    public function getAllFromHerbariumAsPairs(int $herbariumID): array
    {
        $sql = "SELECT collectionID, collection
                FROM tbl_management_collections as collections
                WHERE  collections.source_id = :herbariumID
                ORDER BY collection;";

        return $this->query($sql, ['herbariumID' => $herbariumID])->fetchAllKeyValue();
    }

    public function getAllFromHerbariumAsPairsByAbbrev(string $herbariumAbbreviation): array
    {
        $sql = "SELECT col.collectionID as id, col.collection as name
                FROM tbl_management_collections as col
                WHERE  col.source_id = :herbarium
                AND `collectionID`
                IN (
                    SELECT DISTINCT `collectionID`
                    FROM `tbl_specimens`
                )
                ORDER BY col.collection";

        return $this->query($sql, ['herbarium' => $herbariumAbbreviation])->fetchAllAssociative();
    }

}
