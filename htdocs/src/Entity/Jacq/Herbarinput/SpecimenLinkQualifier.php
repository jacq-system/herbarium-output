<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_specimens_links_qualifiers', schema: 'herbarinput')]
class SpecimenLinkQualifier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'link_qualifierID')]
    private ?int $id = null;


    #[ORM\Column(name: 'SpecimenQualifier_engl')]
    private string $nameEn;

    public function getName(): string
    {
        return $this->nameEn;
    }


}
