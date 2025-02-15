<?php declare(strict_types=1);

namespace App\Controller\Services\Rest;

use App\Service\ClassificationService;
use App\Service\OrganisationService;
use App\Service\Rest\CoordinateBoundaryService;
use App\Service\Rest\CoordinateConversionService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class LivingPlantsController extends AbstractFOSRestController
{
    public function __construct(protected readonly OrganisationService  $organisationService, protected readonly ClassificationService $classificationService)
    {
    }

    #[Get(
        path: '/services/rest/livingplants/derivatives',
        summary: 'find all derivatives which fit given criteria',
        tags: ['livingplants'],
        parameters: [
            new QueryParameter(
                name: 'org',
                description: 'optional id of organisation (and its children), defaults to all',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer'),
                example: 4
            ),
            new QueryParameter(
                name: 'separated',
                description: 'optional status of separated bit (0 or 1), defaults to all',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer'),
                example: 0
            ),
            new QueryParameter(
                name: 'derivativeID',
                description: 'optional derivate-id; if given, only the derivative with this id will be returned, defaults to all',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer'),
                example: 1645
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'List',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        type: 'array',
                        items: new Items(
                            properties: [
                                new Property(property: 'zone', type: 'integer', example: 33),
                                new Property(property: 'hemisphere', type: 'string', example: "N"),
                                new Property(property: 'easting', type: 'integer', example: 601779),
                                new Property(property: 'northing', type: 'integer', example: 5340548),
                                new Property(property: 'string', type: 'string', example: "33U 601779 5340548"),
                            ],
                            type: 'object'
                        )
                    )
                ),
                    new MediaType(
                        mediaType: 'application/xml',
                        schema: new Schema(
                            type: 'array',
                            items: new Items(
                                properties: [
                                    new Property(property: 'zone', type: 'integer', example: 33),
                                    new Property(property: 'hemisphere', type: 'string', example: "N"),
                                    new Property(property: 'easting', type: 'integer', example: 601779),
                                    new Property(property: 'northing', type: 'integer', example: 5340548),
                                    new Property(property: 'string', type: 'string', example: "33U 601779 5340548"),
                                ],
                                type: 'object'
                            )
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
    #[Route('/services/rest/livingplants/derivatives', name: "services_rest_livingplants_derivatives", methods: ['GET'])]
    public function derivatives(#[MapQueryParameter] ?int $org, #[MapQueryParameter] ?int $separated = 0, #[MapQueryParameter] ?int $derivativeID = null): Response
    {
        //TODO probably deprecated route
        $criteria = array();
        if (isset($org)) {
            $criteria['organisationIds'] = $this->organisationService->getAllChildren($org);
        }
        if (isset($separated)) {
            $criteria['separated'] = $separated;
        }
        if (isset($derivativeID)) {
            $criteria['derivativeID'] = $derivativeID;
        }

        $data = $this->classificationService->getList($criteria);
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }


}
