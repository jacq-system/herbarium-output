<?php declare(strict_types=1);

namespace App\Controller\Services\Rest;

use App\Service\SpecimenService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class StableIdentifierController extends AbstractFOSRestController
{
    public function __construct(protected readonly SpecimenService $specimenService)
    {
    }

    #[Get(
        path: '/services/rest/stableIdentifier/sid/{specimenID}',
        summary: 'Get specimen-id, valid stable identifier and all stable identifiers of a given specimen-id',
        tags: ['stable identifier'],
        parameters: [
            new PathParameter(
                name: 'specimenID',
                description: 'ID of specimen',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 1385945
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
                                new Property(property: 'specimenID', description: 'ID of specimen', type: 'integer', example: 1385945
                                ),
                                new Property(property: 'stableIdentifierLatest', description: 'Latest stable identifier', properties: [
                                    new Property(property: 'stableIdentifier', description: 'Stable identifier', type: 'string'),
                                    new Property(property: 'timestamp', description: 'Timestamp of the stable identifier', type: 'string', format: 'date-time'),
                                    new Property(property: 'link', description: 'Link to details page of JACQ (for convenience)', type: 'string', format: 'uri'),
                                ],
                                    type: 'object'
                                ),
                                new Property(
                                    property: 'stableIdentifierList', description: 'List of all found stable identifiers, ordered by timestamp', type: 'array',
                                    items: new Items(
                                        properties: [
                                            new Property(property: 'stableIdentifier', description: 'Stable identifier', type: 'string'),
                                            new Property(property: 'timestamp', description: 'Timestamp of the stable identifier', type: 'string', format: 'date-time'),
                                        ],
                                        type: 'object'
                                    )
                                ),
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
    #[Route('/services/rest/stableIdentifier/sid/{specimenID}', methods: ['GET'])]
    public function sid(int $specimenID): Response
    {
        $results = [];
        try {
            $specimen = $this->specimenService->findAccessibleForPublic($specimenID);
        }
        catch (\Exception $e){
            $view = $this->view([], 404);
            return $this->handleView($view);
        }
        if (!empty($specimen->getStableIdentifiers())){
            $results['specimenID'] = $specimen->getId();
            $results['stableIdentifierLatest'] = $this->specimenService->sid2array($specimen);
            $results['stableIdentifierList'] = $this->specimenService->sids2array($specimen);
        }else{
            $view = $this->view("Stable Identifier does not exist", 404);
            return $this->handleView($view);
        }
        $view = $this->view($results, 200);
        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/stableIdentifier/resolve/{sid}',
        summary: 'Get specimen-id, valid stable identifier and all stable identifiers of a given stable identifier. ',
        tags: ['stable identifier'],
        parameters: [
            new PathParameter(
                name: 'sid',
                description: 'stable identifier of specimen',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string'),
                example: 'https://wu.jacq.org/WU-0000264'
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
                                new Property(property: 'specimenID', description: 'ID of specimen', type: 'integer', example: 6830
                                ),
                                new Property(property: 'stableIdentifierLatest', description: 'Latest stable identifier', properties: [
                                    new Property(property: 'stableIdentifier', description: 'Stable identifier', type: 'string'),
                                    new Property(property: 'timestamp', description: 'Timestamp of the stable identifier', type: 'string', format: 'date-time'),
                                    new Property(property: 'link', description: 'Link to details page of JACQ (for convenience)', type: 'string', format: 'uri'),
                                ],
                                    type: 'object'
                                ),
                                new Property(
                                    property: 'stableIdentifierList', description: 'List of all found stable identifiers, ordered by timestamp', type: 'array',
                                    items: new Items(
                                        properties: [
                                            new Property(property: 'stableIdentifier', description: 'Stable identifier', type: 'string'),
                                            new Property(property: 'timestamp', description: 'Timestamp of the stable identifier', type: 'string', format: 'date-time'),
                                        ],
                                        type: 'object'
                                    )
                                ),
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
    #[Route('/services/rest/stableIdentifier/resolve/{sid}', requirements: ['sid' => '^https:\/\/(?:[a-zA-Z0-9.-]+\.)?jacq\.org\/[A-Za-z0-9\-]+$'], methods: ['GET'])]
    public function resolve(string $sid): Response
    {
        //TODO removed the "withRedirect" option in OPenApi, solving by "nonvisible" forward inside the framework
        $sid = urldecode($sid);
        return $this->forward(self::class . '::sid', ['specimenID' => $this->specimenService->findSpecimenIdUsingSid($sid)]);
    }

    #[Get(
        path: '/services/rest/stableIdentifier/errors',
        summary: 'get a list of all errors which prevent the generation of stable identifier',
        tags: ['stable identifier'],
        parameters: [
            new QueryParameter(
                name: 'sourceID',
                description: 'optional ID of source to check (default=all sources)',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer', nullable: true),
                example: 30
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
                                new Property(
                                    property: 'total',
                                    description: 'Total number of records found',
                                    type: 'integer'
                                ),
                                new Property(
                                    property: 'result',
                                    description: 'List of found entries',
                                    type: 'array',
                                    items: new Items(
                                        properties: [
                                            new Property(
                                                property: 'specimenID',
                                                description: 'ID of specimen',
                                                type: 'integer'
                                            ),
                                            new Property(
                                                property: 'link',
                                                description: 'Link to details-page of JACQ (for convenience)',
                                                type: 'string',
                                                format: 'uri'
                                            ),
                                            new Property(
                                                property: 'errorList',
                                                description: 'List of errors and existing stable identifiers (if any) for this specimen-ID',
                                                type: 'array',
                                                items: new Items(
                                                    properties: [
                                                        new Property(
                                                            property: 'stableIdentifier',
                                                            description: 'Stable identifier (if it exists) or null',
                                                            type: 'string',
                                                            nullable: true
                                                        ),
                                                        new Property(
                                                            property: 'timestamp',
                                                            description: 'Timestamp of creation',
                                                            type: 'string',
                                                            format: 'date-time'
                                                        ),
                                                        new Property(
                                                            property: 'error',
                                                            description: 'The error description',
                                                            type: 'string'
                                                        ),
                                                        new Property(
                                                            property: 'link',
                                                            description: 'Link to details-page of JACQ of the blocking specimen (if present)',
                                                            type: 'string',
                                                            format: 'uri',
                                                            nullable: true
                                                        ),
                                                    ],
                                                    type: 'object'
                                                )
                                            ),
                                        ],
                                        type: 'object'
                                    )
                                ),
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
    #[Route('/services/rest/stableIdentifier/errors', methods: ['GET'])]
    public function errors(#[MapQueryParameter] ?int $sourceID = null): Response
    {
        $results = $this->specimenService->getEntriesWithErrors($sourceID);
        $view = $this->view($results, 200);
        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/stableIdentifier/multi',
        summary: 'Get all entries with more than one stable identifier per specimen-ID',
        tags: ['stable identifier'],
        parameters: [
            new QueryParameter(
                name: 'page',
                description: 'optional number of page to be returned (default=first page)',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer', nullable: true),
                example: 2
            ),
            new QueryParameter(
                name: 'entriesPerPage',
                description: 'optional number entries per page (default=50)',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer', nullable: true),
                example: 6
            ),
            new QueryParameter(
                name: 'sourceID',
                description: 'optional ID of source to check (default=all sources)',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer', nullable: true),
                example: 30
            ),
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
                            properties: array(
                                new Property(property: 'page', description: 'Page currently displayed', type: 'integer'),
                                new Property(property: 'previousPage', description: 'Link to the previous page', type: 'string', format: 'uri', nullable: true),
                                new Property(property: 'nextPage', description: 'Link to the next page', type: 'string', format: 'uri', nullable: true),
                                new Property(property: 'firstPage',description: 'Link to the first page',type: 'string',format: 'uri'),
                                new Property(property: 'lastPage',description: 'Link to the last page',type: 'string',format: 'uri'),
                                new Property(property: 'totalPages',description: 'Total number of pages',type: 'integer'),
                                new Property(property: 'total',description: 'Total number of records found',type: 'integer'),
                                new Property(property: 'result',description: 'List of found entries',type: 'array',
                                    items: new Items(
                                        properties: array(
                                            new Property(property: 'specimenID',description: 'ID of the specimen',type: 'integer'),
                                            new Property(property: 'numberOfEntries',description: 'Number of records found for this specimen ID',type: 'integer'),
                                            new Property(property: 'stableIdentifierList',description: 'List of stable identifiers for this specimen ID',type: 'array',
                                                items: new Items(
                                                    properties: array(
                                                        new Property(property: 'stableIdentifier',description: 'Stable identifier',type: 'string'),
                                                        new Property(property: 'timestamp',description: 'Timestamp associated with the stable identifier',type: 'string',format: 'date-time'),
                                                    ),
                                                    type: 'object'
                                                )
                                            ),
                                        ),
                                        type: 'object'
                                    )
                                ),
                            ),
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
    #[Route('/services/rest/stableIdentifier/multi', name: "services_rest_sid_multi", methods: ['GET'])]
    public function multi(#[MapQueryParameter] int $page = 1, #[MapQueryParameter] int $entriesPerPage = 6, #[MapQueryParameter]  ?int $sourceID = null): Response
    {
        if ($sourceID !== null) {
            $results = $this->specimenService->getMultipleEntriesFromSource($sourceID);
        }else{
            $results = $this->specimenService->getMultipleEntries($page, $entriesPerPage);
        }

        $view = $this->view($results, 200);

        return $this->handleView($view);
    }
}
