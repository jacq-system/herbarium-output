<?php declare(strict_types=1);

namespace App\Entity\Jacq\Herbarinput;


use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
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


    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\OneToOne(targetEntity: ImageDefinition::class, mappedBy: 'institution')]
    private ?ImageDefinition $imageDefinition = null;

    public function getImageDefinition(): ?ImageDefinition
    {
        return $this->imageDefinition;
    }

    public function getCode(): string
    {
        return $this->licenseUri;
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

}
