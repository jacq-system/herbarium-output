<?php declare(strict_types=1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_tax_species', schema: 'herbarinput')]
class Species
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'taxonID')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Genus::class)]
    #[ORM\JoinColumn(name: 'genID', referencedColumnName: 'genID')]
    private Genus $genus;

    #[ORM\ManyToOne(targetEntity: Authors::class)]
    #[ORM\JoinColumn(name: 'authorID', referencedColumnName: 'authorID')]
    private ?Authors $authorSpecies;
    #[ORM\ManyToOne(targetEntity: Authors::class)]
    #[ORM\JoinColumn(name: 'subspecies_authorID', referencedColumnName: 'authorID')]
    private ?Authors $authorSubspecies;
    #[ORM\ManyToOne(targetEntity: Authors::class)]
    #[ORM\JoinColumn(name: 'variety_authorID', referencedColumnName: 'authorID')]
    private ?Authors $authorVariety;
    #[ORM\ManyToOne(targetEntity: Authors::class)]
    #[ORM\JoinColumn(name: 'subvariety_authorID', referencedColumnName: 'authorID')]
    private ?Authors $authorSubvariety;
    #[ORM\ManyToOne(targetEntity: Authors::class)]
    #[ORM\JoinColumn(name: 'forma_authorID', referencedColumnName: 'authorID')]
    private ?Authors $authorForma;
    #[ORM\ManyToOne(targetEntity: Authors::class)]
    #[ORM\JoinColumn(name: 'subforma_authorID', referencedColumnName: 'authorID')]
    private ?Authors $authorSubforma;

    #[ORM\ManyToOne(targetEntity: Epithet::class)]
    #[ORM\JoinColumn(name: 'speciesID', referencedColumnName: 'epithetID')]
    private ?Epithet $epithetSpecies;

    #[ORM\ManyToOne(targetEntity: Epithet::class)]
    #[ORM\JoinColumn(name: 'subspeciesID', referencedColumnName: 'epithetID')]
    private ?Epithet $epithetSubspecies;

    #[ORM\ManyToOne(targetEntity: Epithet::class)]
    #[ORM\JoinColumn(name: 'varietyID', referencedColumnName: 'epithetID')]
    private ?Epithet $epithetVariety;

    #[ORM\ManyToOne(targetEntity: Epithet::class)]
    #[ORM\JoinColumn(name: 'subvarietyID', referencedColumnName: 'epithetID')]
    private ?Epithet $epithetSubvariety;

    #[ORM\ManyToOne(targetEntity: Epithet::class)]
    #[ORM\JoinColumn(name: 'formaID', referencedColumnName: 'epithetID')]
    private ?Epithet $epithetForma;

    #[ORM\ManyToOne(targetEntity: Epithet::class)]
    #[ORM\JoinColumn(name: 'subformaID', referencedColumnName: 'epithetID')]
    private ?Epithet $epithetSubforma;

    #[ORM\Column(name: 'statusID')]
    private int $status;

    #[ORM\ManyToOne(targetEntity: Species::class)]
    #[ORM\JoinColumn(name: "synID", referencedColumnName: "taxonID", nullable: true)]
    private ?Species $validName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isSynonym(): bool
    {
        return $this->getValidName() !== null;
    }

    public function getValidName(): ?Species
    {
        return $this->validName;
    }

    public function getFullName(bool $html = false): string
    {
        $text = '<i>' . $this->getGenus()->getName() . '</i>';
        if ($this->getEpithetSpecies() !== null) {
            $text .= " <i>" . $this->getEpithetSpecies()->getName() . "</i> " . $this->getAuthorSpecies()->getName();
        }

        if ($this->getEpithetSubspecies() !== null) {
            $text .= " subsp. <i>" . $this->getEpithetSubspecies()->getName() . "</i> " . $this->getAuthorSubspecies()->getName();
        }
        if ($this->getEpithetVariety() !== null) {
            $text .= " var. <i>" . $this->getEpithetVariety()->getName() . "</i> " . $this->getAuthorVariety()->getName();
        }
        if ($this->getEpithetSubvariety() !== null) {
            $text .= " subvar. <i>" . $this->getEpithetSubvariety()->getName() . "</i> " . $this->getAuthorSubvariety()->getName();
        }
        if ($this->getEpithetForma() !== null) {
            $text .= " forma <i>" . $this->getEpithetForma()->getName() . "</i> " . $this->getAuthorForma()->getName();
        }
        if ($this->getEpithetSubforma() !== null) {
            $text .= " subforma <i>" . $this->getEpithetSubforma()->getName() . "</i> " . $this->getAuthorSubforma()->getName();
        }

        if (!$html){
            return strip_tags($text);
        }
        return $text;
    }

    public function getGenus(): Genus
    {
        return $this->genus;
    }

    public function getEpithetSpecies(): ?Epithet
    {
        return $this->epithetSpecies;
    }

    public function getAuthorSpecies(): ?Authors
    {
        return $this->authorSpecies;
    }

    public function getEpithetSubspecies(): ?Epithet
    {
        return $this->epithetSubspecies;
    }

    public function getAuthorSubspecies(): ?Authors
    {
        return $this->authorSubspecies;
    }

    public function getEpithetVariety(): ?Epithet
    {
        return $this->epithetVariety;
    }

    public function getAuthorVariety(): ?Authors
    {
        return $this->authorVariety;
    }

    public function getEpithetSubvariety(): ?Epithet
    {
        return $this->epithetSubvariety;
    }

    public function getAuthorSubvariety(): ?Authors
    {
        return $this->authorSubvariety;
    }

    public function getEpithetForma(): ?Epithet
    {
        return $this->epithetForma;
    }

    public function getAuthorForma(): ?Authors
    {
        return $this->authorForma;
    }

    public function getEpithetSubforma(): ?Epithet
    {
        return $this->epithetSubforma;
    }

    public function getAuthorSubforma(): ?Authors
    {
        return $this->authorSubforma;
    }

    public function isHybrid(): bool
    {
        return ($this->getStatus() === 1 && $this->getEpithetSpecies() === null && $this->getAuthorSpecies() === null);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

}
