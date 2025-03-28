<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_specimens_identstatus', schema: 'herbarinput')]
class IdentificationStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'identstatusID')]
    private ?int $id = null;


    #[ORM\Column(name: 'identification_status')]
    private string $name;

    public function getName(): string
    {
        return $this->name;
    }


}
