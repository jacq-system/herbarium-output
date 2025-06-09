<?php declare(strict_types=1);

namespace App\Twig\Extension;

use App\Service\Output\SearchFormSessionService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SearchFormExtension extends AbstractExtension
{
    public function __construct(readonly private SearchFormSessionService $searchFormSessionService)
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
        if ($sort === null && $column === 'taxon') {
            return ' ↓';
        }
        if ($sort !== null && key($sort) === $column) {
            if ($sort[$column] === 'ASC') {
                return ' ↓';
            } else {
                return ' ↑';
            }
        }

        return "";
    }


}
