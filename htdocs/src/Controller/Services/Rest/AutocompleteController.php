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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AutocompleteController extends AbstractFOSRestController
{
    public function __construct(protected readonly TaxonService $taxaNamesService)
    {
    }

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
                example: 'Asteranth'
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
                                new Property(property: 'label', description: 'scientific name', type: 'string', example: 'Aster L.'),
                                new Property(property: 'value', description: 'scientific name', type: 'string', example: 'Aster L.'),
                                new Property(property: 'id', description: 'ID of taxon name', type: 'integer', example: 16885),
                                new Property(property: 'uuid', description: 'URL to UUID service', type: 'object', example: '{"href": "url to get the uuid"}')
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
                                    new Property(property: 'label', description: 'scientific name', type: 'string', example: 'Aster L.'),
                                    new Property(property: 'value', description: 'scientific name', type: 'string', example: 'Aster L.'),
                                    new Property(property: 'id', description: 'ID of taxon name', type: 'integer', example: 16885),
                                    new Property(property: 'uuid', description: 'URL to UUID service', type: 'object', example: '{"href": "url to get the uuid"}')
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
    #[Route('/services/rest/autocomplete/scientificNames/{term}.{_format}', defaults: ['_format' => 'json'], methods: ['GET'])]
    public function scientificNames(string $term): Response
    {
        $results = [];
        $data = $this->taxaNamesService->autocompleteStartsWith($term);
        foreach ($data as $row) {
            $results[] = array(
                "label" => $row['ScientificName'],
                "value" => $row['ScientificName'],
                "id" => $row['taxonID'],
                "uuid" => array('href' => $this->generateUrl('services_rest_scinames_uuid', ['taxonID' => $row['taxonID']], UrlGeneratorInterface::ABSOLUTE_URL))
            );
        }
        $view = $this->view($results, 200);

        return $this->handleView($view);
    }

}
