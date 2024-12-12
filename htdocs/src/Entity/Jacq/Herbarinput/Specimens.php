<?php declare(strict_types=1);

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
    private ?string $locality = null;

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


    #[ORM\Column(name: 'Coord_S')]
    private ?int $degreeS;

    #[ORM\Column(name: 'S_Min')]
    private ?int $minuteS;

    #[ORM\Column(name: 'S_Sec')]
    private ?float $secondS;

    #[ORM\Column(name: 'Coord_N')]
    private ?int $degreeN;

    #[ORM\Column(name: 'N_Min')]
    private ?int $minuteN;

    #[ORM\Column(name: 'N_Sec')]
    private ?float $secondN;

    #[ORM\Column(name: 'Coord_W')]
    private ?int $degreeW;

    #[ORM\Column(name: 'W_Min')]
    private ?int $minuteW;

    #[ORM\Column(name: 'W_Sec')]
    private ?float $secondW;

    #[ORM\Column(name: 'Coord_E')]
    private ?int $degreeE;

    #[ORM\Column(name: 'E_Min')]
    private ?int $minuteE;

    #[ORM\Column(name: 'E_Sec')]
    private ?float $secondE;


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

    #[ORM\ManyToOne(targetEntity: Province::class)]
    #[ORM\JoinColumn(name: 'provinceID', referencedColumnName: 'provinceID')]
    private ?Province $province = null;

    #[ORM\ManyToOne(targetEntity: Country::class)]
    #[ORM\JoinColumn(name: 'NationID', referencedColumnName: 'NationID')]
    private ?Country $country = null;

    public function __construct()
    {
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

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function getLocality(): ?string
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

    public function isObservation(): ?bool
    {
        return $this->observation;
    }

    public function hasObservationImage(): ?bool
    {
        return $this->imageObservation;
    }

    public function hasImage(): ?bool
    {
        return $this->image;
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

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function getLatitude(): ?float
    {
        if ($this->degreeS > 0 || $this->minuteS > 0 || $this->secondS > 0) {
            return -($this->degreeS + $this->minuteS / 60 + $this->secondS / 3600);
        } else if ($this->degreeN > 0 || $this->minuteN > 0 || $this->secondN > 0) {
            return $this->degreeN + $this->minuteN / 60 + $this->secondN / 3600;
        }
        return null;
    }

    public function getLongitude(): ?float
    {
        if ($this->degreeW > 0 || $this->minuteW > 0 || $this->secondW > 0) {
            return -($this->degreeW + $this->minuteW / 60 + $this->secondW / 3600);
        } else if ($this->degreeE > 0 || $this->minuteE > 0 || $this->secondE > 0) {
            return $this->degreeE + $this->minuteE / 60 + $this->secondE / 3600;
        }
        return null;
    }
}
