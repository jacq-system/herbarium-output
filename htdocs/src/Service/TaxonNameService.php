<?php

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
}
