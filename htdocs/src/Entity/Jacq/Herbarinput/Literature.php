<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use App\Repository\Herbarinput\LiteratureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LiteratureRepository::class)]
#[ORM\Table(name: 'tbl_lit', schema: 'herbarinput')]
class Literature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'citationID')]
    private ?int $id = null;

    #[ORM\Column(name: 'hideScientificNameAuthors')]
    private bool $hideScientificNameAuthors;

    public function isHideScientificNameAuthors(): bool
    {
        return $this->hideScientificNameAuthors;
    }

    public function getId(): ?int
    {
        return $this->id;
    }



}
