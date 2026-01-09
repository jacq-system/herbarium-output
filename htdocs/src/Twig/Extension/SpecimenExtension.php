<?php declare(strict_types=1);

namespace App\Twig\Extension;

use JACQ\Entity\Jacq\Herbarinput\Collector;
use JACQ\Entity\Jacq\Herbarinput\Species;
use JACQ\Entity\Jacq\Herbarinput\Specimens;
use JACQ\Service\GeoService;
use JACQ\Service\Legacy\IiifFacade;
use JACQ\Repository\Herbarinput\CollectorRepository;
use JACQ\Service\SpecimenService;
use JACQ\Service\SpeciesService;
use JACQ\Service\TypusService;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class SpecimenExtension extends AbstractExtension
{
    public function __construct(protected readonly IiifFacade $iiifFacade, protected readonly EntityManagerInterface $entityManager, protected readonly SpecimenService $specimenService, protected readonly TypusService $typusService, protected readonly SpeciesService $taxonService, protected readonly CollectorRepository $collectorRepository, readonly GeoService $geoService)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('taxonAuthority', [$this, 'getTaxonAuthority']),
            new TwigFilter('locality', [$this, 'getLocality']),
            new TwigFilter('localityLong', [$this, 'getLocalityLong']),
            new TwigFilter('herbariumNr', [$this, 'getHerbariumNumber']),
            new TwigFilter('annotation', [$this, 'getAnnotation']),
            new TwigFilter('taxonName', [$this, 'getTaxonName']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getBloodHoundId', [$this, 'getBloodHoundId']),
            new TwigFunction('getCollectionText', [$this, 'getCollectionText']),
            new TwigFunction('getManifestUrl', [$this, 'getManifestUrl']),
            new TwigFunction('getScientificName', [$this, 'getScientificName']),
            new TwigFunction('constructStableIdentifier', [$this, 'constructStableIdentifier']),
            new TwigFunction('getProtologs', [$this, 'getProtologs']),
            new TwigFunction('getRelatedSpecimens', [$this, 'getRelatedSpecimenRelations']),

        ];
    }

    public function getManifestUrl(Specimens $specimen): string
    {
        return $this->iiifFacade->resolveManifestUri($specimen);
    }

    public function getTaxonAuthority(Species $taxon): string
    { //TODO do not keep in database whole HTML including path to assets, the db view is also not necessary for this function..
        $text = '';
        $sql = "SELECT serviceID, hyper FROM herbar_view.view_taxon_link_service WHERE taxonID = :taxon";
        $result = $this->entityManager->getConnection()->executeQuery($sql, ['taxon' => $taxon->id])->fetchAllAssociative();

        foreach ($result as $rowtax) {
            $text .= '<br/>';
            if ($rowtax['serviceID'] == 1) {
                $text .= $rowtax["hyper"] . "&nbsp;";
                $text .= str_replace("IPNI (K)", "Plants of the World Online / POWO (K)", str_replace("serviceID1_logo", "serviceID49_logo", str_replace("http://ipni.org/ipni/idPlantNameSearch.do?id=", "http://powo.science.kew.org/taxon/urn:lsid:ipni.org:names:", $rowtax["hyper"])));
            } else {
                $text .= $rowtax["hyper"];
            }
        }

        return str_replace('assets/images', '/logo/services', $text);
    }

    public function getBloodHoundId(Collector $collector): ?string
    {
        return $this->collectorRepository->getBloodhoundId($collector);
    }

    public function getCollectionText(Specimens $specimen): ?string
    {
        return $this->specimenService->getCollectionText($specimen);

    }

    public function getScientificName(Specimens $specimen): string
    {

        return $this->specimenService->getScientificName($specimen);

    }

    public function getLocality(Specimens $specimen): string
    {
        $text = '';
        $switch = false;
        if ($specimen->country?->nameEng !== null) {
            $text .= "<img src='/flags/" . strtolower($specimen->country->isoCode2) . ".png'> " . $specimen->country->nameEng;
            $switch = true;
        }
        if ($specimen->province !== null) {
            if ($switch) {
                $text .= ". ";
            }
            $text .= $specimen->province->name;
            $switch = true;
        }
        if (!empty($specimen->locality)) {
            if ($switch) {
                $text .= ". ";
            }
            if (mb_strlen(trim($specimen->locality)) > 200) {
                $text .= mb_substr(trim($specimen->locality), 0, 200) . "...";
            } else {
                $text .= trim($specimen->locality);
            }
        }
        return $text;
    }

    public function getLocalityLong(Specimens $specimen): string
    {
        $text = '';
        $switch = false;
        if ($specimen->country?->nameEng !== null) {
            $text .= "<img src='/flags/" . strtolower($specimen->country->isoCode2) . ".png'> " . $specimen->country->nameEng;
            $switch = true;
        }
        if ($specimen->province !== null) {
            if ($switch) {
                $text .= " / ";
            }
            $text .= $specimen->province->name;
        }

        if ($specimen->hasCoords()) {
            $coords = $this->geoService->DMSToDecimal($specimen->getDMSCoords());
            $text .= " | " . round($coords->getLat(),5) . ", " . round($coords->getLng(),5);
        }
        return $text;
    }

    public function constructStableIdentifier(Specimens $specimen): string
    {
        return $this->specimenService->constructStableIdentifier($specimen);
    }

    public function getHerbariumNumber(Specimens $specimen): string
    {

        $sourceId = $specimen->herbCollection->institution->id;
        if ($sourceId === 29) {
            return ($specimen->herbNumber) ?: ('B (JACQ-ID' . $specimen->id . ')');
        } elseif ($sourceId === 50) {
            return ($specimen->herbNumber) ?: ('Willing (JACQ-ID ' . $specimen->id . ')');
        } else {
            return $specimen->herbCollection->institution->code . " " . $specimen->herbNumber;
        }

    }

    public function getAnnotation(Specimens $specimen): string
    {
        $sourceId = $specimen->herbCollection->id;
        if ($sourceId == '35') {
            return (preg_replace("#<a .*a>#", "", $specimen->getAnnotation(true) ?? ''));
        }
        return $specimen->getAnnotation(true) ?? '';

    }

    public function getTaxonName(Species $species): string
    {
        return $this->taxonService->taxonNameWithHybrids($species, true);
    }

    public function getProtologs(Species $species): array
    {
        return $this->typusService->getProtologs($species);
    }

    public function getRelatedSpecimenRelations(Specimens $specimen): array
    {
        $relations = [];
        foreach ($specimen->getAllDirectRelations() as $relation) {
            if ($relation->getSpecimen1()->id === $specimen->id) {
                $relations[] = ["relation"=>$relation->getLinkQualifier()?->name, "specimen"=>$relation->getSpecimen2()];
            } else {
                $relations[] = ["relation"=>$relation->getLinkQualifier()?->getNameReverse(), "specimen"=>$relation->getSpecimen1()];
            }
        }
        return $relations;
    }

}
