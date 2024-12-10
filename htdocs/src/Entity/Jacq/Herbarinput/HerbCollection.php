<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_management_collections', schema: 'herbarinput')]
class HerbCollection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'collectionID')]
    private ?int $id = null;

    #[ORM\Column(name: 'collection')]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Meta::class)]
    #[ORM\JoinColumn(name: 'source_id', referencedColumnName: 'source_id')]
    private Meta $institution;

    public function getInstitution(): Meta
    {
        return $this->institution;
    }



}
