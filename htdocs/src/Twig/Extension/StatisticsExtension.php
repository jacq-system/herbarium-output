<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StatisticsExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('median', [$this, 'calculateMedian']),
            new TwigFilter('avg', [$this, 'calculateAvg']),
        ];
    }

    /**
     * @param list<int> $numbers
     */
    public function calculateMedian(array $numbers): float
    {
        sort($numbers, SORT_NUMERIC);

        $count = count($numbers);
        $mid = intdiv($count, 2);

        if (1 === $count % 2) {
            return (float) $numbers[$mid];
        }

        return ($numbers[$mid - 1] + $numbers[$mid]) / 2;
    }

    /**
     * @param list<int> $numbers
     */
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
