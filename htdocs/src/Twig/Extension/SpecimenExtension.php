<?php declare(strict_types=1);

namespace App\Twig\Extension;

use App\Entity\Jacq\Herbarinput\Collector;
use App\Entity\Jacq\Herbarinput\Species;
use App\Entity\Jacq\Herbarinput\Specimens;
use App\Facade\Rest\IiifFacade;
use App\Repository\Herbarinput\CollectorRepository;
use App\Service\SpecimenService;
use App\Service\SpeciesService;
use App\Service\TypusService;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class SpecimenExtension extends AbstractExtension
{
    public function __construct(protected readonly IiifFacade $iiifFacade, protected readonly EntityManagerInterface $entityManager, protected readonly SpecimenService $specimenService, protected readonly TypusService $typusService, protected readonly SpeciesService $taxonService, protected readonly CollectorRepository $collectorRepository)
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
        $result = $this->entityManager->getConnection()->executeQuery($sql, ['taxon' => $taxon->getId()])->fetchAllAssociative();

        foreach ($result as $rowtax) {
            $text .= '<br/>';
            if ($rowtax['serviceID'] == 1) {
                $text .= $rowtax["hyper"] . "&nbsp;";
                $text .= str_replace("IPNI (K)", "Plants of the World Online / POWO (K)", str_replace("serviceID1_logo", "serviceID49_logo", str_replace("http://ipni.org/ipni/idPlantNameSearch.do?id=", "http://powo.science.kew.org/taxon/urn:lsid:ipni.org:names:", $rowtax["hyper"])));
            } else {
                $text .= $rowtax["hyper"];
            }
        }
        $text = str_replace('assets/images', '/logo/services', $text);

        return $text;
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
        if ($specimen?->getCountry()?->getNameEng() !== null) {
            $text .= "<img src='/flags/" . strtolower($specimen->getCountry()->getIsoCode2()) . ".png'> " . $specimen->getCountry()->getNameEng();
            $switch = true;
        }
        if ($specimen->getProvince() !== null) {
            if ($switch) {
                $text .= ". ";
            }
            $text .= $specimen->getProvince()->getName();
            $switch = true;
        }
        if (!empty($specimen->getLocality())) {
            if ($switch) {
                $text .= ". ";
            }
            if (mb_strlen(trim($specimen->getLocality())) > 200) {
                $text .= mb_substr(trim($specimen->getLocality()), 0, 200) . "...";
            } else {
                $text .= trim($specimen->getLocality());
            }
        }
        return $text;
    }

    public function getLocalityLong(Specimens $specimen): string
    {
        $text = '';
        $switch = false;
        if ($specimen?->getCountry()?->getNameEng() !== null) {
            $text .= "<img src='/flags/" . strtolower($specimen->getCountry()->getIsoCode2()) . ".png'> " . $specimen->getCountry()->getNameEng();
            $switch = true;
        }
        if ($specimen->getProvince() !== null) {
            if ($switch) {
                $text .= " / ";
            }
            $text .= $specimen->getProvince()->getName();
        }

        if ($specimen->getLongitude() != null || $specimen->getLatitude() != null) {
            $text .= " - " . $specimen->getCoords();
        }
        return $text;
    }

    public function constructStableIdentifier(Specimens $specimen): string
    {
        return $this->specimenService->constructStableIdentifier($specimen);
    }

    public function getHerbariumNumber(Specimens $specimen): string
    {

        $sourceId = $specimen->getHerbCollection()->getInstitution()->getId();
        if ($sourceId === 29) {
            return ($specimen->getHerbNumber()) ?: ('B (JACQ-ID' . $specimen->getId() . ')');
        } elseif ($sourceId === 50) {
            return ($specimen->getHerbNumber()) ?: ('Willing (JACQ-ID ' . $specimen->getId() . ')');
        } else {
            return $specimen->getHerbCollection()->getInstitution()->getCode() . " " . $specimen->getHerbNumber();
        }

    }

    public function getAnnotation(Specimens $specimen): string
    {
        $sourceId = $specimen->getHerbCollection()->getId();
        if ($sourceId == '35') {
            return (preg_replace("#<a .*a>#", "", $specimen->getAnnotation(true)));
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
        foreach ($specimen->getAllRelations() as $relation) {
            if ($relation->getSpecimen1()->getId() === $specimen->getId()) {
                $relations[] = ["relation"=>$relation->getLinkQualifier()?->getName(), "specimen"=>$relation->getSpecimen2()];
            } else {
                $relations[] = ["relation"=>$relation->getLinkQualifier()?->getName(), "specimen"=>$relation->getSpecimen1()];
            }
        }
        return $relations;
    }

}
