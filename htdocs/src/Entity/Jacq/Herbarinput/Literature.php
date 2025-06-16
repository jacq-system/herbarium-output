<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use App\Repository\Herbarinput\LiteratureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(name: 'periodicalID')]
    private ?int $periodical;

    #[ORM\OneToMany(targetEntity: Synonymy::class, mappedBy: "literature")]
    private Collection $synonymies;

    public function __construct()
    {
        $this->synonymies = new ArrayCollection();
    }
    public function isHideScientificNameAuthors(): bool
    {
        return $this->hideScientificNameAuthors;
    }

    public function getId(): ?int
    {
        return $this->id;
    }



}
