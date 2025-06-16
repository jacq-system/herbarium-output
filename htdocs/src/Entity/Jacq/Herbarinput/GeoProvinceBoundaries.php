<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_geo_province_boundaries', schema: 'herbarinput')]
class GeoProvinceBoundaries
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $provinceID;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $annotation = null;

    #[ORM\Column(type: 'float')]
    private float $boundSouth;

    #[ORM\Column(type: 'float')]
    private float $boundNorth;

    #[ORM\Column(type: 'float')]
    private float $boundEast;

    #[ORM\Column(type: 'float')]
    private float $boundWest;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvinceID(): int
    {
        return $this->provinceID;
    }

    public function getAnnotation(): ?string
    {
        return $this->annotation;
    }

    public function getBoundSouth(): float
    {
        return $this->boundSouth;
    }

    public function getBoundNorth(): float
    {
        return $this->boundNorth;
    }

    public function getBoundEast(): float
    {
        return $this->boundEast;
    }

    public function getBoundWest(): float
    {
        return $this->boundWest;
    }


}
