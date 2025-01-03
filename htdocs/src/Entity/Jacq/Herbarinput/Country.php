<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_geo_nation', schema: 'herbarinput')]
class Country
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'NationID')]
    private ?int $id = null;

    #[ORM\Column(name: 'nation')]
    private string $name;

    #[ORM\Column(name: 'nation_engl')]
    private string $nameEng;

    #[ORM\Column(name: 'language_variants')]
    private string $variants;

    #[ORM\Column(name: 'iso_alpha_2_code')]
    private string $isoCode2;

    #[ORM\Column(name: 'iso_alpha_3_code')]
    private string $isoCode3;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameEng(): string
    {
        return $this->nameEng;
    }

    public function getVariants(): string
    {
        return $this->variants;
    }

    public function getIsoCode2(): string
    {
        return $this->isoCode2;
    }

    public function getIsoCode3(): string
    {
        return $this->isoCode3;
    }



}
