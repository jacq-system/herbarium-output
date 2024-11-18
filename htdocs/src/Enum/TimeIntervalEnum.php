<?php declare(strict_types=1);

namespace App\Enum;

enum TimeIntervalEnum: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';
}
