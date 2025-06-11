<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_specimens_links', schema: 'herbarinput')]
class SpecimenLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'specimens_linkID')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Specimens::class, inversedBy: 'outgoingRelations')]
    #[ORM\JoinColumn(name: 'specimen1_ID', referencedColumnName: 'specimen_ID', nullable: false)]
    private Specimens $specimen1;

    #[ORM\ManyToOne(targetEntity: Specimens::class, inversedBy: 'incomingRelations')]
    #[ORM\JoinColumn(name: 'specimen2_ID', referencedColumnName: 'specimen_ID', nullable: false)]
    private Specimens $specimen2;

    #[ORM\ManyToOne(targetEntity: SpecimenLinkQualifier::class)]
    #[ORM\JoinColumn(name: 'link_qualifierID', referencedColumnName: 'link_qualifierID', nullable: true)]
    private ?SpecimenLinkQualifier $linkQualifier;

    public function getSpecimen1(): Specimens
    {
        return $this->specimen1;
    }

    public function getSpecimen2(): Specimens
    {
        return $this->specimen2;
    }

    public function getLinkQualifier(): ?SpecimenLinkQualifier
    {
        return $this->linkQualifier;
    }


}
