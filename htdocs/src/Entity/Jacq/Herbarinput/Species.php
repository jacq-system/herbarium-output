<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_tax_species', schema: 'herbarinput')]
class Species
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'taxonID')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Genus::class)]
    #[ORM\JoinColumn(name: 'genID', referencedColumnName: 'genID')]
    private Genus $genus;

    #[ORM\ManyToOne(targetEntity: Authors::class)]
    #[ORM\JoinColumn(name: 'authorID', referencedColumnName: 'authorID')]
    private Authors $author;

    #[ORM\ManyToOne(targetEntity: EpithetSpecies::class)]
    #[ORM\JoinColumn(name: 'speciesID', referencedColumnName: 'epithetID')]
    private EpithetSpecies $epithet;

    #[ORM\Column(name: 'basID')]
    private int $basID;

    #[ORM\Column(name: 'synID')]
    private int $synID;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGenus(): Genus
    {
        return $this->genus;
    }

    public function getAuthor(): Authors
    {
        return $this->author;
    }

    public function getEpithet(): EpithetSpecies
    {
        return $this->epithet;
    }


}
