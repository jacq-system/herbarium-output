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

    #[ORM\Column(name: 'basID')]
    private int $basID;

    #[ORM\Column(name: 'synID')]
    private int $synID;
}
