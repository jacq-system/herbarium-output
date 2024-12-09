<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_geo_province', schema: 'herbarinput')]
class Province
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'provinceID')]
    private ?int $id = null;

    #[ORM\Column(name: 'provinz')]
    private string $name;

    #[ORM\Column(name: 'provinz_local')]
    private string $nameLocal;

}
