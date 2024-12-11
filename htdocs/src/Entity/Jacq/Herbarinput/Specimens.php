<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;


use App\Entity\Jacq\HerbarPictures\PhaidraCache;
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
    private ?int $number = null;

    #[ORM\Column(name: 'HerbNummer')]
    private string $herbNumber;

    #[ORM\Column(name: 'alt_number')]
    private ?string $altNumber = null;

    #[ORM\Column(name: 'series_number')]
    private ?string $seriesNumber;

    #[ORM\Column(name: 'CollNummer')]
    private string $collectionNumber;

    #[ORM\Column(name: 'observation')]
    private ?bool $observation;

    #[ORM\Column(name: 'Datum')]
    private ?string $date;

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
    private ?bool $image;

    #[ORM\Column(name: 'digital_image_obs')]
    private ?bool $imageObservation;

    #[ORM\Column(name: 'taxon_alt')]
    private string $taxonAlternative;

    #[ORM\ManyToOne(targetEntity: HerbCollection::class)]
    #[ORM\JoinColumn(name: 'collectionID', referencedColumnName: 'collectionID')]
    private HerbCollection $collection;

    #[ORM\ManyToOne(targetEntity: Series::class)]
    #[ORM\JoinColumn(name: 'seriesID', referencedColumnName: 'seriesID')]
    private ?Series $series;

    #[ORM\ManyToOne(targetEntity: Collector::class)]
    #[ORM\JoinColumn(name: 'SammlerID', referencedColumnName: 'SammlerID')]
    private ?Collector $collector = null;

    #[ORM\ManyToOne(targetEntity: Collector2::class)]
    #[ORM\JoinColumn(name: 'Sammler_2ID', referencedColumnName: 'Sammler_2ID')]
    private ?Collector2 $collector2 = null;

    #[ORM\OneToMany(targetEntity: Typus::class, mappedBy: 'specimen')]
    private Collection $typus;

    #[ORM\OneToOne(targetEntity: PhaidraCache::class, mappedBy: 'specimen')]
    private ?PhaidraCache $phaidraImages = null;

    #[ORM\ManyToOne(targetEntity: Species::class)]
    #[ORM\JoinColumn(name: 'taxonID', referencedColumnName: 'taxonID')]
    private Species $species;



    public function __construct() {
        $this->typus = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function getHerbNumber(): string
    {
        return $this->herbNumber;
    }

    public function getAltNumber(): ?string
    {
        return $this->altNumber;
    }

    public function getSeriesNumber(): ?string
    {
        return $this->seriesNumber;
    }

    public function getCollectionNumber(): string
    {
        return $this->collectionNumber;
    }

    public function isObservation(): ?bool
    {
        return $this->observation;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function getLocality(): string
    {
        return $this->locality;
    }

    public function getLocalityEng(): string
    {
        return $this->localityEng;
    }

    public function getHabitus(): string
    {
        return $this->habitus;
    }

    public function getHabitat(): string
    {
        return $this->habitat;
    }

    public function getAnnotation(): string
    {
        return $this->annotation;
    }

    public function hasImage(): ?bool
    {
        return $this->image;
    }

    public function hasObservationImage(): ?bool
    {
        return $this->imageObservation;
    }

    public function getTaxonAlternative(): string
    {
        return $this->taxonAlternative;
    }

    public function getCollection(): HerbCollection
    {
        return $this->collection;
    }

    public function getSeries(): ?Series
    {
        return $this->series;
    }

    public function getTypus(): Collection
    {
        return $this->typus;
    }

    public function getSpecies(): Species
    {
        return $this->species;
    }


    public function getImageIconFilename(): ?string
    {
        if ($this->isObservation()) {
            if ($this->hasObservationImage()) {
                return "obs.png";
            } else {
                return "obs_bw.png";
            }
        } else {
            if ($this->hasImage() || $this->hasObservationImage()) {
                if ($this->hasObservationImage() && $this->hasImage()) {
                    return "spec_obs.png";
                } elseif ($this->hasObservationImage() && !$this->hasImage()) {
                    return "obs.png";
                } else {
                    return "camera.png";
                }
            }
        }
        return null;
    }

    public function getPhaidraImage(): ?PhaidraCache
    {
        return $this->phaidraImages;
    }

    public function getCollector(): ?Collector
    {
        return $this->collector;
    }


    public function getCollector2(): ?Collector2
    {
        return $this->collector2;
    }

}
