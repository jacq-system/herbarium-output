<?php declare(strict_types=1);

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class KmlService
{
    public const int EXPORT_LIMIT = 1500;

    protected string $head = '<?xml version="1.0" encoding="UTF-8"?><kml xmlns="http://www.opengis.net/kml/2.2"><Document><description>search results Virtual Herbaria</description>';
    protected string $foot = '</Document></kml>';

    public function __construct(protected readonly SpecimenService $specimenService, protected readonly TypusService $typusService)
    {
    }

    public function export(string $text): string
    {
        return $this->head . $text. $this->foot;
    }

    public function prepareRow(array $rowSpecimen)
    {
        $sammler = $this->specimenService->collection($rowSpecimen);

        $location = $rowSpecimen['nation_engl'];
        if (strlen(trim((string)$rowSpecimen['provinz']))>0) {
            $location .= " / " . trim((string)$rowSpecimen['provinz']);
        }
        if ($rowSpecimen['Coord_S'] > 0 || $rowSpecimen['S_Min'] > 0 || $rowSpecimen['S_Sec'] > 0) {
            $lat = -($rowSpecimen['Coord_S'] + $rowSpecimen['S_Min'] / 60 + $rowSpecimen['S_Sec'] / 3600);
        } else if ($rowSpecimen['Coord_N'] > 0 || $rowSpecimen['N_Min'] > 0 || $rowSpecimen['N_Sec'] > 0) {
            $lat = $rowSpecimen['Coord_N'] + $rowSpecimen['N_Min'] / 60 + $rowSpecimen['N_Sec'] / 3600;
        } else {
            $lat = 0;
        }
        if ($rowSpecimen['Coord_W'] > 0 || $rowSpecimen['W_Min'] > 0 || $rowSpecimen['W_Sec'] > 0) {
            $lon = -($rowSpecimen['Coord_W'] + $rowSpecimen['W_Min'] / 60 + $rowSpecimen['W_Sec'] / 3600);
        } else if ($rowSpecimen['Coord_E'] > 0 || $rowSpecimen['E_Min'] > 0 || $rowSpecimen['E_Sec'] > 0) {
            $lon = $rowSpecimen['Coord_E'] + $rowSpecimen['E_Min'] / 60 + $rowSpecimen['E_Sec'] / 3600;
        } else {
            $lon = 0;
        }
        if ($lat!=0 || $lon!=0) {
            $location .= " / " . round($lat, 2) . "° / " . round($lon, 2) . "°";
        }

        if ($lat || $lon) {
           return "<Placemark>\n"
                . "  <name>" . htmlspecialchars($this->typusService->taxonWithHybrids($rowSpecimen), ENT_NOQUOTES) . "</name>\n"
                . "  <description>\n"
                . "    <![CDATA[\n"
                . "      " . $this->addLine($rowSpecimen['collection'] . " " . $rowSpecimen['HerbNummer'] . " [dbID " . $rowSpecimen['specimen_ID'] . "]")
                . "      " . $this->addLine($sammler)
                . "      " . $this->addLine($rowSpecimen['Datum'])
                . "      " . $this->addLine($location)
                . "      " . $this->addLine($rowSpecimen['Fundort'])
                . "      " . $this->addLine($this->getStableIdentifier($rowSpecimen['specimen_ID']))
                . "      <a href=\"http://herbarium.univie.ac.at/database/detail.php?ID=" . $rowSpecimen['specimen_ID'] . "\">link</a>\n"
                . "    ]]>\n"
                . "  </description>\n"
                . "  <Point>\n"
                . "    <coordinates>$lon,$lat</coordinates>\n"
                . "  </Point>\n"
                . "</Placemark>\n";
        }
        return '';
    }

     protected function addLine($value)
    {
        return htmlspecialchars($value, ENT_NOQUOTES) . "<br>\n";
    }

    protected function getStableIdentifier(int $specimenID): string
    {
        $specimen = $this->specimenService->find($specimenID);
        if (!empty($specimen->getMainStableIdentifier())) {
            return $specimen->getMainStableIdentifier()->getIdentifier();
        } else {
            return $this->specimenService->constructStableIdentifier($specimen);
        }

    }

}
