<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use App\Entity\Jacq\HerbarPictures\IiifDefinition;
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

    #[ORM\Column(name: 'coll_short_prj')]
    private string $collShortPrj;

    #[ORM\Column(name: 'picture_filename')]
    private ?string $pictureFilename = null;


    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(name: 'source_id', referencedColumnName: 'source_id')]
    private Institution $institution;

    #[ORM\OneToOne(targetEntity: IiifDefinition::class, mappedBy: 'herbCollection')]
    private ?IiifDefinition $iiifDefinition = null;


    public function getInstitution(): Institution
    {
        return $this->institution;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPictureFilename(): ?string
    {
        return $this->pictureFilename;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCollShortPrj(): string
    {
        return $this->collShortPrj;
    }

    public function getIiifDefinition(): ?IiifDefinition
    {
        return $this->iiifDefinition;
    }



}
