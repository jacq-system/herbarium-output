<?php

namespace App\Controller\Services\Rest;

use App\Service\TaxonNameService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ScinamesController extends AbstractFOSRestController
{
    public function __construct(protected readonly TaxonNameService $taxaNamesService)
    {
    }

    #[Get(
        path: '/services/rest/JACQscinames/uuid/{taxonID}',
        summary: 'Get uuid, uuid-url and scientific name of a given taxonID',
        tags: ['scinames'],
        parameters: [
            new PathParameter(
                name: 'taxonID',
                description: 'ID of taxon name',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 249254
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
    #[Route('/services/rest/JACQscinames/uuid/{taxonID}.{_format}', name: "services_rest_scinames_uuid", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function scientificNames(int $taxonID): Response
    {
        $results = [];
        //TODO
        $view = $this->view($results, 200);
        return $this->handleView($view);
    }

}
