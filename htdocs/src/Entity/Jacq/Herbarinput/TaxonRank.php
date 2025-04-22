<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use App\Repository\Herbarinput\TaxonRankRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaxonRankRepository::class)]
#[ORM\Table(name: 'tbl_tax_rank', schema: 'herbarinput')]
class TaxonRank
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'tax_rankID')]
    private ?int $id = null;

    #[ORM\Column(name: 'rank')]
    private string $name;

    #[ORM\Column(name: 'rank_abbr')]
    private ?string $abbreviation;

    #[ORM\Column(name: 'rank_hierarchy')]
    private int $hierarchy;

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function getHierarchy(): int
    {
        return $this->hierarchy;
    }

    public function getName(): string
    {
        return $this->name;
    }

}
