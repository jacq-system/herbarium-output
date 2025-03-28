<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_tax_rank', schema: 'herbarinput')]
class TaxonRank
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'tax_rankID')]
    private ?int $id = null;


    #[ORM\Column(name: 'rank_abbr')]
    private ?string $abbreviation;

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }


}
