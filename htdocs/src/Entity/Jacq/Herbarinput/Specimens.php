<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;


use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_specimens', schema: 'herbarinput')]
class Specimens
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'specimen_ID')]
    private ?int $id = null;

    #[ORM\Column(name: 'Nummer')]
    private int $number;

    #[ORM\Column(name: 'HerbNummer')]
    private string $herbNumber;

    #[ORM\ManyToOne(targetEntity: HerbCollection::class)]
    #[ORM\JoinColumn(name: 'collectionID', referencedColumnName: 'collectionID')]
    private HerbCollection $collection;

    public function getId(): ?int
    {
        return $this->id;
    }

}
