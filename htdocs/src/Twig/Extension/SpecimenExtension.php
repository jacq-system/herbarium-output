<?php declare(strict_types=1);

namespace App\Twig\Extension;

use App\Facade\Rest\IiifFacade;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SpecimenExtension extends AbstractExtension
{
    public function __construct(protected readonly IiifFacade $iiifFacade)
    {
    }

    public function getFilters()
    {
        return [
            new TwigFilter('manifestUrl', [$this, 'getManifest']),
        ];
    }

    public function getManifest(int $specimenId): string
    {
      return $this->iiifFacade->resolveManifestUri($specimenId);
    }


}
