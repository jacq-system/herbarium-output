<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_tax_families', schema: 'herbarinput')]
class Family
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'familyID')]
    private ?int $id = null;

    #[ORM\Column(name: 'family')]
    private string $name;

    #[ORM\Column(name: 'family_alt')]
    private string $nameAlternative;
}
