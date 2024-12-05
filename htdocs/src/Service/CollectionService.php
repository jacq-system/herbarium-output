<?php declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

readonly class CollectionService
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function getAllAsPairs(): array
    {
        //TODO use right join instead of distinct?
        $sql = "SELECT `collectionID`, `collection`
                FROM `tbl_management_collections`
                WHERE `collectionID`
                IN (
                    SELECT DISTINCT `collectionID`
                    FROM `tbl_specimens`
                )
                ORDER BY `collection`";

        return $this->entityManager->getConnection()->executeQuery($sql)->fetchAllKeyValue();
    }

    public function getAllFromHerbariumAsPairs(int $herbariumID): array
    {
        //TODO purpose of the meta table??
        $sql = "SELECT collectionID, collection
                FROM tbl_management_collections as collections, meta
                WHERE collections.source_id = meta.source_id
                 AND collections.source_id = :herbariumID
                ORDER BY collection;";

        return $this->entityManager->getConnection()->executeQuery($sql, ['herbariumID' => $herbariumID])->fetchAllKeyValue();
    }

}
