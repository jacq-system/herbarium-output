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
     * TODO refactoring mostly syntax - having no idea what is going on :-)
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
                $results[] = array(
                    "referenceName" => $dbRow['referenceName'],
                    "referenceId" => intval($dbRow['referenceId']),
                    "referenceType" => "citation",
                    "taxonID" => $taxonID,
                    "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $taxonID], UrlGeneratorInterface::ABSOLUTE_URL)),
                    "hasChildren" => $this->hasClassificationChildren($taxonID, $dbRow['referenceId']),
                    "hasType" => false, //TODO always false?
                    "hasSpecimen" => false //TODO always false?
                );
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
                $results[] = array(
                    "referenceName" => '= ' . $dbSyn['referenceName'],  //  mark the reference Name as synonym
                    "referenceId" => intval($dbSyn['referenceId']),
                    "referenceType" => "citation",
                    "taxonID" => $taxonID,
                    "uuid" => array('href' => $this->router->generate('services_rest_scinames_uuid', ['taxonID' => $taxonID], UrlGeneratorInterface::ABSOLUTE_URL)),
                    "hasChildren" => false,
                    "hasType" => false,
                    "hasSpecimen" => false,
                );
            }
        }

        return $results;
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
            $hasChildren = (bool) $child;
        }
        return $hasChildren;
    }
}
