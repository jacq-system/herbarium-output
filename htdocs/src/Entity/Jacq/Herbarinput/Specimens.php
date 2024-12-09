<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_specimens', schema: 'herbarinput')]
class Specimens
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'specimen_ID')]
    private ?int $id = null;

    #[ORM\Column(name: 'Nummer')]
    private int $number;

    #[ORM\Column(name: 'HerbNummer')]
    private string $herbNumber;

    #[ORM\Column(name: 'alt_number')]
    private string $altNumber;

    #[ORM\Column(name: 'series_number')]
    private string $seriesNumber;

    #[ORM\Column(name: 'CollNummer')]
    private string $collectionNumber;

    #[ORM\Column(name: 'SammlerID')]
    private int $collector;

    #[ORM\Column(name: 'Sammler_2ID')]
    private int $collector2;

    #[ORM\Column(name: 'Datum')]
    private string $date;

    #[ORM\Column(name: 'Fundort')]
    private string $locality;

    #[ORM\Column(name: 'Fundort_engl')]
    private string $localityEng;

    #[ORM\Column(name: 'habitus')]
    private string $habitus;

    #[ORM\Column(name: 'habitat')]
    private string $habitat;

    #[ORM\Column(name: 'Bemerkungen')]
    private string $annotation;

    #[ORM\Column(name: 'digital_image')]
    private int $image;

    #[ORM\Column(name: 'digital_image_obs')]
    private int $imageObservation;

    #[ORM\Column(name: 'taxon_alt')]
    private string $taxonAlternative;

    #[ORM\ManyToOne(targetEntity: HerbCollection::class)]
    #[ORM\JoinColumn(name: 'collectionID', referencedColumnName: 'collectionID')]
    private HerbCollection $collection;

    #[ORM\ManyToOne(targetEntity: Series::class)]
    #[ORM\JoinColumn(name: 'seriesID', referencedColumnName: 'seriesID')]
    private Series $series;

    #[ORM\OneToMany(targetEntity: Typus::class, mappedBy: 'specimen')]
    private Collection $typus;

    #[ORM\ManyToOne(targetEntity: Species::class)]
    #[ORM\JoinColumn(name: 'taxonID', referencedColumnName: 'taxonID')]
    private Species $species;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function __construct() {
        $this->typus = new ArrayCollection();
    }

}
