<?php declare(strict_types = 1);

namespace App\Entity\Jacq\GbifPilot;

use App\Entity\Jacq\Herbarinput\Specimens;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'europeana_images', schema: 'gbif_pilot')]
class EuropeanaImages
{

    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Specimens::class, inversedBy: 'europeanaImages')]
    #[ORM\JoinColumn(name: 'specimen_ID', referencedColumnName: 'specimen_ID')]
    private Specimens $specimen;

    #[ORM\Column(name: 'filesize')]
    private int $filesize;

    public function getFilesize(): int
    {
        return $this->filesize;
    }

}
