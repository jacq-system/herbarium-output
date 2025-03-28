<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Jacq\Herbarinput\Specimens;

class KmlService
{
    public const int EXPORT_LIMIT = 1500;

    protected string $head = '<?xml version="1.0" encoding="UTF-8"?><kml xmlns="http://www.opengis.net/kml/2.2"><Document><description>search results Virtual Herbaria</description>';
    protected string $foot = '</Document></kml>';

    public function __construct(protected readonly SpecimenService $specimenService, protected readonly TaxonService $taxonService)
    {
    }

    public function export(string $text): string
    {
        return $this->head . $text. $this->foot;
    }

    public function prepareRow(Specimens $specimen)
    {
        $collectorText = $this->specimenService->getCollectionText($specimen);

        $location = $specimen->getCountry()->getNameEng();
        if (!empty($specimen->getProvince()?->getName())) {
            $location .= " / " . trim($specimen->getProvince()->getName());
        }
        if ($specimen->getLatitude() !== null && $specimen->getLongitude() !== null) {
            $location .= " / " . round($specimen->getLatitude(), 2) . "° / " . round($specimen->getLongitude(), 2) . "°";
        }

        if ($specimen->getLatitude() !== null && $specimen->getLongitude() !== null) {
           return "<Placemark>\n"
                . "  <name>" . htmlspecialchars($this->taxonService->taxonNameWithHybrids($specimen->getSpecies(), true), ENT_NOQUOTES) . "</name>\n"
                . "  <description>\n"
                . "    <![CDATA[\n"
                . "      " . $this->addLine($specimen->getHerbCollection()->getName() . " " . $specimen->getHerbNumber() . " [dbID " . $specimen->getId() . "]")
                . "      " . $this->addLine($collectorText)
                . "      " . $this->addLine($specimen->getDate())
                . "      " . $this->addLine($location)
                . "      " . $this->addLine($specimen->getLocality())
                . "      " . $this->addLine($this->specimenService->getStableIdentifier($specimen))
                . "      <a href=\"".$this->specimenService->getStableIdentifier($specimen). "\">link</a>\n"
                . "    ]]>\n"
                . "  </description>\n"
                . "  <Point>\n"
                . "    <coordinates>".$specimen->getLongitude().','.$specimen->getLatitude()."</coordinates>\n"
                . "  </Point>\n"
                . "</Placemark>\n";
        }
        return '';
    }

     protected function addLine(?string $value)
    {
        if(empty($value)) {
            return "";
        }
        return htmlspecialchars($value, ENT_NOQUOTES) . "<br>\n";
    }


}
