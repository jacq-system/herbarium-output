<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_tax_synonymy', schema: 'herbarinput')]
class Synonymy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'tax_syn_ID')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Literature::class)]
    #[ORM\JoinColumn(name: 'source_citationID', referencedColumnName: 'citationID')]
    private Literature $literature;

    #[ORM\ManyToOne(targetEntity: Species::class)]
    #[ORM\JoinColumn(name: 'taxonID', referencedColumnName: 'taxonID')]
    private Species $species;

    #[ORM\Column(name: 'acc_taxon_ID')]
    private ?int $actualTaxonId = null;

    #[ORM\OneToOne(targetEntity: Classification::class, mappedBy: 'synonymy')]
    private ?Classification $classification;

    public function getLiterature(): Literature
    {
        return $this->literature;
    }

    public function getSpecies(): Species
    {
        return $this->species;
    }

    public function getActualTaxonId(): ?int
    {
        return $this->actualTaxonId;
    }

    public function getClassification(): ?Classification
    {
        return $this->classification;
    }


}
