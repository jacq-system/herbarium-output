<?php declare(strict_types=1);

namespace App\Service;

use JACQ\Entity\Jacq\Herbarinput\Specimens;
use JACQ\Service\SpeciesService;
use JACQ\Service\SpecimenService;

readonly class KmlService
{
    public const int EXPORT_LIMIT = 1500;

    public function __construct(protected SpecimenService $specimenService, protected SpeciesService $taxonService)
    {
    }


    public function prepareRow(Specimens $specimen): string
    {
        $collectorText = $this->specimenService->getCollectionText($specimen);

        $location = $specimen->country?->nameEng;
        if (!empty($specimen->province?->name)) {
            $location .= " / " . trim($specimen->province->name);
        }
        if ($specimen->getLatitude() !== null && $specimen->getLongitude() !== null) {
            $location .= " / " . round($specimen->getLatitude(), 2) . "° / " . round($specimen->getLongitude(), 2) . "°";
        }

        if ($specimen->getLatitude() !== null && $specimen->getLongitude() !== null) {
           return "<Placemark>\n"
                . "  <name>" . htmlspecialchars($this->taxonService->taxonNameWithHybrids($specimen->species, true), ENT_NOQUOTES) . "</name>\n"
                . "  <description>\n"
                . "    <![CDATA[\n"
                . "      " . $this->addLine($specimen->herbCollection->name . " " . $specimen->herbNumber . " [dbID " . $specimen->id . "]")
                . "      " . $this->addLine($collectorText)
                . "      " . $this->addLine($specimen->getDate())
                . "      " . $this->addLine($location)
                . "      " . $this->addLine($specimen->locality)
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
    public function prepareRowReduced(Specimens $specimen): string
    {
        if ($specimen->getLatitude() !== null && $specimen->getLongitude() !== null) {
            return "<Placemark>\n"
                . "  <name>" .$specimen->id . "</name>\n"
                . "  <Point>\n"
                . "    <coordinates>".$specimen->getLongitude().','.$specimen->getLatitude()."</coordinates>\n"
                . "  </Point>\n"
                . "</Placemark>\n";
        }
        return '';
    }

    public function makeGeoJsonRecord(array $record): array
    {
        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [$this->getLongitude($record), $this->getLatitude($record)],
            ],
            'properties' => [
                'id' => $record['id'],
            ],
        ];
    }

    protected function getLatitude(array $row): ?float
    {
        if ($row['Coord_S'] > 0 || $row['S_Min'] > 0 || $row['S_Sec'] > 0) {
            return -($row['Coord_S'] + $row['S_Min'] / 60 + $row['S_Sec'] / 3600);
        } else if ($row['Coord_N'] > 0 || $row['N_Min'] > 0 || $row['N_Sec'] > 0) {
            return $row['Coord_N'] + $row['N_Min'] / 60 + $row['N_Sec'] / 3600;
        }
        return null;
    }

    protected function getLongitude(array $row): ?float
    {
        if ($row['Coord_W'] > 0 || $row['W_Min'] > 0 || $row['W_Sec'] > 0) {
            return -($row['Coord_W'] + $row['W_Min'] / 60 + $row['W_Sec'] / 3600);
        } else if ($row['Coord_E'] > 0 || $row['E_Min'] > 0 || $row['E_Sec'] > 0) {
            return $row['Coord_E'] + $row['E_Min'] / 60 + $row['E_Sec'] / 3600;
        }
        return null;
    }

     protected function addLine(?string $value):string
    {
        if(empty($value)) {
            return "";
        }
        return htmlspecialchars($value, ENT_NOQUOTES) . "<br>\n";
    }


}
