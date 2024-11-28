<?php declare(strict_types=1);

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StatisticsExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('median', [$this, 'calculateMedian']),
            new TwigFilter('avg', [$this, 'calculateAvg']),
        ];
    }

    public function calculateMedian(array $numbers): float
    {
        sort($numbers);
        $count = count($numbers);

        if ($count % 2 === 1) {
            return $numbers[intval($count / 2)];
        } else {
            return ($numbers[$count / 2 - 1] + $numbers[$count / 2]) / 2;
        }
    }

    public function calculateAvg(array $numbers): float
    {
        $sum = array_sum($numbers);
        $count = count($numbers);

        if ($count > 0) {
            return $sum / $count;
        }

        return 0;
    }
}
