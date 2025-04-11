<?php declare(strict_types=1);

namespace App\Service;

readonly class UuidConfiguration
{
    public function __construct(protected(set) string $endpoint, protected(set) string $secret)
    {
    }


}
