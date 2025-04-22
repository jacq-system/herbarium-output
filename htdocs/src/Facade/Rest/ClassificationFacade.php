<?php declare(strict_types=1);

namespace App\Facade\Rest;


use App\Repository\Herbarinput\LiteratureRepository;
use App\Repository\Herbarinput\SynonymyRepository;
use App\Service\ReferenceService;
use App\Service\TaxonService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

readonly class ClassificationFacade
{
    public function __construct(protected ReferenceService $referenceService, protected EntityManagerInterface $entityManager, protected TaxonService $taxonService, protected RouterInterface $router, protected LiteratureRepository $literatureRepository, protected SynonymyRepository $synonymyRepository)
    {
    }

    /**
     * Fetch a list of all references (which have a classification attached)
     *
     * @param string $referenceType Type of references to return (citation, person, service, specimen, periodical)
     * @param ?int $referenceID optional ID of reference
     * @return array References information
     */
    public function resolveByType(string $referenceType, ?int $referenceID): array
    {
//            //TODO  these three from oirignal code are not supported or do not exists?
//            case 'person':
//            case 'service':
//            case 'specimen':
        return match ($referenceType) {
            'citation' => $this->referenceService->getCitationReferences($referenceID),
            'periodical' => $this->referenceService->getPeriodicalReferences($referenceID),
            default => [],
        };
    }

    /**
     * Return (other) references for this name which include them in their classification
     */
    public function resolveNameReferences(int $taxonID, int $excludeReferenceId = 0, int $insertSeries = 0): array
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
                    "hasChildren" => $this->synonymyRepository->hasClassificationChildren($taxonID, $dbRow['referenceId']),
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
            if ($this->taxonService->isAcceptedTaxonPartOfClassification($dbSyn['referenceId'], $dbSyn['acceptedId'])) {
                $results[] = array("referenceName" => '= ' . $dbSyn['referenceName'],  //  mark the reference Name as synonym
                    "referenceId" => intval($dbSyn['referenceId']), "referenceType" => "citation", "taxonID" => $taxonID, "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $taxonID], UrlGeneratorInterface::ABSOLUTE_URL)), "hasChildren" => false, "hasType" => false, "hasSpecimen" => false,);
            }
        }

        return $results;
    }

    /**
     * Get number of classification children who have children themselves of a given taxonID according to a given reference of type citation
     *
     * @param int $referenceID ID of reference (citation)
     * @param ?int $taxonID ID of taxon
     */
    public function resolveNumberOfChildrenWithChildrenCitation(int $referenceID, ?int $taxonID = 0): int
    {
        $resultNumber = 0;
        $stack = array();

        $stack[] = intval($taxonID);
        do {
            $taxonID = array_pop($stack);

            // basic query
            $sql = "SELECT ts.taxonID,
                       max(`has_children`.`tax_syn_ID` IS NOT NULL) AS `hasChildren`,
                       max(`has_synonyms`.`tax_syn_ID` IS NOT NULL) AS `hasSynonyms`,
                       max(`has_basionym`.`basID` IS NOT NULL) AS `hasBasionym`
                FROM tbl_tax_synonymy ts
                 LEFT JOIN tbl_tax_species tsp ON ts.taxonID = tsp.taxonID
                 LEFT JOIN tbl_tax_rank tr ON tsp.tax_rankID = tr.tax_rankID
                 LEFT JOIN tbl_tax_classification tc ON ts.tax_syn_ID = tc.tax_syn_ID
                 LEFT JOIN tbl_tax_synonymy has_synonyms ON (has_synonyms.acc_taxon_ID = ts.taxonID AND has_synonyms.source_citationID = ts.source_citationID)
                 LEFT JOIN tbl_tax_classification has_children_clas ON has_children_clas.parent_taxonID = ts.taxonID
                 LEFT JOIN tbl_tax_synonymy has_children ON (has_children.tax_syn_ID = has_children_clas.tax_syn_ID AND has_children.source_citationID = ts.source_citationID)
                 LEFT JOIN tbl_tax_species has_basionym ON ts.taxonID = has_basionym.taxonID
                WHERE ts.source_citationID = :referenceID
                 AND ts.acc_taxon_ID IS NULL ";

            // check if we search for children of a specific taxon
            if ($taxonID > 0) {
                $sql .= " AND tc.parent_taxonID = :taxonID ";
            } // .. if not make sure we only return entries which have at least one child
            else {
                $sql .= " AND tc.parent_taxonID IS NULL
                      AND has_children.tax_syn_ID IS NOT NULL ";
            }

            $dbRows = $this->entityManager->getConnection()->executeQuery($sql, ['taxonID' => $taxonID, 'referenceID' => $referenceID])->fetchAllAssociative();

            // process all results and create response from it
            //TODO modify query with HAVING clause and fetch the counts themself?
            foreach ($dbRows as $dbRow) {
                if ($dbRow['hasChildren'] > 0 || $dbRow['hasSynonyms'] > 0 || $dbRow['hasBasionym']) {
                    $stack[] = $dbRow['taxonID'];
                    $resultNumber++;
                }
            }
        } while (!empty($stack));

        return $resultNumber;
    }

    /**
     * Get statistics information of a given reference
     */
    public function resolvePeriodicalStatistics(int $referenceID): array
    {
        $results = array();

        $sql = "SELECT count(*) AS number
                  FROM tbl_tax_synonymy
                  WHERE source_citationID = :referenceID
                   AND acc_taxon_ID IS NULL";
        $results["nrAccTaxa"] = $this->entityManager->getConnection()->executeQuery($sql, ['referenceID' => $referenceID])->fetchOne();


        $sql = "SELECT count(*) AS number
               FROM tbl_tax_synonymy
               WHERE source_citationID = :referenceID
                AND acc_taxon_ID IS NOT NULL";
        $results["nrSynonyms"] = $this->entityManager->getConnection()->executeQuery($sql, ['referenceID' => $referenceID])->fetchOne();


        $sql = "SELECT tr.rank_plural, tr.rank_hierarchy, count(tr.tax_rankID) AS number
                   FROM tbl_tax_synonymy ts
                    LEFT JOIN tbl_tax_species tsp ON ts.taxonID = tsp.taxonID
                    LEFT JOIN tbl_tax_rank tr ON tsp.tax_rankID = tr.tax_rankID
                   WHERE source_citationID = :referenceID
                    AND acc_taxon_ID IS NULL
                   GROUP BY tr.tax_rankID
                   ORDER BY tr.rank_hierarchy";
        $dbRowsAcc = $this->entityManager->getConnection()->executeQuery($sql, ['referenceID' => $referenceID])->fetchAllAssociative();

        $sql = "SELECT tr.rank_plural, tr.rank_hierarchy, count(tr.tax_rankID) AS number
                   FROM tbl_tax_synonymy ts
                    LEFT JOIN tbl_tax_species tsp ON ts.taxonID = tsp.taxonID
                    LEFT JOIN tbl_tax_rank tr ON tsp.tax_rankID = tr.tax_rankID
                   WHERE source_citationID = :referenceID
                    AND acc_taxon_ID IS NOT NULL
                   GROUP BY tr.tax_rankID
                   ORDER BY tr.rank_hierarchy";
        $dbRowsSyn = $this->entityManager->getConnection()->executeQuery($sql, ['referenceID' => $referenceID])->fetchAllAssociative();


        $rows = array();
        foreach ($dbRowsAcc as $dbRow) {
            $rows[$dbRow['rank_hierarchy']]['acc'] = array("rank" => $dbRow['rank_plural'], "number" => $dbRow['number']);
        }
        foreach ($dbRowsSyn as $dbRow) {
            $rows[$dbRow['rank_hierarchy']]['syn'] = array("rank" => $dbRow['rank_plural'], "number" => $dbRow['number']);
        }

        $results["ranks"] = array();
        foreach ($rows as $row) {
            if (isset($row['acc'])) {
                $buffer['rank'] = $row['acc']['rank'];
                $buffer['nrAccTaxa'] = $row['acc']['number'];
            } else {
                $buffer['nrAccTaxa'] = 0;
            }
            if (isset($row['syn'])) {
                $buffer['rank'] = $row['syn']['rank'];
                $buffer['nrSynTaxa'] = $row['syn']['number'];
            } else {
                $buffer['nrSynTaxa'] = 0;
            }
            $results["ranks"][] = $buffer;
        }

        return $results;
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
    public function resolveChildren(string $referenceType, int $referenceID, int $taxonID, int $insertSeries): array
    {
        $results = array();

        switch ($referenceType) {
            case 'periodical':
                $dbRows = $this->literatureRepository->getChildrenReferences($referenceID);
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
                $dbRows = $this->referenceService->getCitationChildrenReferences($referenceID, $taxonID);
                foreach ($dbRows as $dbRow) {
                    $results[] = array(
                        "taxonID" => intval($dbRow['taxonID']),
                        "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $taxonID], UrlGeneratorInterface::ABSOLUTE_URL)),
                        "referenceId" => $referenceID,
                        "referenceName" => $dbRow['scientificName'],
                        "referenceType" => "citation",
                        "hasChildren" => ($dbRow['hasChildren'] > 0 || $dbRow['hasSynonyms'] > 0 || $dbRow['hasBasionym']),
                        "hasType" => $this->taxonService->hasType($dbRow['taxonID']),
                        "hasSpecimen" => $this->taxonService->hasSpecimen($dbRow['taxonID']),
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
     * fetch synonyms (and basionym) for a given taxonID, according to a given reference
     *
     * @param string $referenceType type of reference (periodical, citation, service, etc.)
     * @param int $referenceID ID of reference
     * @param int $taxonID ID of taxon name
     * @param int $insertSeries optional ID of cication-Series to insert
     * @return array List of synonyms including extra information
     */
    public function resolveSynonyms($referenceType, $referenceID, $taxonID, $insertSeries = 0): array
    {
        $results = [];
        $basID = 0;
        $basionymResult = null;

        $basionym = $this->taxonService->getBasionym($taxonID);

        if ($basionym !== null) {
            $basionymID = $basionym['basID'];
            $basionymResult = array(
                "taxonID" => $basID,
                "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $basionymID], UrlGeneratorInterface::ABSOLUTE_URL)),
                "referenceName" => $basionym['scientificName'],
                "referenceId" => $referenceID,
                "referenceType" => $referenceType,
                "hasType" => $this->taxonService->hasType($basionymID),
                "hasSpecimen" => $this->taxonService->hasSpecimen($basionymID),
                "insertedCitation" => false,
                "referenceInfo" => array(
                    "type" => "homotype",
                    "cited" => false
                ),
            );
        }

        switch (trim($referenceType)) {
            case 'citation': //TODO only citation type is implemented, rearrange?
                $synonyms = $this->taxonService->findSynonyms($taxonID, $referenceID);
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
                            "hasType" => $this->taxonService->hasType($synonym['taxonID']),
                            "hasSpecimen" => $this->taxonService->hasSpecimen($synonym['taxonID']),
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

    /**
     * Get the parent entry of a given reference
     *
     * @param string $referenceType type of reference (periodical, citation, service, etc.)
     * @param int $referenceId ID of reference
     * @param int $taxonID ID of taxon name
     */
    public function resolveParent(string $referenceType, int $referenceId, int $taxonID): ?array
    {
        $parent = null;

        switch ($referenceType) {
            case 'periodical':
                // periodical is a top level element, so no parent
                return null;
            case 'citation':
            default:
                // only necessary if taxonID is not null
                //TODO taxonID is required parameter of the route, can't be null..
                if ($taxonID > 0) {
                    $sql = "SELECT `herbar_view`.GetScientificName( ts.`taxonID`, 0 ) AS `referenceName`, tc.number, tc.order, ts.taxonID
                                            FROM tbl_tax_synonymy ts
                                             LEFT JOIN tbl_tax_classification tc ON ts.tax_syn_ID = tc.tax_syn_ID
                                             LEFT JOIN tbl_tax_classification tcchild ON ts.taxonID = tcchild.parent_taxonID
                                             LEFT JOIN tbl_tax_synonymy tschild ON (    tschild.source_citationID = ts.source_citationID
                                                                                    AND tcchild.tax_syn_ID = tschild.tax_syn_ID)
                                            WHERE ts.source_citationID = :referenceId
                                             AND ts.acc_taxon_ID IS NULL
                                             AND tschild.taxonID = :taxonID";

                    $dbRow = $this->entityManager->getConnection()->executeQuery($sql, ['taxonID' => $taxonID, 'referenceId' => $referenceId])->fetchAssociative();
                    // check if we found a parent
                    if ($dbRow) {
                        $parent = array(
                            "taxonID" => $dbRow['taxonID'],
                            "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $dbRow['taxonID']], UrlGeneratorInterface::ABSOLUTE_URL)),
                            "referenceId" => $referenceId,
                            "referenceName" => $dbRow['referenceName'],
                            "referenceType" => "citation",
                            "hasType" => $this->taxonService->hasType($dbRow['taxonID']),
                            "hasSpecimen" => $this->taxonService->hasSpecimen($dbRow['taxonID']),
                            "referenceInfo" => array(
                                "number" => $dbRow['number'],
                                "order" => $dbRow['order']
                            )
                        );
                    } // if not we either have a synonym and have to search for an accepted taxon or have to return the citation entry
                    else {
                        $sql = "SELECT `herbar_view`.GetScientificName( taxonID, 0 ) AS referenceName, acc_taxon_ID
                                                  FROM tbl_tax_synonymy
                                                  WHERE taxonID = :taxonID
                                                   AND source_citationID = :referenceId
                                                   AND acc_taxon_ID IS NOT NULL";
                        $accTaxon = $this->entityManager->getConnection()->executeQuery($sql, ['taxonID' => $taxonID, 'referenceId' => $referenceId])->fetchAssociative();
                        // if we have found an accepted taxon for our synonym then return it
                        if ($accTaxon) {
                            $parent = array(
                                "taxonID" => $accTaxon['acc_taxon_ID'],
                                "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $accTaxon['acc_taxon_ID']], UrlGeneratorInterface::ABSOLUTE_URL)),
                                "referenceId" => $referenceId,
                                "referenceName" => $accTaxon['referenceName'],
                                "referenceType" => "citation",
                                "hasType" => $this->taxonService->hasType($accTaxon['acc_taxon_ID']),
                                "hasSpecimen" => $this->taxonService->hasSpecimen($accTaxon['acc_taxon_ID'])
                            );
                        } // if not we have to return the citation entry
                        else {
                            $sql = "SELECT `herbar_view`.GetProtolog(l.citationID) AS referenceName, l.citationID AS referenceId
                                                    FROM tbl_lit l
                                                    WHERE l.citationID = :referenceId";
                            $citation = $this->entityManager->getConnection()->executeQuery($sql, ['referenceId' => $referenceId])->fetchAssociative();

                            if ($citation) {
                                $parent = array(
                                    "taxonID" => 0,
                                    "referenceId" => $citation['referenceId'],
                                    "referenceName" => $citation['referenceName'],
                                    "referenceType" => "citation",
                                    "hasType" => false,
                                    "hasSpecimen" => false
                                );
                            }
                        }
                    }
                } // find the top-level periodical entry
                else {
                    $sql = "SELECT lp.periodical AS referenceName, l.periodicalID AS referenceId
                                            FROM tbl_lit_periodicals lp
                                             LEFT JOIN tbl_lit l ON l.periodicalID = lp.periodicalID
                                            WHERE l.citationID = :referenceId";
                    $periodical = $this->entityManager->getConnection()->executeQuery($sql, ['referenceId' => $referenceId])->fetchAssociative();

                    if ($periodical) {
                        $parent = array(
                            "taxonID" => 0,
                            "referenceId" => $periodical['referenceId'],
                            "referenceName" => $periodical['referenceName'],
                            "referenceType" => "periodical",
                            "hasType" => false,
                            "hasSpecimen" => false
                        );
                    }
                }
                break;
        }

        // return results
        return $parent;
    }

    protected function getInsertedCitation(int $insertSeries, int $referenceID, int $taxonID): array
    {
        $results = [];

        $ids = $this->referenceService->findCitationsId($insertSeries, $referenceID, $taxonID);
        foreach ($ids as $id) {
            $results[] = array(
                'referenceName' => $this->literatureRepository->getProtolog($id),
                'referenceId' => $id,
                "referenceType" => "citation",
                "taxonID" => $taxonID,
                "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $taxonID], UrlGeneratorInterface::ABSOLUTE_URL)),
                "hasChildren" => $this->synonymyRepository->hasClassificationChildren($taxonID, $id),
            );
        }

        return $results;
    }
}
