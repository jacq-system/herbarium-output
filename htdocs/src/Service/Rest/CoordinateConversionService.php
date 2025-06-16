<?php declare(strict_types=1);

namespace App\Service\Rest;

use PHPCoord\CoordinateReferenceSystem\Geographic2D;
use PHPCoord\Point\GeographicPoint;
use PHPCoord\Point\UTMPoint;
use PHPCoord\UnitOfMeasure\Angle\Degree;
use PHPCoord\UnitOfMeasure\Length\Metre;


/*
 * Vienna:
 * 48°12′36″ N  16°22′12″ E
 * 48°12.60000′ N 16°22.20000′ E
 * 48.2100000° 16.3700000°
 * 33U 601779 5340548
 * 33UXP0177940548
 */

readonly class CoordinateConversionService
{


    public function utm2latlon(string $utm): array
    {
        $parts = explode(' ', preg_replace('/\s+/', ' ', trim($utm)));
        if (is_numeric($parts[1])) {
            $hemisphere = (substr($parts[0], 2) >= 'N') ? UTMPoint::HEMISPHERE_NORTH : UTMPoint::HEMISPHERE_SOUTH;
            $easting = $parts[1];
            $northing = $parts[2];
        } else {
            $hemisphere = ($parts[1] == 'S') ? UTMPoint::HEMISPHERE_SOUTH : UTMPoint::HEMISPHERE_NORTH;
            $easting = $parts[2];
            $northing = $parts[3];
        }
        $from = new UTMPoint(
            Geographic2D::fromSRID(Geographic2D::EPSG_WGS_84),
            new Metre((float) $easting),
            new Metre((float) $northing),
            intval($parts[0]),
            $hemisphere
        );
        $to = $from->asGeographicPoint();
        return array(
            'lat' => $to->getLatitude()->getValue(),
            'lon' => $to->getLongitude()->getValue(),
            'string' => (string)$to
        );
    }

    public function latlon2utm(float $lat, float $lon): array
    {
        $lat = floatval($lat);
        $from = GeographicPoint::create(
            Geographic2D::fromSRID(Geographic2D::EPSG_WGS_84),
            new Degree($lat),
            new Degree(floatval($lon)),
            null
        );
        $to = $from->asUTMPoint();
        if ($lat < -32) {
            $band = chr((int)floor($lat / 8) + ord('M')); // band is 'H' or below
        } elseif ($lat < 8) {
            $band = chr((int)floor($lat / 8) + ord('N')); // band is between 'J' and 'N'
        } else {
            $band = chr((int)floor($lat / 8) + ord('O')); // band is 'P' or above
        }
        return array(
            'zone' => $to->getZone(),
            'hemisphere' => $to->getHemisphere(),
            'easting' => (int)$to->getEasting()->getValue(),
            'northing' => (int)$to->getNorthing()->getValue(),
            'string' => $to->getZone() . $band . ' ' . (int)$to->getEasting()->getValue() . ' ' . (int)$to->getNorthing()->getValue()
        );
    }

    /**
     * @param $mgrs
     * @return array|string[]
     */
    public function mgrs2utm(string $mgrs): array
    {
        $parts = $this->parseMGRSstring($mgrs);
        if ($parts['error']) {
            return ($parts);
        }

        if ($parts['letters'][0] == 'X' && ($parts['zone'] == 32 || $parts['zone'] == 34 || $parts['zone'] == 36)) {
            return array('error' => 'no valid MGRS string');
        }

        $data = array('zone' => $parts['zone'],
            'hemisphere' => ($parts['letters'][0] < 'N') ? 'S' : 'N'
        );

        $grid_values = $this->getGridValues($parts['zone']);
        // Check that the second letter of the MGRS string is within the range of valid second letter values
        // Also check that the third letter is valid
        if ($parts['letters'][1] < $grid_values['ltr2_low_value'] || $parts['letters'][1] > $grid_values['ltr2_high_value'] || $parts['letters'][2] > 'V') {
            return array('error' => 'no valid MGRS string');
        }

        $grid_northing = (ord($parts['letters'][2]) - ord('A')) * 100000 + $grid_values['false_northing'];
        $grid_easting = ((ord($parts['letters'][1]) - ord($grid_values['ltr2_low_value'])) + 1) * 100000;
        if ($grid_values['ltr2_low_value'] == 'J' && $parts['letters'][1] > 'O') {
            $grid_easting -= 100000;
        }
        if ($parts['letters'][2] > 'O') {
            $grid_northing -= 100000;
        }
        if ($parts['letters'][2] > 'I') {
            $grid_northing -= 100000;
        }
        if ($grid_northing >= 2000000) {
            $grid_northing -= 2000000;
        }

        $min_northing = $this->getMinNorthing($parts['letters'][0]);
        $scaled_min_northing = $min_northing;
        while ($scaled_min_northing >= 2000000) {
            $scaled_min_northing -= 2000000;
        }
        $grid_northing -= $scaled_min_northing;
        if ($grid_northing < 0) {
            $grid_northing += 2000000;
        }
        $grid_northing += $min_northing;

        $data['easting'] = $grid_easting + $parts['easting'];
        $data['northing'] = $grid_northing + $parts['northing'];
        $data['string'] = $data['zone'] . ' ' . $data['hemisphere'] . ' ' . (int)$data['easting'] . ' ' . (int)$data['northing'];

        return $data;
    }

    /**
     * parse a MGRS coordinate string into its components
     *
     * @param string $text the string to parse
     * @return array the components (zone, letters, easting, northing, error (if any))
     */
    protected function parseMGRSstring(string $text): array
    {
        $data = array();

        // 33UXP0177940548
        $mgrsString = str_replace(' ', '', trim($text));  // erase all blanks
        $pointerRun = $pointerStart = 0;

        while (is_numeric($mgrsString[$pointerRun])) {
            $pointerRun++;
        }
        $num_digits = $pointerRun - $pointerStart;
        if ($num_digits <= 2) {
            if ($num_digits > 0) {
                $data['zone'] = intval(substr($mgrsString, $pointerStart, $num_digits));
            } else {
                $data['zone'] = 0;
            }
        } else {
            return array('error' => 'no valid MGRS string');
        }
        $pointerStart = $pointerRun;

        while (ctype_alpha($mgrsString[$pointerRun])) {
            $pointerRun++;
        }
        $num_letters = $pointerRun - $pointerStart;
        if ($num_letters == 3) {
            $data['letters'] = strtoupper(substr($mgrsString, $pointerStart, $num_letters));
            if ($data['letters'][0] <= 'B' || $data['letters'][0] >= 'Y' ||
                $data['letters'][0] == 'I' || $data['letters'][0] == 'O' ||
                $data['letters'][1] == 'I' || $data['letters'][1] == 'O' ||
                $data['letters'][2] == 'I' || $data['letters'][2] == 'O') {
                return array('error' => 'no valid MGRS string');
            }
        } else {
            return array('error' => 'no valid MGRS string');
        }
        $pointerStart = $pointerRun;

        while (is_numeric($mgrsString[$pointerRun])) {
            $pointerRun++;
        }
        $num_digits = $pointerRun - $pointerStart;
        if ($num_digits <= 10 && $num_digits % 2 == 0) {
            $n = $num_digits / 2;
            $data['precision'] = $n;
            if ($n > 0) {
                $multiplier = pow(10.0, 5 - $n);
                $data['easting'] = intval(substr($mgrsString, $pointerStart, $n)) * $multiplier;
                $data['northing'] = intval(substr($mgrsString, $pointerStart + $n, $n)) * $multiplier;
            } else {
                $data['easting'] = $data['northing'] = 0;
            }
        } else {
            return array('error' => 'no valid MGRS string');
        }

        $data['error'] = '';
        return $data;
    }

    /**
     * set the letter range used for the 2nd letter in the MGRS coordinate string, based on the set number of the utm zone (zone % 6)
     * also set the false northing for even numbered zones
     *
     * @param int $zone zone-number
     * @return array ltr2_low_value, ltr2_high_value and false_northing
     */
    protected function getGridValues(int $zone): array
    {
        $set_number = $zone % 6;  // Set number (1-6) based on UTM zone number
        if (!$set_number) {
            $set_number = 6;
        }

        if ($set_number == 1 || $set_number == 4) {
            $data['ltr2_low_value'] = 'A';
            $data['ltr2_high_value'] = 'H';
        } elseif ($set_number == 2 || $set_number == 5) {
            $data['ltr2_low_value'] = 'J';
            $data['ltr2_high_value'] = 'R';
        } else {
            $data['ltr2_low_value'] = 'S';
            $data['ltr2_high_value'] = 'Z';
        }

        $data['false_northing'] = (($set_number % 2) == 0) ? 1500000 : 0;

        return $data;
    }

    /**
     * This function receives a latitude band letter and returns the minimum northing
     *
     * @param string $letter latitude band letter
     * @return float|int
     */
    protected function getMinNorthing(string $letter): float|int
    {
        if ($letter == 'X') {
            return 7900000;
        } elseif ($letter == 'W') {
            return 7000000;
        } elseif ($letter >= 'P') {
            return (ord($letter) - ord('P')) * 900000 + 800000;
        } elseif ($letter == 'N') {
            return 0;
        } elseif ($letter >= 'J') {
            return (ord($letter) - ord('J')) * 900000 + 6400000;
        } elseif ($letter >= 'E') {
            return (ord($letter) - ord('E')) * 900000 + 2800000;
        } elseif ($letter == 'D') {
            return 2000000;
        } else {  // C
            return 1100000;
        }
    }

}
