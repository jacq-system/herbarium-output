<?php declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

readonly class OrganisationService
{
// TODO non-existing table herbarinput.tbl_organisation
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function getDescription(int $organisationId): string
    {
        $sql = "SELECT description
             FROM tbl_organisation
             WHERE id = :organisationId";

        return $this->entityManager->getConnection()->executeQuery($sql, ['organisationId' => $organisationId])->fetchOne();
    }


    public function getAllChildren(int $parentId): array
    {
        $ret = array($parentId);
        $sql = "SELECT id
              FROM tbl_organisation
              WHERE parent_organisation_id = :parentId";
        $children = $this->entityManager->getConnection()->executeQuery($sql, ['parentId' => $parentId])->fetchAllAssociative();

        foreach ($children as $child) {
            $ret = array_merge($ret, $this->getAllChildren($child['id']));
        }
        return $ret;
    }

}
