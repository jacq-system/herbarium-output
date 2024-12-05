<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Jacq\Herbarinput\Meta;
use Doctrine\ORM\EntityManagerInterface;

readonly class InstitutionService
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function getAllAsPairs(): array
    {
        $sql = "SELECT source_id, CONCAT(`source_code`,' - ',`source_name`) herbname
                FROM `meta`
                WHERE `source_id`
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

        return $this->entityManager->getConnection()->executeQuery($sql)->fetchAllKeyValue();
    }

    public function findByCode(string $code): Meta
    {
        return $this->entityManager->getRepository(Meta::class)->findOneBy(['code' => $code]);
    }


}
