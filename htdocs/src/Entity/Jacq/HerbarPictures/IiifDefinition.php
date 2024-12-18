<?php declare(strict_types = 1);

namespace App\Entity\Jacq\HerbarPictures;

use App\Entity\Jacq\Herbarinput\HerbCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'iiif_definition', schema: 'herbar_pictures')]
class IiifDefinition
{

    #[ORM\Column(name: 'manifest_uri')]
    private string $manifestUri;

    #[ORM\Column(name: 'manifest_backend')]
    private ?string $manifestBackend = null;

    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: HerbCollection::class, inversedBy: 'iiifDefinition')]
    #[ORM\JoinColumn(name: 'source_id_fk', referencedColumnName: 'source_id')]
    private HerbCollection $herbCollection;

    public function getManifestUri(): string
    {
        return $this->manifestUri;
    }

    public function getManifestBackend(): ?string
    {
        return $this->manifestBackend;
    }


}
