<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_tax_classification', schema: 'herbarinput')]
class Classification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'classification_id')]
    private ?int $id = null;

    #[ORM\Column(name: 'parent_taxonID')]
    private int $parentTaxonId;

    #[ORM\Column(name: 'order')]
    private int $sort;

    #[ORM\OneToOne(targetEntity: Synonymy::class, inversedBy: 'classification')]
    #[ORM\JoinColumn(name: 'tax_syn_ID', referencedColumnName: 'tax_syn_ID')]
    private Synonymy $synonymy;

    public function getParentTaxonId(): int
    {
        return $this->parentTaxonId;
    }

    public function getSynonymy(): Synonymy
    {
        return $this->synonymy;
    }

    public function getSort(): int
    {
        return $this->sort;
    }




}
