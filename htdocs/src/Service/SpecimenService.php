<?php declare(strict_types=1);

namespace App\Service;


use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

readonly class SpecimenService
{

    public function __construct(protected EntityManagerInterface $entityManager, protected RouterInterface $router)
    {
    }

    /**
     * get specimen-id of a given stable identifier
     */
    public function findSpecimenIiUsingSid(string $sid): ?int
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

        $rows = $this->entityManager->getConnection()->executeQuery($sql, ['sourceID' => $sourceID])->fetchAllAssociative();

        $data = array('total' => count($rows));
        foreach ($rows as $line => $row) {
            $data['result'][$line] = $row;
            $data['result'][$line]['stableIdentifierList'] = $this->getAllStableIdentifiers($row['specimenID']);
        }

        return $data;
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
        $rowCount = $this->entityManager->getConnection()->executeQuery($sql)->fetchOne();

        $lastPage = (int)floor(($rowCount - 1) / $entriesPerPage);
        if ($page > $lastPage) {
            $page = $lastPage;
        } elseif ($page < 0) {
            $page = 0;
        }

        $data = array('page' => $page + 1,
            'previousPage' => $this->urlHelperRouteMulti((($page > 0) ? ($page - 1) : 0), $entriesPerPage),
            'nextPage' => $this->urlHelperRouteMulti((($page < $lastPage) ? ($page + 1) : $lastPage), $entriesPerPage),
            'firstPage' => $this->urlHelperRouteMulti(0, $entriesPerPage),
            'lastPage' => $this->urlHelperRouteMulti($lastPage, $entriesPerPage),
            'totalPages' => $lastPage + 1,
            'total' => $rowCount,
        );
        $offset = ($page * $entriesPerPage);
        $sql = "SELECT specimen_ID AS specimenID, count(specimen_ID) AS `numberOfEntries`
                              FROM tbl_specimens_stblid
                              WHERE stableIdentifier IS NOT NULL
                              GROUP BY specimen_ID
                              HAVING numberOfEntries > 1
                              ORDER BY numberOfEntries DESC, specimenID
                              LIMIT :entriesPerPage OFFSET :offset";

        $rows = $this->entityManager->getConnection()->executeQuery($sql, ["offset" => $offset, "entriesPerPage" => $entriesPerPage], ['offset' => ParameterType::INTEGER, "entriesPerPage" => ParameterType::INTEGER])->fetchAllAssociative();

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

}
