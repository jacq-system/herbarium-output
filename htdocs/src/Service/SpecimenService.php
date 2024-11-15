<?php declare(strict_types=1);

namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;

readonly class SpecimenService
{

    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    /**
     * get all stable identifiers and their respective timestamps of a given specimen-id
     */
    public function getAllStableIdentifiers(int $specimenID): array
    {
        //TODO refactor to Router in future
        $sql = "SELECT stableIdentifier, timestamp, CONCAT('https://www.jacq.org/detail.php?ID=', specimen_ID) AS link
                                       FROM tbl_specimens_stblid
                                       WHERE specimen_ID = :specimenID
                                        AND stableIdentifier IS NOT NULL
                                       ORDER BY timestamp DESC
                                       LIMIT 1";

        $ret['latest'] = $this->entityManager->getConnection()->executeQuery($sql, ['specimenID' => $specimenID])->fetchAssociative();
        $sql = "SELECT stableIdentifier, timestamp, error
                                     FROM tbl_specimens_stblid
                                     WHERE specimen_ID = :specimenID
                                     ORDER BY timestamp DESC";
        $ret['list'] = $this->entityManager->getConnection()->executeQuery($sql, ['specimenID' => $specimenID])->fetchAllAssociative();
        //TODO ???
        foreach ($ret['list'] as $key => $val) {
            if (!empty($val['error'])) {
                preg_match("/already exists \((?P<number>\d+)\)$/", $val['error'], $parts);
                //TODO refactor to Router when "output" ready
                $ret['list'][$key]['link'] = (!empty($parts['number'])) ? "https://www.jacq.org/detail.php?ID=" . $parts['number'] : '';
            }
        }

        return $ret;
    }

}
