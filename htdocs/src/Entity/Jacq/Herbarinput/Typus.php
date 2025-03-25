<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_specimens_types', schema: 'herbarinput')]
class Typus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'specimens_types_ID')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Specimens::class, inversedBy: 'typus')]
    #[ORM\JoinColumn(name: 'specimenID', referencedColumnName: 'specimen_ID')]
    private Specimens $specimen;

    #[ORM\ManyToOne(targetEntity: TypusRank::class)]
    #[ORM\JoinColumn(name: 'typusID', referencedColumnName: 'typusID')]
    private TypusRank $rank;

    #[ORM\ManyToOne(targetEntity: Species::class)]
    #[ORM\JoinColumn(name: 'taxonID', referencedColumnName: 'taxonID')]
    private Species $species;

    public function getRank(): TypusRank
    {
        return $this->rank;
    }

    public function getSpecies(): Species
    {
        return $this->species;
    }

    #[ORM\Column(name: 'typified_by_Person ')]
    private string $person;

    #[ORM\Column(name: 'typified_Date')]
    private string $date;

    public function getPerson(): string
    {
        return $this->person;
    }

    public function getDate(): string
    {
        return $this->date;
    }

}
