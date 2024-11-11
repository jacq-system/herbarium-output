<?php declare(strict_types = 1);

namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;

readonly class TaxonNameService
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
}
