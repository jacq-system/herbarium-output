<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use JACQ\UI\Http\SearchFormSessionService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SearchFormExtension extends AbstractExtension
{
    public function __construct(private readonly SearchFormSessionService $searchFormSessionService)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('sortableChar', [$this, 'getSortableChar']),
        ];
    }

    public function getSortableChar(string $column): string
    {
        $sort = $this->searchFormSessionService->getSort();
        if (null !== $sort && key($sort) === $column) {
            if ('ASC' === $sort[$column]) {
                return ' ↓';
            }

            return ' ↑';
        }

        return '';
    }
}
