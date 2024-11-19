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

    public function getCitationReferences(?int $referenceID): array
    {
        if (!empty($referenceID)) {
            $sql = <<<SQL
                    SELECT titel AS `name`, citationID AS `id`
                    FROM tbl_lit
                    WHERE citationID = :id
                    SQL;
            return $this->entityManager->getConnection()->executeQuery($sql, ['id' => $referenceID])->fetchAllAssociative();
            //TODO fetchAssociative make sense when ID is provided, but to fulfill compatibility with original keep single element array
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

    public function getPeriodicalReferences(?int $referenceID): array
    {
        if (!empty($referenceID)) {
            $sql = <<<SQL
                    SELECT periodical AS `name`, periodicalID AS `id`
                    FROM tbl_lit_periodicals
                    WHERE periodicalID = :id
                    SQL;
            return $this->entityManager->getConnection()->executeQuery($sql, ['id' => $referenceID])->fetchAllAssociative();
            //TODO see above
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
     * check if there are any classification children of the taxonID according to this reference
     */
    public function hasClassificationChildren(int $taxonID, int $referenceID): bool
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
     * get all citations which belong to the given periodical
     */
    public function getPeriodicalChildrenReferences(int $referenceID): array
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
    public function getCitationChildrenReferences(int $referenceID, int $taxonID): array
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

    public function findCitations(int $insertSeries, int $referenceID, int $taxonID): array
    {
        $sql = "SELECT citationID
                FROM tbl_classification_citation_insert
                WHERE series = :insertSeries
                 AND taxonID = :taxonID
                 AND referenceId = :referenceID
                ORDER BY sequence";
        return $this->entityManager->getConnection()->executeQuery($sql, ['taxonID' => $taxonID, 'insertSeries' => $insertSeries, 'referenceID' => $referenceID])->fetchAllAssociative();

    }

    public function getCitationName(int $id): ?string
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


}
