<?php declare(strict_types=1);

namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

readonly class ReferenceService
{

    public function __construct(protected EntityManagerInterface $entityManager, protected RouterInterface $router)
    {
    }

    /**
     * Fetch a list of all references (which have a classification attached)
     *
     * @param string $referenceType Type of references to return (citation, person, service, specimen, periodical)
     * @param ?int $referenceID optional ID of reference
     * @return array References information
     */
    public function getByType(string $referenceType, ?int $referenceID): array
    {
//            //TODO  these three from oirignal code are not supported or do not exists?
//            case 'person':
//            case 'service':
//            case 'specimen':
        return match ($referenceType) {
            'citation' => $this->getCitationReferences($referenceID),
            'periodical' => $this->getPeriodicalReferences($referenceID),
            default => [],
        };
    }

    protected function getCitationReferences(?int $referenceID): array
    {
        if (!empty($referenceID)) {
            $sql = <<<SQL
                    SELECT titel AS `name`, citationID AS `id`
                    FROM tbl_lit
                    WHERE citationID = :id
                    SQL;
            return $this->entityManager->getConnection()->executeQuery($sql, ['id' => $referenceID])->fetchAssociative();
        } else {
            $sql = <<<SQL
                    SELECT l.titel AS `name`, l.citationID AS `id`
                    FROM tbl_lit l
                        LEFT JOIN tbl_tax_synonymy ts ON ts.source_citationID = l.citationID
                        LEFT JOIN tbl_tax_classification tc ON tc.tax_syn_ID = ts.tax_syn_ID
                    WHERE l.category LIKE '%classification%'
                        AND ts.tax_syn_ID IS NOT NULL
                        AND tc.classification_id IS NOT NULL
                    GROUP BY ts.source_citationID
                    ORDER BY `name`
                    SQL;
            return $this->entityManager->getConnection()->executeQuery($sql)->fetchAllAssociative();
        }

    }

    protected function getPeriodicalReferences(?int $referenceID): array
    {
        if (!empty($referenceID)) {
            $sql = <<<SQL
                    SELECT periodical AS `name`, periodicalID AS `id`
                    FROM tbl_lit_periodicals
                    WHERE periodicalID = :id
                    SQL;
            return $this->entityManager->getConnection()->executeQuery($sql, ['id' => $referenceID])->fetchAssociative();
        } else {
            $sql = <<<SQL
                            SELECT lp.periodical AS `name`, l.periodicalID AS `id`
                            FROM tbl_lit_periodicals lp
                            LEFT JOIN tbl_lit l ON l.periodicalID = lp.periodicalID
                            LEFT JOIN tbl_tax_synonymy ts ON ts.source_citationID = l.citationID
                            LEFT JOIN tbl_tax_classification tc ON tc.tax_syn_ID = ts.tax_syn_ID
                            WHERE l.category LIKE '%classification%'
                                AND ts.tax_syn_ID IS NOT NULL
                                AND tc.classification_id IS NOT NULL
                            GROUP BY l.periodicalID
                            ORDER BY `name`
                            SQL;
            return $this->entityManager->getConnection()->executeQuery($sql)->fetchAllAssociative();
        }


    }

    /**
     * Return (other) references for this name which include them in their classification
     */
    public function getNameReferences(int $taxonID, int $excludeReferenceId = 0, int $insertSeries = 0): array
    {
        $results = [];
        // direct integration of tbl_lit_... for (much) faster sorting whe using ORDER BY
        // only select entries which are part of a classification, so either tc.tax_syn_ID or has_children_syn.tax_syn_ID must not be NULL
        //ONLY_FULL_GROUP_BY,
        $sql = "SELECT ts.source_citationID AS referenceId, `herbar_view`.GetProtolog(`ts`.`source_citationID`) AS `referenceName`
            FROM tbl_tax_synonymy ts
             LEFT JOIN tbl_tax_classification tc ON tc.tax_syn_ID = ts.tax_syn_ID
             LEFT JOIN tbl_tax_classification has_children ON has_children.parent_taxonID = ts.taxonID
             LEFT JOIN tbl_tax_synonymy has_children_syn ON (    has_children_syn.tax_syn_ID = has_children.tax_syn_ID
                                                             AND has_children_syn.source_citationID = ts.source_citationID)
             LEFT JOIN tbl_lit l ON l.citationID = ts.source_citationID
             LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
             LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
             LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
            WHERE ts.source_citationID IS NOT NULL
             AND ts.acc_taxon_ID IS NULL
             AND ts.taxonID = :taxonID
             AND (tc.tax_syn_ID IS NOT NULL OR has_children_syn.tax_syn_ID IS NOT NULL) ";
        if ($insertSeries !== 0) {
            $sql .= " AND ts.source_citationID NOT IN (SELECT citationID
                                                   FROM tbl_classification_citation_insert
                                                   WHERE series = :insertSeries
                                                    AND taxonID = :taxonID
                                                    AND referenceId = :excludeReferenceId)"; //TODO param :excludeReferenceId can be default O, no control?
        }
        $sql .= " GROUP BY ts.source_citationID
              ORDER BY la.autor, l.jahr, le.autor, l.suptitel, lp.periodical, l.vol, l.part, l.pp";
        $dbRows = $this->entityManager->getConnection()->executeQuery($sql, ['taxonID' => $taxonID, 'insertSeries' => $insertSeries, 'excludeReferenceId' => $excludeReferenceId])->fetchAllAssociative();
        foreach ($dbRows as $dbRow) {
            // check for exclude id
            if ($dbRow['referenceId'] != $excludeReferenceId) {
                $results[] = [
                    "referenceName" => $dbRow['referenceName'],
                    "referenceId" => intval($dbRow['referenceId']),
                    "referenceType" => "citation", "taxonID" => $taxonID,
                    "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $taxonID], UrlGeneratorInterface::ABSOLUTE_URL)),
                    "hasChildren" => $this->hasClassificationChildren($taxonID, $dbRow['referenceId']),
                    "hasType" => false, //TODO always false?
                    "hasSpecimen" => false //TODO always false?
                ];
            }
        }

        // Fetch all synonym rows (if any)
        // direct integration of tbl_lit_... for (much) faster sorting whe using ORDER BY
        // ONLY_FULL_GROUP_BY,
        $sqlSyns = "SELECT ts.source_citationID AS referenceId,
                       `herbar_view`.GetProtolog(`ts`.`source_citationID`) AS `referenceName`,
                       ts.acc_taxon_ID AS acceptedId
                FROM tbl_tax_synonymy ts
                 LEFT JOIN tbl_lit l ON l.citationID = ts.source_citationID
                 LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
                 LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
                 LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
                WHERE ts.source_citationID IS NOT NULL
                 AND ts.source_citationID != :excludeReferenceId
                 AND ts.acc_taxon_ID IS NOT NULL
                 AND ts.taxonID = :taxonID
                GROUP BY ts.source_citationID
                ORDER BY la.autor, l.jahr, le.autor, l.suptitel, lp.periodical, l.vol, l.part, l.pp";
        $dbSyns = $this->entityManager->getConnection()->executeQuery($sqlSyns, ['taxonID' => $taxonID, 'excludeReferenceId' => $excludeReferenceId])->fetchAllAssociative();

        foreach ($dbSyns as $dbSyn) {
            if ($this->isAcceptedTaxonPartOfClassification($dbSyn['referenceId'], $dbSyn['acceptedId'])) {
                $results[] = array("referenceName" => '= ' . $dbSyn['referenceName'],  //  mark the reference Name as synonym
                    "referenceId" => intval($dbSyn['referenceId']), "referenceType" => "citation", "taxonID" => $taxonID, "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $taxonID], UrlGeneratorInterface::ABSOLUTE_URL)), "hasChildren" => false, "hasType" => false, "hasSpecimen" => false,);
            }
        }

        return $results;
    }

    /**
     * check if there are any classification children of the taxonID according to this reference
     */
    protected function hasClassificationChildren(int $taxonID, int $referenceID): bool
    {
        $sqlQueryChild = "SELECT ts.taxonID
                                       FROM tbl_tax_synonymy ts
                                        LEFT JOIN tbl_tax_classification tc ON ts.tax_syn_ID = tc.tax_syn_ID
                                       WHERE ts.source_citationID = :referenceID
                                        AND ts.acc_taxon_ID IS NULL
                                        AND tc.parent_taxonID = :taxonID";
        $child = $this->entityManager->getConnection()->executeQuery($sqlQueryChild, ['taxonID' => $taxonID, 'referenceID' => $referenceID])->fetchAssociative();
        if ($child !== false) {
            $hasChildren = true;
        } else {
            $sqlQueryChild = "SELECT ts.taxonID
                                       FROM tbl_tax_synonymy ts
                                       WHERE ts.source_citationID = :referenceID
                                        AND ts.acc_taxon_ID = $taxonID";
            $child = $this->entityManager->getConnection()->executeQuery($sqlQueryChild, ['referenceID' => $referenceID])->fetchAssociative();
            $hasChildren = (bool)$child;
        }
        return $hasChildren;
    }

    /**
     * check if the accepted taxon is part of a classification
     * only select entries which are part of a classification, so either tc.tax_syn_ID or has_children_syn.tax_syn_ID must not be NULL
     */
    protected function isAcceptedTaxonPartOfClassification(int $referenceId, int $acceptedId): bool
    {
        $sqlQuerySynonym = "SELECT count(ts.source_citationID AS referenceId)
                                    FROM tbl_tax_synonymy ts
                                     LEFT JOIN tbl_tax_classification tc ON ts.tax_syn_ID = tc.tax_syn_ID
                                     LEFT JOIN tbl_tax_classification has_children ON has_children.parent_taxonID = ts.taxonID
                                     LEFT JOIN tbl_tax_synonymy has_children_syn ON (    has_children_syn.tax_syn_ID = has_children.tax_syn_ID
                                                                                     AND has_children_syn.source_citationID = ts.source_citationID)
                                    WHERE ts.source_citationID = :reference
                                     AND ts.acc_taxon_ID IS NULL
                                     AND ts.taxonID = :acceptedId
                                     AND (tc.tax_syn_ID IS NOT NULL OR has_children_syn.tax_syn_ID IS NOT NULL)";
        $rowCount = $this->entityManager->getConnection()->executeQuery($sqlQuerySynonym, ['reference' => $referenceId, 'acceptedId' => $acceptedId])->fetchOne();
        if ($rowCount > 0) {
            return true;
        }
        return false;
    }

    /**
     * Get classification children of a given taxonID according to a given reference
     *
     * @param string $referenceType Type of reference (periodical, citation, service, etc.)
     * @param int $referenceID ID of reference
     * @param int $taxonID optional ID of taxon
     * @param int $insertSeries optional ID of cication-Series to insert
     * @return array structured array with classification information
     */
    public function getChildren(string $referenceType, int $referenceID, int $taxonID, int $insertSeries): array
    {
        $results = array();

        switch ($referenceType) {
            case 'periodical':
                $dbRows = $this->getPeriodicalChildrenReferences($referenceID);
                foreach ($dbRows as $dbRow) {
                    $results[] = array(
                        "taxonID" => 0,
                        "referenceId" => intval($dbRow['referenceID']),
                        "referenceName" => $dbRow['referenceName'],
                        "referenceType" => "citation",
                        "hasChildren" => true,
                        "hasType" => false,
                        "hasSpecimen" => false,
                        "insertedCitation" => false,
                    );
                }
                return $results;
            case 'citation':
            default:
                $dbRows = $this->getCitationChildrenReferences($referenceID, $taxonID);
                foreach ($dbRows as $dbRow) {
                    $results[] = array(
                        "taxonID" => intval($dbRow['taxonID']),
                        "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $taxonID], UrlGeneratorInterface::ABSOLUTE_URL)),
                        "referenceId" => $referenceID,
                        "referenceName" => $dbRow['scientificName'],
                        "referenceType" => "citation",
                        "hasChildren" => ($dbRow['hasChildren'] > 0 || $dbRow['hasSynonyms'] > 0 || $dbRow['hasBasionym']),
                        "hasType" => $this->hasType($dbRow['taxonID']),
                        "hasSpecimen" => $this->hasSpecimen($dbRow['taxonID']),
                        "insertedCitation" => false,
                        "referenceInfo" => array(
                            "number" => $dbRow['number'],
                            "order" => intval($dbRow['order']),
                            "rank_abbr" => $dbRow['rank_abbr'],
                            "rank_hierarchy" => intval($dbRow['rank_hierarchy']),
                            "tax_syn_ID" => intval($dbRow['tax_syn_ID']),
                        ),
                    );
                    $insertedCitations = $this->getInsertedCitation($insertSeries, $referenceID, $dbRow['taxonID']);
                    if (!empty($insertedCitations)) {
                        foreach ($insertedCitations as $citation) {
                            $results[] = array(
                                "taxonID" => $citation['taxonID'],
                                "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $taxonID], UrlGeneratorInterface::ABSOLUTE_URL)),
                                "referenceId" => $citation['referenceId'],
                                "referenceName" => $citation['referenceName'],
                                "referenceType" => $citation['referenceType'],
                                "hasChildren" => $citation['hasChildren'],
                                "insertedCitation" => true,
                            );
                        }
                    }
                }
                break;
        }

        return $results;
    }

    /**
     * get all citations which belong to the given periodical
     */
    protected function getPeriodicalChildrenReferences(int $referenceID): array
    {

        $sql = "SELECT `herbar_view`.GetProtolog(l.citationID) AS referenceName, l.citationID AS referenceID
                    FROM tbl_lit l
                     LEFT JOIN tbl_tax_synonymy ts ON ts.source_citationID = l.citationID
                     LEFT JOIN tbl_tax_classification tc ON tc.tax_syn_ID = ts.tax_syn_ID
                    WHERE ts.tax_syn_ID IS NOT NULL
                     AND tc.classification_id IS NOT NULL
                     AND l.periodicalID = :referenceID
                    GROUP BY ts.source_citationID
                    ORDER BY referenceName";
        return $this->entityManager->getConnection()->executeQuery($sql, ['referenceID' => $referenceID])->fetchAllAssociative();

    }

    /**
     * get all citations which belong to the given citation
     */
    protected function getCitationChildrenReferences(int $referenceID, int $taxonID): array
    {
        $sql = "SELECT `herbar_view`.GetScientificName( ts.`taxonID`, 0 ) AS `scientificName`,
                           ts.taxonID,
                           ts.tax_syn_ID AS `tax_syn_ID`,
                           tc.`number` AS `number`,
                           tc.`order` AS `order`,
                           tr.rank_abbr,
                           tr.rank_hierarchy,
                           MAX(`has_children`.`tax_syn_ID` IS NOT NULL) AS `hasChildren`,
                           MAX(`has_synonyms`.`tax_syn_ID` IS NOT NULL) AS `hasSynonyms`,
                           (`has_basionym`.`basID`         IS NOT NULL) AS `hasBasionym`
                    FROM tbl_tax_synonymy ts
                     LEFT JOIN tbl_tax_species tsp ON ts.taxonID = tsp.taxonID
                     LEFT JOIN tbl_tax_rank tr ON tsp.tax_rankID = tr.tax_rankID
                     LEFT JOIN tbl_tax_classification tc ON ts.tax_syn_ID = tc.tax_syn_ID
                     LEFT JOIN tbl_tax_synonymy has_synonyms ON (has_synonyms.acc_taxon_ID = ts.taxonID
                                                                 AND has_synonyms.source_citationID = ts.source_citationID)
                     LEFT JOIN tbl_tax_classification has_children_clas ON has_children_clas.parent_taxonID = ts.taxonID
                     LEFT JOIN tbl_tax_synonymy has_children ON (has_children.tax_syn_ID = has_children_clas.tax_syn_ID
                                                                 AND has_children.source_citationID = ts.source_citationID)
                     LEFT JOIN tbl_tax_species has_basionym ON ts.taxonID = has_basionym.taxonID
                    WHERE ts.source_citationID = :referenceID
                     AND ts.acc_taxon_ID IS NULL ";


        // check if we search for children of a specific taxon
        if ($taxonID > 0) {
            $sql .= " AND tc.parent_taxonID = :taxonID ";
        } // .. if not make sure we only return entries which have at least one child
        else {
            $sql .= " AND tc.parent_taxonID IS NULL
                          AND has_children.tax_syn_ID IS NOT NULL";
        }
        $sql .= " GROUP BY ts.taxonID ORDER BY `order`, `scientificName`";

        return $this->entityManager->getConnection()->executeQuery($sql, ['referenceID' => $referenceID, 'taxonID' => $taxonID])->fetchAllAssociative();

    }

    /**
     * Are there any type records of a given taxonID?
     *
     * @param int $taxonID ID of taxon
     */
    protected function hasType(int $taxonID): bool
    {
        $sql = "SELECT s.specimen_ID
                FROM tbl_specimens s
                 LEFT JOIN tbl_specimens_types tst ON tst.specimenID = s.specimen_ID
                WHERE tst.typusID IS NOT NULL
                 AND tst.taxonID = :taxonID";
        return (bool)$this->entityManager->getConnection()->executeQuery($sql, ['taxonID' => $taxonID])->fetchAssociative();
    }

    /**
     * Are there any specimen records of a given taxonID?
     *
     * @param int $taxonID ID of taxon
     * @return bool specimen record(s) present?
     */
    protected function hasSpecimen(int $taxonID): bool
    {
        $sql = "SELECT specimen_ID FROM tbl_specimens WHERE taxonID = :taxonID";
        return (bool)$this->entityManager->getConnection()->executeQuery($sql, ['taxonID' => $taxonID])->fetchAssociative();
    }

    protected function getInsertedCitation(int $insertSeries, int $referenceID, int $taxonID): array
    {
        $results = [];

        $dbRows = $this->findCitations($insertSeries, $referenceID, $taxonID);
        foreach ($dbRows as $row) {
            $results[] = array(
                'referenceName' => $this->getCitationName($row['citationID']),
                'referenceId' => intval($row['citationID']),
                "referenceType" => "citation",
                "taxonID" => $taxonID,
                "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $taxonID], UrlGeneratorInterface::ABSOLUTE_URL)),
                "hasChildren" => $this->hasClassificationChildren($taxonID, $row['citationID']),
            );
        }

        return $results;
    }

    protected function findCitations(int $insertSeries, int $referenceID, int $taxonID): array
    {
        $sql = "SELECT citationID
                FROM tbl_classification_citation_insert
                WHERE series = :insertSeries
                 AND taxonID = :taxonID
                 AND referenceId = :referenceID
                ORDER BY sequence";
        return $this->entityManager->getConnection()->executeQuery($sql, ['taxonID' => $taxonID, 'insertSeries' => $insertSeries, 'referenceID' => $referenceID])->fetchAllAssociative();

    }

    protected function getCitationName(int $id): ?string
    {
        $sql = "SELECT `herbar_view`.GetProtolog(`citationID`) AS `referenceName`
                                               FROM `tbl_lit`
                                               WHERE `citationID` = :id";
        $name = $this->entityManager->getConnection()->executeQuery($sql, ['id' => $id])->fetchOne();
        if ($name === false) {
            return null;
        }
        return $name;
    }

    /**
     * fetch synonyms (and basionym) for a given taxonID, according to a given reference
     *
     * @param string $referenceType type of reference (periodical, citation, service, etc.)
     * @param int $referenceID ID of reference
     * @param int $taxonID ID of taxon name
     * @param int $insertSeries optional ID of cication-Series to insert
     * @return array List of synonyms including extra information
     */
    public function getSynonyms($referenceType, $referenceID, $taxonID, $insertSeries = 0): array
    {
        $results = [];
        $basID = 0;
        $basionymResult = null;

        $basionym = $this->getBasionym($taxonID);

        if ($basionym !== null) {
            $basionymID = $basionym['basID'];
            $basionymResult = array(
                "taxonID" => $basID,
                "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $basionymID], UrlGeneratorInterface::ABSOLUTE_URL)),
                "referenceName" => $basionym['scientificName'],
                "referenceId" => $referenceID,
                "referenceType" => $referenceType,
                "hasType" => $this->hasType($basionymID),
                "hasSpecimen" => $this->hasSpecimen($basionymID),
                "insertedCitation" => false,
                "referenceInfo" => array(
                    "type" => "homotype",
                    "cited" => false
                ),
            );
        }

        switch (trim($referenceType)) {
            case 'citation': //TODO only citation type is implemented, rearrange?
                $synonyms = $this->findSynonyms($taxonID, $referenceID);
                foreach ($synonyms as $synonym) {
                    // ignore if synonym is basionym
                    if ($basionym !== null && $synonym['taxonID'] == $basionymID) {
                        $basionymResult["referenceInfo"]["cited"] = true;
                    } else {
                        $results[] = array(
                            "taxonID" => intval($synonym['taxonID']),
                            "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $synonym['taxonID']], UrlGeneratorInterface::ABSOLUTE_URL)),

                            "referenceName" => $synonym['scientificName'],
                            "referenceId" => $referenceID,
                            "referenceType" => $referenceType,
                            "hasType" => $this->hasType($synonym['taxonID']),
                            "hasSpecimen" => $this->hasSpecimen($synonym['taxonID']),
                            "insertedCitation" => false,
                            "referenceInfo" => array(
                                "type" => ($synonym['homotype'] > 0) ? "homotype" : "heterotype",
                                'cited' => true
                            ),
                        );
                        $insertedCitations = $this->getInsertedCitation($insertSeries, $referenceID, $synonym['taxonID']);
                        if (!empty($insertedCitations)) {
                            foreach ($insertedCitations as $citation) {
                                $results[] = array(
                                    "taxonID" => $citation['taxonID'],
                                    "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $citation['taxonID']], UrlGeneratorInterface::ABSOLUTE_URL)),
                                    "referenceId" => $citation['referenceId'],
                                    "referenceName" => $citation['referenceName'],
                                    "referenceType" => $citation['referenceType'],
                                    "hasChildren" => $citation['hasChildren'],
                                    "insertedCitation" => true,
                                );
                            }
                        }
                    }
                }
                break;
        }

        // if we have a basionym, prepend it to list
        if ($basionymResult != null) {
            $insertedCitations = $this->getInsertedCitation($insertSeries, $referenceID, $basionymResult['taxonID']);
            if (!empty($insertedCitations)) {
                foreach ($insertedCitations as $citation) {
                    $buffer = array(
                        "taxonID" => $citation['taxonID'],
                        "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $citation['taxonID']], UrlGeneratorInterface::ABSOLUTE_URL)),
                        "referenceId" => $citation['referenceId'],
                        "referenceName" => $citation['referenceName'],
                        "referenceType" => $citation['referenceType'],
                        "hasChildren" => $citation['hasChildren'],
                        "insertedCitation" => true,
                    );
                    array_unshift($results, $buffer);
                }
            }
            array_unshift($results, $basionymResult);
        }

        return $results;
    }

    protected function getBasionym(int $taxonID): ?array
    {
        $sql = "SELECT `herbar_view`.GetScientificName(`ts`.`basID`, 0) AS `scientificName`, ts.basID
            FROM tbl_tax_species ts
            WHERE ts.taxonID = :taxonID
             AND ts.basID IS NOT NULL";
        $basionym = $this->entityManager->getConnection()->executeQuery($sql, ['taxonID' => $taxonID])->fetchAssociative();
        if ($basionym === false) {
            return null;
        }
        return $basionym;
    }

    protected function findSynonyms(int $taxonID, int $referenceID): array
    {
        $sql = "SELECT `herbar_view`.GetScientificName( ts.taxonID, 0 ) AS scientificName, ts.taxonID, (tsp.basID = tsp_source.basID) AS homotype
                    FROM tbl_tax_synonymy ts
                     LEFT JOIN tbl_tax_species tsp ON tsp.taxonID = ts.taxonID
                     LEFT JOIN tbl_tax_species tsp_source ON tsp_source.taxonID = ts.acc_taxon_ID
                    WHERE ts.acc_taxon_ID = :taxonID
                     AND source_citationID = :referenceID";
        return $this->entityManager->getConnection()->executeQuery($sql, ['taxonID' => $taxonID, 'referenceID' => $referenceID])->fetchAllAssociative();
    }

}
