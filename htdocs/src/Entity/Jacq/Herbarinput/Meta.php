<?php declare(strict_types=1);

namespace App\Entity\Jacq\Herbarinput;


use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'meta', schema: 'herbarinput')]
class Meta
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'source_id')]
    private ?int $id = null;

    #[ORM\Column(name: 'source_code')]
    private string $code;

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
        return $this->code;
    }


}
