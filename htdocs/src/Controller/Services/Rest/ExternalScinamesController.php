<?php declare(strict_types = 1);

namespace App\Controller\Services\Rest;

use App\Service\TaxonService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ExternalScinamesController extends AbstractFOSRestController
{
    public function __construct(protected readonly TaxonService $taxaNamesService)
    {
    }

    #[Get(
        path: '/services/rest/externalScinames/find/{term}',
        summary: 'search for scientific names; get IDs and scientific names of search result',
        tags: ['externalScinames'],
        parameters: [
            new PathParameter(
                name: 'term',
                description: 'search term',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string'),
                example: 'Rhopalocarpus alternifolius (Baker) Capuron'
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'uuid, uuid-url and scientific name',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        type: 'array',
                        items: new Items(
                            properties: [
                                new Property(property: 'uuid', description: 'Universally Unique Identifier', type: 'string'), //TODO add examples
                                new Property(property: 'url', description: 'url for uuid request resolver', type: 'string'),
                                new Property(property: 'taxonID', description: 'ID of scientific name', type: 'integer'),
                                new Property(property: 'scientificName', description: 'scientific name', type: 'string'),
                                new Property(property: 'taxonName', description: 'scientific name without hybrids', type: 'string')
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
                                    new Property(property: 'uuid', description: 'Universally Unique Identifier', type: 'string'),
                                    new Property(property: 'url', description: 'url for uuid request resolver', type: 'string'),
                                    new Property(property: 'taxonID', description: 'ID of scientific name', type: 'integer'),
                                    new Property(property: 'scientificName', description: 'scientific name', type: 'string'),
                                    new Property(property: 'taxonName', description: 'scientific name without hybrids', type: 'string')
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
    #[Route('/services/rest/externalScinames/find/{term}.{_format}', name: "services_rest_externalScinames_find", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function uuid(string $term): Response
    {
        $results = ["not implemented, in development by Johannes"];
        //TODO not implemented, in development by Johannes
        $view = $this->view($results, 418);

        return $this->handleView($view);
    }


}
