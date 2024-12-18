<?php declare(strict_types=1);

namespace App\Service;


use App\Entity\Jacq\Herbarinput\Specimens;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SpecimenService extends BaseService
{
    public const string JACQID_PREFIX = "JACQID";

    /**
     * get specimen-id of a given stable identifier
     */
    public function findSpecimenIdUsingSid(string $sid): ?int
    {
        $pos = strpos($sid, self::JACQID_PREFIX);
        if ($pos !== false) {  // we've found a sid with JACQID in it, so check the attached specimen-ID and return it, if valid
            $specimenID = intval(substr($sid, $pos + strlen(self::JACQID_PREFIX)));
            $id = $this->findAccessibleForPublic($specimenID)->getId();
        } else {
            $id = $this->findBySid($sid);
        }
        return $id === 0 ? null : $id;
    }

    public function find(int $id): Specimens
    {
        $specimen = $this->entityManager->getRepository(Specimens::class)->find($id);
        if ($specimen === null) {
            throw new EntityNotFoundException('Specimen not found');
        }
        return $specimen;
    }

    public function findAccessibleForPublic(int $id): Specimens
    {
        $specimen = $this->entityManager->getRepository(Specimens::class)->findOneBy(["id"=>$id, 'accessibleForPublic'=>true]);
        if ($specimen === null) {
            throw new EntityNotFoundException('Specimen not found');
        }
        return $specimen;
    }

    public function findBySid(string $sid): int
    {
        $sql = "SELECT specimen_ID
                     FROM tbl_specimens_stblid
                     WHERE stableIdentifier = :sid";
        return $this->query($sql, ['sid' => $sid])->fetchOne();
    }

    /**
     * get a list of all errors which prevent the generation of stable identifier
     */
    public function getEntriesWithErrors(?int $sourceID): array
    {
        $data = [];
        $specimens = $this->specimensWithErrors($sourceID);
        $data['total'] = count($specimens);
        foreach ($specimens as $line => $specimen) {
            $data['result'][$line] = ['specimenID' => $specimen->getId(), 'link' => $this->router->generate('app_front_specimenDetail', ['specimenID' => $specimen->getId()], UrlGeneratorInterface::ABSOLUTE_URL)];
            $data['result'][$line]['errorList'] = $this->sids2array($specimen);
        }

        return $data;
    }

    protected function specimensWithErrors(?int $sourceID): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder = $queryBuilder->select('DISTINCT s')->from(Specimens::class, 's')->join('s.stableIdentifiers', 'sid')->where('sid.identifier IS NULL');

        if ($sourceID !== null) {
            $queryBuilder = $queryBuilder->join('s.herbCollection', 'col')->andWhere('col.institution = :sourceID')->setParameter('sourceID', $sourceID);
        }
        return $queryBuilder->getQuery()->getResult();
    }

    public function sids2array(Specimens $specimen): array
    {
        $ret = [];
        $sids = $specimen->getStableIdentifiers();
        foreach ($sids as $key => $stableIdentifier) {
            $ret[$key]['stableIdentifier'] = $stableIdentifier->getIdentifier();
            $ret[$key]['timestamp'] = $stableIdentifier->getTimestamp()->format('Y-m-d H:i:s');

            if (!empty($stableIdentifier->getError())) {
                $ret[$key]['error'] = $stableIdentifier->getError();

                preg_match("/already exists \((?P<number>\d+)\)$/", $stableIdentifier->getError(), $parts);

                $ret[$key]['link'] = (!empty($parts['number'])) ? $this->router->generate('app_front_specimenDetail', ['specimenID' => $parts['number']], UrlGeneratorInterface::ABSOLUTE_URL) : '';

            }
        }

        return $ret;
    }

    /**
     * get a list of all specimens with multiple stable identifiers of a given source
     */
    public function getMultipleEntriesFromSource(int $sourceID): array
    {
        $sql = "SELECT ss.specimen_ID AS specimenID, count(ss.specimen_ID) AS `numberOfEntries`
                              FROM tbl_specimens_stblid ss
                               JOIN tbl_specimens s ON ss.specimen_ID = s.specimen_ID
                               JOIN tbl_management_collections mc ON s.collectionID = mc.collectionID
                              WHERE ss.stableIdentifier IS NOT NULL
                               AND mc.source_id = :sourceID
                              GROUP BY ss.specimen_ID
                              HAVING numberOfEntries > 1
                              ORDER BY numberOfEntries DESC, specimenID";

        $rows = $this->query($sql, ['sourceID' => $sourceID])->fetchAllAssociative();

        $data = array('total' => count($rows));
        foreach ($rows as $line => $row) {
            $data['result'][$line] = $row;
            $data['result'][$line]['stableIdentifierList'] = $this->getAllStableIdentifiers($row['specimenID']);
        }

        return $data;
    }

    /**
     * get all stable identifiers and their respective timestamps of a given specimen-id
     */
    public function getAllStableIdentifiers(int $specimenID): array
    {
        $specimen = $this->findAccessibleForPublic($specimenID);
        if (empty($specimen->getStableIdentifiers())) {
            return [];
        }
        $ret['latest'] = $this->sid2array($specimen);
        $ret['list'] = $this->sids2array($specimen);
        return $ret;
    }

    public function sid2array(Specimens $specimen): array
    {
        return ['stableIdentifier' => $specimen->getMainStableIdentifier()->getIdentifier(), 'timestamp' => $specimen->getMainStableIdentifier()->getTimestamp()->format('Y-m-d H:i:s'), 'link' => $this->router->generate('app_front_specimenDetail', ['specimenID' => $specimen->getId()], UrlGeneratorInterface::ABSOLUTE_URL)

        ];
    }

    /**
     * get a list of all specimens with multiple stable identifiers
     *
     * @param int $page optional page number, defaults to first page
     * @param int $entriesPerPage optional number of items, defaults to 50
     * @return array list of results
     */
    public function getMultipleEntries(int $page = 0, int $entriesPerPage = 50): array
    {
        if ($entriesPerPage <= 0) {
            $entriesPerPage = 50;
        } else if ($entriesPerPage > 100) {
            $entriesPerPage = 100;
        }

        $sql = "SELECT count(*) FROM (SELECT specimen_ID AS specimenID, count(specimen_ID) AS `numberEntries`
                                FROM tbl_specimens_stblid
                                WHERE stableIdentifier IS NOT NULL
                                GROUP BY specimen_ID
                                HAVING numberEntries > 1) AS subquery";
        $rowCount = $this->query($sql)->fetchOne();

        $lastPage = (int)floor(($rowCount - 1) / $entriesPerPage);
        if ($page > $lastPage) {
            $page = $lastPage;
        } elseif ($page < 0) {
            $page = 0;
        }

        $data = array('page' => $page + 1, 'previousPage' => $this->urlHelperRouteMulti((($page > 0) ? ($page - 1) : 0), $entriesPerPage), 'nextPage' => $this->urlHelperRouteMulti((($page < $lastPage) ? ($page + 1) : $lastPage), $entriesPerPage), 'firstPage' => $this->urlHelperRouteMulti(0, $entriesPerPage), 'lastPage' => $this->urlHelperRouteMulti($lastPage, $entriesPerPage), 'totalPages' => $lastPage + 1, 'total' => $rowCount,);
        $offset = ($page * $entriesPerPage);
        $sql = "SELECT specimen_ID AS specimenID, count(specimen_ID) AS `numberOfEntries`
                              FROM tbl_specimens_stblid
                              WHERE stableIdentifier IS NOT NULL
                              GROUP BY specimen_ID
                              HAVING numberOfEntries > 1
                              ORDER BY numberOfEntries DESC, specimenID
                              LIMIT :entriesPerPage OFFSET :offset";

        $rows = $this->query($sql, ["offset" => $offset, "entriesPerPage" => $entriesPerPage], ['offset' => ParameterType::INTEGER, "entriesPerPage" => ParameterType::INTEGER])->fetchAllAssociative();

        foreach ($rows as $line => $row) {
            $data['result'][$line] = $row;
            $data['result'][$line]['stableIdentifierList'] = $this->getAllStableIdentifiers($row['specimenID']);
        }

        return $data;
    }

    protected function urlHelperRouteMulti(int $page, int $entriesPerPage): string
    {
        return $this->router->generate('services_rest_sid_multi', ['page' => $page, 'entriesPerPage' => $entriesPerPage], UrlGeneratorInterface::ABSOLUTE_URL);
    }


    /**
     * get the specimen-ID of a given HerbNumber and source-id
     */
    public function getSpecimenIdFromHerbNummer(string $herbNummer, int $source_id): ?int
    {
        $sql = "SELECT specimen_ID
                             FROM tbl_specimens s
                              LEFT JOIN tbl_management_collections mc on s.collectionID = mc.collectionID
                             WHERE s.HerbNummer = :herbNummer
                              AND mc.source_id = :source_id";
        $id = $this->query($sql, ["herbNummer" => $herbNummer, "source_id" => $source_id])->fetchOne();

        if ($id !== false) {
            return $id;
        }
        return null;

    }

    /**
     * try to construct PID
     */
    public function constructStableIdentifier(Specimens $specimen): string
    {
        $sourceId = $specimen->getHerbCollection()->getId();
        if (!empty($sourceId) && !empty($specimen->getHerbNumber())) {
            $modifiedHerbNumber = str_replace(' ', '', $specimen->getHerbNumber());

            if ($sourceId == '29') { // B
                if (strlen(trim($modifiedHerbNumber)) > 0) {
                    $modifiedHerbNumber = str_replace('-', '', $modifiedHerbNumber);
                } else {
                    $modifiedHerbNumber = self::JACQID_PREFIX . $specimen->getId();
                }
                return "https://herbarium.bgbm.org/object/" . $modifiedHerbNumber;
            } elseif ($sourceId == '27') { // LAGU
                return "https://lagu.jacq.org/object/" . $modifiedHerbNumber;
            } elseif ($sourceId == '48') { // TBI
                return "https://tbi.jacq.org/object/" . $modifiedHerbNumber;
            } elseif ($sourceId == '50') { // HWilling
                if (strlen(trim($modifiedHerbNumber)) > 0) {
                    $modifiedHerbNumber = str_replace('-', '', $modifiedHerbNumber);
                } else {
                    $modifiedHerbNumber = self::JACQID_PREFIX . $specimen->getId();
                }
                return "https://willing.jacq.org/object/" . $modifiedHerbNumber;
            }
        }
        return '';
    }


    public function collection(array $row): string
    {
        $text = $row['Sammler'];
        if (strstr((string)$row['Sammler_2'], "&") || strstr((string)$row['Sammler_2'], "et al.")) {
            $text .= " et al.";
        } else if ($row['Sammler_2']) {
            $text .= " & " . $row['Sammler_2'];
        }

        if ($row['series_number']) {
            if ($row['Nummer']) {
                $text .= " " . $row['Nummer'];
            }
            if ($row['alt_number'] && $row['alt_number'] != "s.n.") {
                $text .= " " . $row['alt_number'];
            }
            if ($row['series']) {
                $text .= " " . $row['series'];
            }
            $text .= " " . $row['series_number'];
        } else {
            if ($row['series']) {
                $text .= " " . $row['series'];
            }
            if ($row['Nummer']) {
                $text .= " " . $row['Nummer'];
            }
            if ($row['alt_number']) {
                $text .= " " . $row['alt_number'];
            }
//        if (strstr($row['alt_number'], "s.n.")) {
//            $text .= " [" . $row['Datum'] . "]";
//        }
        }

        return trim($text);
    }
}
