<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_collector_2', schema: 'herbarinput')]
class Collector2
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Sammler_2ID')]
    private ?int $id = null;

    #[ORM\Column(name: 'Sammler_2')]
    private string $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

}
