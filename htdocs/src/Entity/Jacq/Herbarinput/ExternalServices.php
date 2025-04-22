<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_nom_service', schema: 'herbarinput')]
class ExternalServices
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'serviceID')]
    private ?int $id = null;


    #[ORM\Column(name: 'name')]
    private string $name;

    public function getName(): string
    {
        return $this->name;
    }


}
