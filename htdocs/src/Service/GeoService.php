<?php declare(strict_types=1);

namespace App\Service;

readonly class GeoService extends BaseService
{

    public function decimalToDMS(float $decimal): string
    {
        $degrees = (int)$decimal;
        $minutesDecimal = abs($decimal - $degrees) * 60;
        $minutes = (int)$minutesDecimal;
        $seconds = ($minutesDecimal - $minutes) * 60;

        return $degrees . '° ' . $minutes . "'" . $seconds . '"';
    }

}
