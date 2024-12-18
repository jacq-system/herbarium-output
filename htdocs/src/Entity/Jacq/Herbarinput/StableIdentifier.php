<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_specimens_stblid', schema: 'herbarinput')]
class StableIdentifier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\Column(name: 'stableIdentifier')]
    private ?string $identifier;

    #[ORM\Column(name: 'origin')]
    private string $origin;

    #[ORM\Column(name: 'visible')]
    private bool $visible;

    #[ORM\Column(name: 'timestamp',type: 'datetime')]
    private \DateTime $timestamp;

    #[ORM\Column(name: 'error')]
    private ?string $error;

    #[ORM\ManyToOne(targetEntity: Specimens::class, inversedBy: 'stableIdentifier')]
    #[ORM\JoinColumn(name: 'specimen_ID', referencedColumnName: 'specimen_ID')]
    private Specimens $specimen;

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function getError(): ?string
    {
        return $this->error;
    }



}
