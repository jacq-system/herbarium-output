<?php declare(strict_types=1);

namespace App\Controller\Services\Rest;

use App\Facade\Rest\IiifFacade;
use App\Service\IiifService;
use App\Service\SpecimenService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IiifController extends AbstractFOSRestController
{
    public function __construct(protected readonly IiifFacade $iiifFacade, protected readonly SpecimenService $specimenService)
    {
    }

    #[Get(
        path: '/services/rest/iiif/manifestUri/{specimenID}',
        summary: 'get the manifest URI for a given specimen-ID',
        tags: ['iiif'],
        parameters: [
            new PathParameter(
                name: 'specimenID',
                description: 'ID of specimen',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 1739342
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'List of taxa names',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        properties: [
                            new Property(property: 'uri', description: 'uri of specimen', type: 'string', example: 'https://services.jacq.org/jacq-services/rest/iiif/manifest/1739342')
                        ]
                    )
                )
                ]
            ),
            new \OpenApi\Attributes\Response(
                response: 400,
                description: 'Bad Request'
            )
        ]
    )]
    #[Route('/services/rest/iiif/manifestUri/{specimenID}', name: "services_rest_iiif_manifest_uri", methods: ['GET'])]
    public function manifestUri(int $specimenID): Response
    {
        try {
            $specimen = $this->specimenService->findAccessibleForPublic($specimenID);
        }catch (\Exception $e){
            $view = $this->view([], 404);
            return $this->handleView($view);
        }
        $results['uri'] = $this->iiifFacade->resolveManifestUri($specimen);

        $view = $this->view($results, 200);

        return $this->handleView($view);
    }


    #[Get(
        path: '/services/rest/iiif/manifest/{specimenID}',
        summary: 'act as a proxy and get the manifest for a given specimen-ID from a backend, supplemented by some additional information.
If no backend is configured, the webservice tries to get the manifest from the actual target-uri.',
        tags: ['iiif'],
        parameters: [
            new PathParameter(
                name: 'specimenID',
                description: 'ID of specimen',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 1739342
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'List of taxa names',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        properties: [
                            new Property(property: 'manifest')
                        ]
                    )
                )
                ]
            ),
            new \OpenApi\Attributes\Response(
                response: 400,
                description: 'Bad Request'
            )
        ]
    )]
    #[Route('/services/rest/iiif/manifest/{specimenID}', name: "services_rest_iiif_manifest", methods: ['GET'])]
    public function manifest(int $specimenID): Response
    {
        try {
            $specimen = $this->specimenService->findAccessibleForPublic($specimenID);
        }catch (\Exception $e){
            $view = $this->view([], 404);
            return $this->handleView($view);
        }

        $results = $this->iiifFacade->getManifest($specimen);
        $view = $this->view($results, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/iiif/createManifest/{serverID}/{imageIdentifier}',
        summary: 'create a manifest for an image server with a given image filename',
        tags: ['iiif'],
        parameters: [
            new PathParameter(
                name: 'serverID',
                description: 'ID of image server',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 1
            ),
            new PathParameter(
                name: 'imageIdentifier',
                description: 'image Identifier',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string'),
                example: 1
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'List of taxa names',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        properties: [
                            new Property(property: 'manifest')
                        ]
                    )
                )
                ]
            ),
            new \OpenApi\Attributes\Response(
                response: 400,
                description: 'Bad Request'
            )
        ]
    )]
    #[Route('/services/rest/iiif/createManifest/{serverID}/{imageIdentifier}', name: "services_rest_iiif_createManifest", methods: ['GET'])]
    public function createManifest(int $serverID, string $imageIdentifier): Response
    {
        $manifest = $this->iiifFacade->createManifestFromExtendedCantaloupeImage($serverID, $imageIdentifier);

        if(empty($manifest)){
            return new JsonResponse(null, 404);
        }
        $view = $this->view($manifest, 200);

        return $this->handleView($view);
    }

}
