<?php declare(strict_types=1);

namespace App\Entity\Jacq\Herbarinput;


use App\Repository\Herbarinput\InstitutionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InstitutionRepository::class)]
#[ORM\Table(name: 'metadata', schema: 'herbarinput')]
class Institution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'MetadataID')]
    private ?int $id = null;

    #[ORM\Column(name: 'SourceInstitutionID')]
    private string $code;

    #[ORM\Column(name: 'LicenseURI')]
    private ?string $licenseUri;

    #[ORM\Column(name: 'OwnerLogoURI')]
    private ?string $ownerLogoUri;

    #[ORM\Column(name: 'OwnerOrganizationAbbrev')]
    private ?string $abbreviation;

    #[ORM\Column(name: 'OwnerOrganizationName')]
    private ?string $name;

    #[ORM\Column(name: 'SourceID')]
    private string $name2;

    #[ORM\OneToOne(targetEntity: ImageDefinition::class, mappedBy: 'institution')]
    private ?ImageDefinition $imageDefinition = null;

    #[ORM\OneToMany(targetEntity: HerbCollection::class, mappedBy: "institution")]
    private Collection $collections;

    public function __construct()
    {
        $this->collections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImageDefinition(): ?ImageDefinition
    {
        return $this->imageDefinition;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLicenseUri(): ?string
    {
        return $this->licenseUri;
    }

    public function getOwnerLogoUri(): ?string
    {
        return $this->ownerLogoUri;
    }

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getName2(): string
    {
        return $this->name2;
    }

    public function getCollections(): Collection
    {
        return $this->collections;
    }


}
