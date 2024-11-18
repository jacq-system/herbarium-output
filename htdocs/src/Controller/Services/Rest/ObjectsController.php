<?php declare(strict_types=1);

namespace App\Controller\Services\Rest;

use App\Facade\Rest\ObjectsFacade;
use App\Service\Rest\SpecimenMapper;
use App\Service\Rest\StatisticsService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class ObjectsController extends AbstractFOSRestController
{
    public function __construct(protected readonly ObjectsFacade $objectsFacade)
    {
    }

    #[Get(
        path: '/services/rest/objects/specimens/search',
        summary: '"/specimens/search" is deprecated, use "/specimens" instead',
        tags: ['objects'],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 307,
                description: 'Deprecated'
            ),
            new \OpenApi\Attributes\Response(
                response: 400,
                description: 'Bad Request'
            )
        ]
    )]
    #[Route('/services/rest/objects/specimens/search.{_format}', defaults: ['_format' => 'json'], methods: ['GET'])]
    public function results(Request $request): Response
    {
        return $this->redirectToRoute('services_rest_objects_specimens', $request->query->all(), 307);
    }

    #[Get(
        path: '/services/rest/objects/specimens/{specimenID}',
        summary: 'get the properties of a specimen',
        tags: ['objects'],
        parameters: [
            new PathParameter(
                name: 'specimenID',
                description: 'ID of specimen',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 123456
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
                                new Property(property: 'results', type: 'object')
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
                                    new Property(property: 'results')
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
    #[Route('/services/rest/objects/specimens/{specimenID}.{_format}', name: "services_rest_objects_specimen", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function specimen(int $specimenID): Response
    {
        $data = $this->objectsFacade->resolveSpecimen($specimenID);
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/objects/specimens',
        summary: 'search for all specimens which fit given criteria',
        tags: ['objects'],
        parameters: [
            new QueryParameter(
                name: 'p',
                description: 'optional number of page to display, starts with 0 (first page), defaults to 0',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer'),
                example: 2
            ),
            new QueryParameter(
                name: 'rpp',
                description: 'optional number of records per page to display (<= 100), defaults to 50',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer'),
                example: 20
            ),
            new QueryParameter(
                name: 'list',
                description: 'optional switch if all specimen data should be returned (=0) or just a list of specimen-IDs (=1), defaults to 1',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer'),
                example: 1
            ),
            new QueryParameter(
                name: 'term',
                description: 'ptional search term for scientific names, use * as a wildcard, multiple terms seperated by \',\'',
                in: 'query',
                required: false,
                schema: new Schema(type: 'string'),
                example: "prunus av*"
            ),
            new QueryParameter(
                name: 'sc',
                description: 'optional search term for source codes, case insensitive',
                in: 'query',
                required: false,
                schema: new Schema(type: 'string'),
                example: "wu"
            ),
            //TODO in original coll, but seems as a typo
            new QueryParameter(
                name: 'collnr',
                description: 'optional search term for collector(s), case insensitive',
                in: 'query',
                required: false,
                schema: new Schema(type: 'string'),
                example: "rainer"
            ),
            new QueryParameter(
                name: 'type',
                description: 'optional switch to search for type records only, defaults to 0',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer'),
                example: 1
            ),
            new QueryParameter(
                name: 'sort',
                description: 'optional sorting of results, seperated by commas, \'-\' as first character changes sorting to DESC, possible items are sciname (scientific name), coll (collector(s)), ser (series), num (collectors number), herbnr (herbarium number), defaults to sciname,herbnr',
                in: 'query',
                required: false,
                schema: new Schema(type: 'string'),
                example: "coll,num"
            ),
            new QueryParameter(
                name: 'herbnr',
                description: '',
                in: 'query',
                required: false,
                schema: new Schema(type: 'string')
            ),
            new QueryParameter(
                name: 'nation',
                description: '',
                in: 'query',
                required: false,
                schema: new Schema(type: 'string')
            ),
            new QueryParameter(
                name: 'withImages',
                description: '',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer'),
                example: 0
            ),
            new QueryParameter(
                name: 'cltr',
                description: 'collector',
                in: 'query',
                required: false,
                schema: new Schema(type: 'string')
            ),
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
                                new Property(property: 'results', type: 'object')
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
                                    new Property(property: 'results')
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
    #[Route('/services/rest/objects/specimens.{_format}', name: "services_rest_objects_specimens", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function specimens(#[MapQueryParameter] ?int $p = 0,#[MapQueryParameter] ?int $rpp = 50,#[MapQueryParameter] ?int $list = 1,#[MapQueryParameter] ?string $term = '',#[MapQueryParameter] ?string $sc = '',#[MapQueryParameter] ?string $collnr = '',#[MapQueryParameter] ?int $type = 0,#[MapQueryParameter] ?string $sort = 'sciname,herbnr',#[MapQueryParameter] ?string $herbnr = '', #[MapQueryParameter] ?string $nation = '', #[MapQueryParameter] ?int $withImages = 0, #[MapQueryParameter] ?string $cltr = ''): Response
    {
        ($rpp > 100) ? $rpp = 100 : null;
        $data = $this->objectsFacade->resolveSpecimens( $p, $rpp, $list, $term,$sc,$collnr,$type,$sort, $herbnr, $nation, $withImages, $cltr);
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }
}
