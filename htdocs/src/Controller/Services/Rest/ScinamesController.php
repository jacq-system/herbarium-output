<?php declare(strict_types = 1);

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
        summary: 'Get scientific name, uuid and uuid-url of a given taxonID',
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
    public function uuid(int $taxonID): Response
    {
        $results = [];
        //TODO need access to another database
        $view = $this->view($results, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/JACQscinames/name/{taxonID}',
        summary: 'Get scientific name, uuid and uuid-url of a given taxonID',
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
    #[Route('/services/rest/JACQscinames/name/{taxonID}.{_format}', name: "services_rest_scinames_name", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function name(int $taxonID): Response
    {
        //TODO this service is just a synonym to $this->uuid()
        return $this->redirectToRoute('services_rest_scinames_uuid', ['taxonID' => $taxonID]);
    }

    #[Get(
        path: '/services/rest/JACQscinames/find/{term}',
        summary: 'fulltext search for scientific names and taxon names and also get their taxonIDs; all parts of the search term are mandatory for the search',
        tags: ['scinames'],
        parameters: [
            new PathParameter(
                name: 'term',
                description: 'look for all scientific names which have "prunus" and "martens" in it and something beginning with "aviu"',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string'),
                example: 'prunus aviu* martens'
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
                                new Property(property: 'taxonID', description: 'ID of scientific name', type: 'integer', example: 47239),
                                new Property(property: 'scientificName', description: 'scientific name', type: 'string', example: 'Prunus avium subsp. duracina (L.) Schübl. & G. Martens'),
                                new Property(property: 'taxonName', description: 'scientific name without hybrids', type: 'string', example: 'Prunus avium subsp. duracina (L.) Schübl. & G. Martens')
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
                                    new Property(property: 'taxonID', description: 'ID of scientific name', type: 'integer', example: 47239),
                                    new Property(property: 'scientificName', description: 'scientific name', type: 'string', example: 'Prunus avium subsp. duracina (L.) Schübl. & G. Martens'),
                                    new Property(property: 'taxonName', description: 'scientific name without hybrids', type: 'string', example: 'Prunus avium subsp. duracina (L.) Schübl. & G. Martens')
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
    #[Route('/services/rest/JACQscinames/find/{term}.{_format}', name: "services_rest_scinames_find", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function find(string $term): Response
    {
        $data =  $this->taxaNamesService->fulltextSearch($term);
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/JACQscinames/resolve/{uuid}',
        summary: 'Get scientific name, uuid-url and taxon-ID of a given uuid',
        tags: ['scinames'],
        parameters: [
            new PathParameter(
                name: 'uuid',
                description: 'uuid of taxon name',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string')
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
                                    new Property(property: 'uuid', description: 'Universally Unique Identifier', type: 'string'), //TODO add examples
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
    #[Route('/services/rest/JACQscinames/resolve/{uuid}.{_format}', name: "services_rest_scinames_resolve", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function resolve(string $uuid): Response
    {
        $data =  $this->taxaNamesService->findByUuid($uuid);
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }

}
