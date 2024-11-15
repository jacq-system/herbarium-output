<?php declare(strict_types=1);

namespace App\Controller\Services\Rest;

use App\Facade\Rest\IiifFacade;
use App\Service\IiifService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImagesController extends AbstractFOSRestController
{
    public function __construct(protected readonly IiifFacade $iiifFacade)
    {
    }


    #[Route('/services/rest/images/show/{specimenID}.{_format}', name: "services_rest_images_show", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function show(int $specimenID): Response
    {
        $results = $this->iiifFacade->resolveManifestUri($specimenID);

        $view = $this->view($results, 200);

        return $this->handleView($view);
    }


    #[Route('/services/rest/images/download/{specimenID}.{_format}', name: "services_rest_images_download", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function download(int $specimenID): Response
    {
        $results = $this->iiifFacade->resolveManifestUri($specimenID);

        $view = $this->view($results, 200);

        return $this->handleView($view);
    }
}
