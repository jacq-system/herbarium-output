<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_geo_nation', schema: 'herbarinput')]
class Country
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'NationID')]
    private ?int $id = null;

    #[ORM\Column(name: 'nation')]
    private string $name;

    #[ORM\Column(name: 'nation_engl')]
    private string $nameEng;

    #[ORM\Column(name: 'language_variants')]
    private string $variants;

}
