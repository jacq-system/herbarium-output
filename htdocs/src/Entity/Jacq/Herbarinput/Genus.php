<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_tax_genera', schema: 'herbarinput')]
class Genus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'genID')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Family::class)]
    #[ORM\JoinColumn(name: 'familyID', referencedColumnName: 'familyID')]
    private Family $family;

    #[ORM\Column(name: 'genus')]
    private string $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function getFamily(): Family
    {
        return $this->family;
    }

}
