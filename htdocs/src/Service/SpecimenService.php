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


    /**
     * get specimen-id of a given stable identifier
     */
    public function getSpecimenIDfromSid(string $sid): ?int
    {
        $pos = strpos($sid, "JACQID");
        if ($pos !== false) {  // we've found a sid with JACQID in it, so check the attached specimen-ID and return it, if valid
            //todo - hard coded 6 digits of ID
            $specimenID = intval(substr($sid, $pos + 6));
            $sql = "SELECT specimen_ID
                             FROM tbl_specimens
                             WHERE specimen_ID = :specimenID";
            $id = $this->entityManager->getConnection()->executeQuery($sql, ['specimenID' => $specimenID])->fetchOne();
        } else {
            $sql = "SELECT specimen_ID
                             FROM tbl_specimens_stblid
                             WHERE stableIdentifier = :sid";
            $id = $this->entityManager->getConnection()->executeQuery($sql, ['sid' => $sid])->fetchOne();
        }
        if ($id === null || $id === false) {
            return null;
        }
        return $id;
    }


    /**
     * get a list of all errors which prevent the generation of stable identifier
     */
    public function getEntriesWithErrors(?int $sourceID = null): array
    {
        if ($sourceID !== null) {
            $sql = "SELECT ss.specimen_ID AS specimenID, CONCAT('https://www.jacq.org/detail.php?ID=', ss.specimen_ID) AS link
                              FROM tbl_specimens_stblid ss
                               JOIN tbl_specimens s ON ss.specimen_ID = s.specimen_ID
                               JOIN tbl_management_collections mc ON s.collectionID = mc.collectionID
                              WHERE ss.stableIdentifier IS NULL
                               AND mc.source_id = :sourceID
                              GROUP BY ss.specimen_ID
                              ORDER BY ss.specimen_ID";
            $rows = $this->entityManager->getConnection()->executeQuery($sql, ['sourceID' => $sourceID])->fetchAllAssociative();
        } else {
            $sql = "SELECT specimen_ID AS specimenID, CONCAT('https://www.jacq.org/detail.php?ID=', specimen_ID) AS link
                              FROM tbl_specimens_stblid
                              WHERE stableIdentifier IS NULL
                              GROUP BY specimen_ID
                              ORDER BY specimen_ID";
               $rows = $this->entityManager->getConnection()->executeQuery($sql)->fetchAllAssociative();
        }
        $data = array('total' => count($rows));
        foreach ($rows as $line => $row) {
            $data['result'][$line] = $row;
            $data['result'][$line]['errorList'] = $this->getAllStableIdentifiers($row['specimenID'])['list'];
        }

        return $data;
    }

}
