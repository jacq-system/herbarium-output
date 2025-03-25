<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_typi', schema: 'herbarinput')]
class TypusRank
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'typusID')]
    private ?int $id = null;


    #[ORM\Column(name: 'typus_lat')]
    private string $latinName;

    public function getLatinName(): string
    {
        return $this->latinName;
    }


}
