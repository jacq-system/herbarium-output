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

    protected function protolog($row): string
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

}
