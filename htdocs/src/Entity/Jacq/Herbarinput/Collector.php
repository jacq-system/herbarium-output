<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use App\Repository\Herbarinput\CollectorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollectorRepository::class)]
#[ORM\Table(name: 'tbl_collector', schema: 'herbarinput')]
class Collector
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'SammlerID')]
    private ?int $id = null;

    #[ORM\Column(name: 'Sammler')]
    private string $name;

    #[ORM\Column(name: 'WIKIDATA_ID')]
    private ?string $wikidataId = null;

    #[ORM\Column(name: 'HUH_ID')]
    private ?string $huhId = null;

    #[ORM\Column(name: 'VIAF_ID')]
    private ?string $viafId = null;

    #[ORM\Column(name: 'ORCID')]
    private ?string $orcidId = null;

    #[ORM\Column(name: 'Bloodhound_ID')]
    private ?string $bloodHoundId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getWikidataId(): ?string
    {
        return $this->wikidataId;
    }

    public function getHuhId(): ?string
    {
        return $this->huhId;
    }

    public function getViafId(): ?string
    {
        return $this->viafId;
    }

    public function getOrcidId(): ?string
    {
        return $this->orcidId;
    }

    public function getBloodHoundId(): ?string
    {
        return $this->bloodHoundId;
    }

}
