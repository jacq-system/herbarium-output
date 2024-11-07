<?php

namespace App\Controller\Services\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AutocompleteController extends AbstractFOSRestController
{
    #[Get(
        path: '/services/rest/autocomplete/scientificNames/{term}',
        summary: 'Search for fitting scientific names and return them',
        tags: ['autocomplete'],
        parameters: [
            new PathParameter(
                name: 'term',
                description: 'part of a scientific name to autocomplete',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string'),
                example: 'Aster'
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'List of taxa names',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        type: 'array',
                        items: new Items(
                            properties: [
                                new Property(property: 'label', type: 'string', example: 'scientific name'),
                                new Property(property: 'value', type: 'string', example: 'scientific name'),
                                new Property(property: 'id', type: 'integer', example: 1),
                                new Property(property: 'uuid', type: 'object', example: '{"href": "url to get the uuid"}')

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
                                    new Property(property: 'label', type: 'string', example: 'scientific name'),
                                    new Property(property: 'value', type: 'string', example: 'scientific name'),
                                    new Property(property: 'id', type: 'integer', example: 1),
                                    new Property(property: 'uuid', type: 'object', example: '{"href": "url to get the uuid"}')

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
    #[Route('/services/rest/autocomplete/scientificNames/{term}.{_format}', defaults: ['_format' => 'json'],  methods: ['GET'])]
    public function scientificNames(string $term): Response
    {
        $data = [
            'message' => $term,
            'email' => "hh",
        ];
        $view = $this->view($data, 200);
        return $this->handleView($view);
    }

}
