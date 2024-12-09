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
}
