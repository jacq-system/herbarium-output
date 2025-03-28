<?php declare(strict_types=1);

namespace App\Service;


use App\Entity\Jacq\Herbarinput\Species;
use App\Entity\Jacq\Herbarinput\Specimens;
use Doctrine\ORM\EntityManagerInterface;

readonly class TypusService
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function protolog($row): string
    {
        $text = "";
        if ($row['suptitel']) {
            $text .= "in " . $row['autor'] . ": " . $row['suptitel'] . " ";
        }
        if ($row['periodicalID']) {
            $text .= $row['periodical'];
        }
        $text .= " " . $row['vol'];
        if ($row['part']) {
            $text .= " (" . $row['part'] . ")";
        }
        $text .= ": " . $row['paginae'];
        if ($row['figures']) {
            $text .= "; " . $row['figures'];
        }
        $text .= " (" . $row['jahr'] . ")";

        return $text;
    }

    public function taxonWithHybrids($row): string
    {
        if ($row['statusID'] == 1 && strlen((string)$row['epithet']) == 0 && strlen((string)$row['author']) == 0) {
            $sql = "SELECT parent_1_ID, parent_2_ID
                        FROM tbl_tax_hybrids
                        WHERE taxon_ID_fk = :taxon";
            $rowHybrid = $this->entityManager->getConnection()->executeQuery($sql, ['taxon' => $row['taxonID']])->fetchAssociative();
            $sql1 = "SELECT tg.genus,
                                    ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                                    ta4.author author4, ta5.author author5,
                                    te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                                    te4.epithet epithet4, te5.epithet epithet5
                                   FROM tbl_tax_species ts
                                    LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
                                    LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                                    LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                                    LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                                    LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                                    LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                                    LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                                    LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                                    LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                                    LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                                    LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                                    LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                                    LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                                   WHERE taxonID = :parent";
            $row1 = $this->entityManager->getConnection()->executeQuery($sql1, ['parent' => $rowHybrid['parent_1_ID']])->fetchAssociative();

            $sql2 = "SELECT tg.genus,
                                    ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                                    ta4.author author4, ta5.author author5,
                                    te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                                    te4.epithet epithet4, te5.epithet epithet5
                                   FROM tbl_tax_species ts
                                    LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
                                    LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                                    LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                                    LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                                    LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                                    LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                                    LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                                    LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                                    LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                                    LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                                    LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                                    LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                                    LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                                   WHERE taxonID = :parent2";
            $row2 = $this->entityManager->getConnection()->executeQuery($sql2, ['parent2' => $rowHybrid['parent_2_ID']])->fetchAssociative();

            return $this->taxon($row1) . " x " . $this->taxon($row2);
        }

        return $this->taxon($row);

    }

    protected function taxon($row)
    {
        $text = $row['genus'] ?? '';
        if (!empty($row['epithet'])) {
            $text .= " " . $row['epithet'] . " " . $row['author'];
        }
        if (!empty($row['epithet1'])) {
            $text .= " subsp. " . $row['epithet1'] . " " . $row['author1'];
        }
        if (!empty($row['epithet2'])) {
            $text .= " var. " . $row['epithet2'] . " " . $row['author2'];
        }
        if (!empty($row['epithet3'])) {
            $text .= " subvar. " . $row['epithet3'] . " " . $row['author3'];
        }
        if (!empty($row['epithet4'])) {
            $text .= " forma " . $row['epithet4'] . " " . $row['author4'];
        }
        if (!empty($row['epithet5'])) {
            $text .= " subforma " . $row['epithet5'] . " " . $row['author5'];
        }

        return $text;
    }

    public function getProtologs(Species $species): array
    {
        $text = [];
        $sql = "SELECT l.suptitel, la.autor, l.periodicalID, lp.periodical, l.vol, l.part, ti.paginae, ti.figures, l.jahr
                 FROM tbl_tax_index ti
                  INNER JOIN tbl_lit l ON l.citationID=ti.citationID
                  LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
                  LEFT JOIN tbl_lit_authors la ON la.autorID=l.editorsID
                 WHERE ti.taxonID=:taxon";
        $result = $this->entityManager->getConnection()->executeQuery($sql, ['taxon' => $species->getId()]);
        while ($row = $result->fetchAssociative()) {
            $text[] = $this->protolog($row);
        }
        return $text;
    }

    public function taxonNameWithHybrids(Species $species, bool $html = false): string
    {
        if ($species->isHybrid()) {
            $sql = "SELECT parent_1_ID as parent1, parent_2_ID as parent2
                        FROM tbl_tax_hybrids
                        WHERE taxon_ID_fk = :taxon";
            $rowHybrids = $this->entityManager->getConnection()->executeQuery($sql, ['taxon' => $species->getId()])->fetchAssociative();
            $parent1 = $this->entityManager->getRepository(Species::class)->find($rowHybrids['parent1']);
            $parent2 = $this->entityManager->getRepository(Species::class)->find($rowHybrids['parent2']);
            return $parent1->getFullName($html) . " x " . $parent2->getFullName($html);
        }

        return $species->getFullName($html);

    }

    public function getTypusText(Specimens $specimen): string
    {
        $text = '';
        foreach ($specimen->getTypus() as $typus) {
            $text .= $typus->getRank()->getLatinName() . ' for ' . $this->taxonNameWithHybrids($specimen->getSpecies());
            $text .= '';
            foreach ($this->getProtologs($typus->getSpecies()) as $protolog) {
                $text .= $protolog . ' ';
            }
        }
        if ($specimen->getSpecies()->isSynonym()) {
            $text .= "Current Name: " . $this->taxonNameWithHybrids($specimen->getSpecies());
        }
        return $text;

    }

}
