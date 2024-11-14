<?php declare(strict_types = 1);

namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;

readonly class TaxonService
{

    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function autocompleteStartsWith(string $term): array
    {
        $words = preg_split('/\s+/', $term, 2);
        if (empty($words)) {
            return [];
        }
        if (count($words) === 2) {
            $sql = <<<SQL
                SELECT ts.taxonID, herbar_view.GetScientificName(ts.taxonID, 0) AS ScientificName
                FROM herbarinput.tbl_tax_species ts
                LEFT JOIN herbarinput.tbl_tax_genera tg ON tg.genID = ts.genID
                LEFT JOIN herbarinput.tbl_tax_epithets te ON te.epithetID = ts.speciesID
                WHERE
                    ts.external = 0
                    AND tg.genus LIKE :piece0
                    AND te.epithet LIKE :piece1
                HAVING ScientificName != ''
                ORDER BY ScientificName
                SQL;

            return $this->entityManager->getConnection()->executeQuery($sql, ['piece0' => $words[0] . '%', 'piece1' => $words[1] . '%'])->fetchAllAssociative();
        } else {
            $sql = <<<SQL
                SELECT ts.taxonID, herbar_view.GetScientificName(ts.taxonID, 0) AS ScientificName
                FROM herbarinput.tbl_tax_species ts
                LEFT JOIN herbarinput.tbl_tax_genera tg ON tg.genID = ts.genID
                WHERE
                    ts.external = 0
                    AND tg.genus LIKE :piece0
                    AND ts.speciesID IS NULL
                HAVING ScientificName != ''
                ORDER BY ScientificName
                SQL;

            return $this->entityManager->getConnection()->executeQuery($sql, ['piece0' => $words[0] . '%'])->fetchAllAssociative();

        }
    }

    public function fulltextSearch(string $term): array
    {
        $words = preg_split('/\s+/', $term);
        if (empty($words)) {
            return [];
        }
        $searchTerm = '+' . implode(" +", $words);
        $sql = <<<SQL
                SELECT taxonID, scientificName, taxonName
                FROM `tbl_tax_sciname`
                WHERE
                    MATCH(scientificName) against(:searchTerm IN BOOLEAN MODE)
                    OR MATCH(taxonName) against(:searchTerm IN BOOLEAN MODE)
                ORDER BY scientificName
                SQL;
        return $this->entityManager->getConnection()->executeQuery($sql, ['searchTerm' => $searchTerm])->fetchAllAssociative();
    }

    public function findByUuid(string $uuid): ?int
    {
     return NULL; //TODO
    }

    /**
     * check if the accepted taxon is part of a classification
     * only select entries which are part of a classification, so either tc.tax_syn_ID or has_children_syn.tax_syn_ID must not be NULL
     */
    public function isAcceptedTaxonPartOfClassification(int $referenceId, int $acceptedId): bool
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

    public function getBasionym(int $taxonID): ?array
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

    /**
     * Are there any type records of a given taxonID?
     *
     * @param int $taxonID ID of taxon
     */
    public function hasType(int $taxonID): bool
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
    public function hasSpecimen(int $taxonID): bool
    {
        $sql = "SELECT specimen_ID FROM tbl_specimens WHERE taxonID = :taxonID";
        return (bool)$this->entityManager->getConnection()->executeQuery($sql, ['taxonID' => $taxonID])->fetchAssociative();
    }

    public function findSynonyms(int $taxonID, int $referenceID): array
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
