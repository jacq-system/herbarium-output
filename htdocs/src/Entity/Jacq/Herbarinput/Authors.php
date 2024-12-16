<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_tax_authors', schema: 'herbarinput')]
class Authors
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'authorID')]
    private ?int $id = null;

    #[ORM\Column(name: 'author')]
    private string $name;

    public function getName(): string
    {
        return $this->name;
    }

}
