<?php declare(strict_types=1);

namespace App\Controller\Services\Rest;

use App\Facade\Rest\ClassificationDownloadFacade;
use App\Facade\Rest\ClassificationFacade;
use App\Service\ReferenceService;
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

class ClassificationController extends AbstractFOSRestController
{
    public function __construct(protected readonly ClassificationFacade $classificationFacade, protected readonly ClassificationDownloadFacade $classificationDownloadFacade)
    {
    }

    #[Get(
        path: '/services/rest/classification/references/{referenceType}/{referenceID}',
        summary: 'Fetch a list of all references (which have a classification attached) or a single reference',
        tags: ['classification'],
        parameters: [
            new PathParameter(
                name: 'referenceType',
                description: 'Type of reference (citation, person, service, specimen, periodical)',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string'),
                example: 'periodical'
            ),
            new PathParameter(
                name: 'referenceID',
                description: 'ID of reference',
                in: 'path',
                required: false, //TODO wrong concept - pathParameter must be required according to the OpenAPI/Swagger spec (https://github.com/OAI/OpenAPI-Specification/blob/main/versions/2.0.md#fixed-fields-7) -> a solution could be split into two routes (listAll, getByID)... Code works, but Swagger UI throws an error..
                schema: new Schema(type: 'integer', nullable: true),
                example: 15
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'fetch a list of all periodicals known to JACQ or returns by ID',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        type: 'array',
                        items: new Items(
                            properties: [
                                new Property(property: 'name', description: 'name of reference', type: 'string', example: 'Addisonia'),
                                new Property(property: 'id', description: 'ID of reference', type: 'integer', example: 15)
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
                                    new Property(property: 'name', description: 'name of reference', type: 'string', example: 'Addisonia'),
                                    new Property(property: 'id', description: 'ID of reference', type: 'integer', example: 15)
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
    #[Route('/services/rest/classification/references/{referenceType}/{referenceID}', name: "services_rest_classification_references", methods: ['GET'])]
    public function references(string $referenceType, ?int $referenceID = null): Response
    {
        $data = $this->classificationFacade->resolveByType($referenceType, $referenceID);
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/classification/nameReferences/{taxonID}',
        summary: '	Return (other) references for this name which include them in their classification',
        tags: ['classification'],
        parameters: [
            new PathParameter(
                name: 'taxonID',
                description: 'ID of taxon name',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 46163
            ),
            new QueryParameter(
                name: 'excludeReferenceId',
                description: 'optional Reference-ID to exclude (to avoid returning the \'active\' reference)',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer', nullable: true),
                example: 31070
            ),
            new QueryParameter(
                name: 'insertSeries',
                description: 'optional ID of citation-Series to be inserted',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer', nullable: true),
                example: 0
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'fetch a list of all periodicals known to JACQ or returns by ID',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        type: 'array',
                        items: new Items(
                            properties: [
                                new Property(property: 'referenceName', description: 'name of the reference', type: 'string', example: ''),
                                new Property(property: 'referenceId', description: 'ID of reference', type: 'integer', example: 15),
                                new Property(property: 'referenceType', description: 'Type of the reference', type: 'string', example: ''),
                                new Property(property: 'taxonID', description: 'the taxon-ID we asked for', type: 'integer', example: 15),
                                new Property(property: 'uuid', description: 'URL to UUID service', type: 'object', example: '{"href": "url to get the uuid"}'),
                                new Property(property: 'hasChildren', description: 'true if children of this entry exist', type: 'boolean', example: true),
                                new Property(property: 'hasType', description: ' true if Typi exist', type: 'boolean', example: false),
                                new Property(property: 'hasSpecimen', description: 'true if at least one specimen exists', type: 'boolean', example: false)
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
                                    new Property(property: 'referenceName', description: 'name of the reference', type: 'string', example: ''),
                                    new Property(property: 'referenceId', description: 'ID of reference', type: 'integer', example: 15),
                                    new Property(property: 'referenceType', description: 'Type of the reference', type: 'string', example: ''),
                                    new Property(property: 'taxonID', description: 'the taxon-ID we asked for', type: 'integer', example: 15),
                                    new Property(property: 'uuid', description: 'URL to UUID service', type: 'object', example: '{"href": "url to get the uuid"}'),
                                    new Property(property: 'hasChildren', description: 'true if children of this entry exist', type: 'boolean', example: true),
                                    new Property(property: 'hasType', description: ' true if Typi exist', type: 'boolean', example: false),
                                    new Property(property: 'hasSpecimen', description: 'true if at least one specimen exists', type: 'boolean', example: false)
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
    #[Route('/services/rest/classification/nameReferences/{taxonID}', name: "services_rest_classification_nameReferences", methods: ['GET'])]
    public function nameReferences(int $taxonID, #[MapQueryParameter] ?int $excludeReferenceId = 0, #[MapQueryParameter] ?int $insertSeries = 0): Response
    {
        $data = $this->classificationFacade->resolveNameReferences($taxonID, $excludeReferenceId, $insertSeries);
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/classification/children/{referenceType}/{referenceID}',
        summary: 'Get classification children of a given taxonID according to a given reference',
        tags: ['classification'],
        parameters: [
            new PathParameter(
                name: 'referenceType',
                description: 'Type of reference (citation, person, service, specimen, periodical)',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string'),
                example: 'periodical'
            ),
            new PathParameter(
                name: 'referenceID',
                description: 'ID of reference',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 70
            ),
            new QueryParameter(
                name: 'taxonID',
                description: 'optional ID of taxon name',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer', nullable: true),
                example: 0
            ),
            new QueryParameter(
                name: 'insertSeries',
                description: 'optional ID of citation-Series to be inserted',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer', nullable: true),
                example: 0
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'fetch a list of all periodicals known to JACQ or returns by ID',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        type: 'array',
                        items: new Items(
                            properties: [
                                new Property(property: 'taxonID', description: 'the taxon-ID we asked for', type: 'integer', example: 15),
                                new Property(property: 'uuid', description: 'URL to UUID service', type: 'object', example: '{"href": "url to get the uuid"}'),
                                new Property(property: 'referenceId', description: 'ID of reference', type: 'integer', example: 15),
                                new Property(property: 'referenceName', description: 'name of the reference', type: 'string', example: ''),
                                new Property(property: 'referenceType', description: 'Type of the reference', type: 'string', example: ''),
                                new Property(property: 'hasChildren', description: 'true if children of this entry exist', type: 'boolean', example: true),
                                new Property(property: 'hasType', description: ' true if Typi exist', type: 'boolean', example: false),
                                new Property(property: 'hasSpecimen', description: 'true if at least one specimen exists', type: 'boolean', example: false),
                                new Property(property: 'referenceInfo', description: '', type: 'object', example: '{"number": "classification number","order": "classification order","rank_abbr": "rank abbreviation","rank_hierarchy": "rank hierarchy","tax_syn_ID": "internal ID of synonym"}')
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
                                    new Property(property: 'taxonID', description: 'the taxon-ID we asked for', type: 'integer', example: 15),
                                    new Property(property: 'uuid', description: 'URL to UUID service', type: 'object', example: '{"href": "url to get the uuid"}'),
                                    new Property(property: 'referenceId', description: 'ID of reference', type: 'integer', example: 15),
                                    new Property(property: 'referenceName', description: 'name of the reference', type: 'string', example: ''),
                                    new Property(property: 'referenceType', description: 'Type of the reference', type: 'string', example: ''),
                                    new Property(property: 'hasChildren', description: 'true if children of this entry exist', type: 'boolean', example: true),
                                    new Property(property: 'hasType', description: ' true if Typi exist', type: 'boolean', example: false),
                                    new Property(property: 'hasSpecimen', description: 'true if at least one specimen exists', type: 'boolean', example: false),
                                    new Property(property: 'referenceInfo', description: '', type: 'object', example: '{"number": "classification number","order": "classification order","rank_abbr": "rank abbreviation","rank_hierarchy": "rank hierarchy","tax_syn_ID": "internal ID of synonym"}')
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
    #[Route('/services/rest/classification/children/{referenceType}/{referenceID}', name: "services_rest_classification_children", methods: ['GET'])]
    public function children(string $referenceType, int $referenceID, #[MapQueryParameter] ?int $taxonID = 0, #[MapQueryParameter] ?int $insertSeries = 0): Response
    {
        $data = $this->classificationFacade->resolveChildren($referenceType, $referenceID, $taxonID, $insertSeries);
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/classification/synonyms/{referenceType}/{referenceID}/{taxonID}',
        summary: 'Fetch synonyms (and basionym) for a given taxonID, according to a given reference',
        tags: ['classification'],
        parameters: [
            new PathParameter(
                name: 'referenceType',
                description: 'Type of reference (citation, person, service, specimen, periodical)',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string'),
                example: 'citation'
            ),
            new PathParameter(
                name: 'referenceID',
                description: 'ID of reference',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 31070
            ),
            new PathParameter(
                name: 'taxonID',
                description: 'ID of taxon name',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 46183
            ),
            new QueryParameter(
                name: 'insertSeries',
                description: 'optional ID of citation-Series to be inserted',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer', nullable: true),
                example: 0
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'fetch a list of all periodicals known to JACQ or returns by ID',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        type: 'array',
                        items: new Items(
                            properties: [
                                new Property(property: 'taxonID', description: 'the taxon-ID we asked for', type: 'integer', example: 15),
                                new Property(property: 'uuid', description: 'URL to UUID service', type: 'object', example: '{"href": "url to get the uuid"}'),
                                new Property(property: 'referenceId', description: 'ID of reference', type: 'integer', example: 15),
                                new Property(property: 'referenceName', description: 'name of the reference', type: 'string', example: ''),
                                new Property(property: 'referenceType', description: 'Type of the reference', type: 'string', example: ''),
                                new Property(property: 'hasType', description: ' true if Typi exist', type: 'boolean', example: false),
                                new Property(property: 'hasSpecimen', description: 'true if at least one specimen exists', type: 'boolean', example: false),
                                new Property(property: 'referenceInfo', description: '', type: 'object', example: '{"type": "","cited": ""}')
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
                                    new Property(property: 'taxonID', description: 'the taxon-ID we asked for', type: 'integer', example: 15),
                                    new Property(property: 'uuid', description: 'URL to UUID service', type: 'object', example: '{"href": "url to get the uuid"}'),
                                    new Property(property: 'referenceId', description: 'ID of reference', type: 'integer', example: 15),
                                    new Property(property: 'referenceName', description: 'name of the reference', type: 'string', example: ''),
                                    new Property(property: 'referenceType', description: 'Type of the reference', type: 'string', example: ''),
                                    new Property(property: 'hasType', description: ' true if Typi exist', type: 'boolean', example: false),
                                    new Property(property: 'hasSpecimen', description: 'true if at least one specimen exists', type: 'boolean', example: false),
                                    new Property(property: 'referenceInfo', description: '', type: 'object', example: '{"type": "","cited": ""}')
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
    #[Route('/services/rest/classification/synonyms/{referenceType}/{referenceID}/{taxonID}', name: "services_rest_classification_synonyms", methods: ['GET'])]
    public function synonyms(string $referenceType, int $referenceID, int $taxonID, #[MapQueryParameter] ?int $insertSeries = 0): Response
    {
        $data = $this->classificationFacade->resolveSynonyms($referenceType, $referenceID, $taxonID, $insertSeries);
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/classification/parent/{referenceType}/{referenceID}/{taxonID}',
        summary: 'Get the parent entry of a given reference',
        tags: ['classification'],
        parameters: [
            new PathParameter(
                name: 'referenceType',
                description: 'Type of reference (citation, person, service, specimen, periodical)',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string'),
                example: 'citation'
            ),
            new PathParameter(
                name: 'referenceID',
                description: 'ID of reference',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 31070
            ),
            new PathParameter(
                name: 'taxonID',
                description: 'ID of taxon name',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 46183
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'parent entry of a given reference',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        type: 'array',
                        items: new Items(
                            properties: [
                                new Property(property: 'taxonID', description: 'the taxon-ID we asked for', type: 'integer', example: 15),
                                new Property(property: 'uuid', description: 'URL to UUID service', type: 'object', example: '{"href": "url to get the uuid"}'),
                                new Property(property: 'referenceId', description: 'ID of reference', type: 'integer', example: 15),
                                new Property(property: 'referenceName', description: 'name of the reference', type: 'string', example: ''),
                                new Property(property: 'referenceType', description: 'Type of the reference', type: 'string', example: ''),
                                new Property(property: 'hasType', description: ' true if Type exist', type: 'boolean', example: false),
                                new Property(property: 'hasSpecimen', description: 'true if at least one specimen exists', type: 'boolean', example: false),
                                new Property(property: 'referenceInfo', description: '', type: 'object', example: '{"number": "","order": ""}')
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
                                    new Property(property: 'taxonID', description: 'the taxon-ID we asked for', type: 'integer', example: 15),
                                    new Property(property: 'uuid', description: 'URL to UUID service', type: 'object', example: '{"href": "url to get the uuid"}'),
                                    new Property(property: 'referenceId', description: 'ID of reference', type: 'integer', example: 15),
                                    new Property(property: 'referenceName', description: 'name of the reference', type: 'string', example: ''),
                                    new Property(property: 'referenceType', description: 'Type of the reference', type: 'string', example: ''),
                                    new Property(property: 'hasType', description: ' true if Type exist', type: 'boolean', example: false),
                                    new Property(property: 'hasSpecimen', description: 'true if at least one specimen exists', type: 'boolean', example: false),
                                    new Property(property: 'referenceInfo', description: '', type: 'object', example: '{"number": "","order": ""}')
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
    #[Route('/services/rest/classification/parent/{referenceType}/{referenceID}/{taxonID}', name: "services_rest_classification_parent", methods: ['GET'])]
    public function parent(string $referenceType, int $referenceID, int $taxonID): Response
    {
        $data = $this->classificationFacade->resolveParent($referenceType, $referenceID, $taxonID);
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/classification/numberOfChildrenWithChildrenCitation/{referenceID}',
        summary: 'Get number of classification children who have children themselves of a given taxonID according to a given reference of type citation',
        tags: ['classification'],
        parameters: [
            new PathParameter(
                name: 'referenceID',
                description: 'ID of reference',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 31070
            ),
            new QueryParameter(
                name: 'taxonID',
                description: 'optional ID of taxon name',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer'),
                example: 233658
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'number of children',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        type: 'integer',
                        example: 2
                    )
                ),
                    new MediaType(
                        mediaType: 'application/xml',
                        schema: new Schema(
                            type: 'integer',
                            example: 2
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
    #[Route('/services/rest/classification/numberOfChildrenWithChildrenCitation/{referenceID}', name: "services_rest_classification_numberOfChildrenWithChildrenCitation", methods: ['GET'])]
    public function numberOfChildrenWithChildrenCitation(int $referenceID, #[MapQueryParameter] ?int $taxonID = 0): Response
    {
        $data = $this->classificationFacade->resolveNumberOfChildrenWithChildrenCitation($referenceID, $taxonID);
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/classification/periodicalStatistics/{referenceID}',
        summary: 'Get statistics information of a given reference',
        tags: ['classification'],
        parameters: [
            new PathParameter(
                name: 'referenceID',
                description: 'ID of reference',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 31070
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'number of children',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(type: 'array',
                        items: new Items(
                            properties: [
                                new Property(property: "nrAccTaxa", type: "integer", example: 492),
                                new Property(property: "nrSynonyms", type: "integer", example: 34),
                                new Property(
                                    property: "ranks",
                                    type: "array",
                                    items: new Items(
                                        properties: [
                                            new Property(property: "rank", type: "string", example: "divisions"),
                                            new Property(property: "nrAccTaxa", type: "integer", example: 1),
                                            new Property(property: "nrSynTaxa", type: "integer", example: 0),
                                        ]
                                    )
                                )
                            ]),
                    )
                ),
                    new MediaType(
                        mediaType: 'application/xml',
                        schema: new Schema(type: 'array',
                            items: new Items(
                                properties: [
                                    new Property(property: "nrAccTaxa", type: "integer", example: 492),
                                    new Property(property: "nrSynonyms", type: "integer", example: 34),
                                    new Property(
                                        property: "ranks",
                                        type: "array",
                                        items: new Items(
                                            properties: [
                                                new Property(property: "rank", type: "string", example: "divisions"),
                                                new Property(property: "nrAccTaxa", type: "integer", example: 1),
                                                new Property(property: "nrSynTaxa", type: "integer", example: 0),
                                            ]
                                        )
                                    )
                                ]),
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
    #[Route('/services/rest/classification/periodicalStatistics/{referenceID}', name: "services_rest_classification_periodicalStatistics", methods: ['GET'])]
    public function periodicalStatistics(int $referenceID): Response
    {
        $data = $this->classificationFacade->resolvePeriodicalStatistics($referenceID);
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/classification/download/{referenceType}/{referenceID}',
        summary: 'Get an array, filled with header and data for download',
        tags: ['classification'],
        parameters: [
            new PathParameter(
                name: 'referenceType',
                description: 'Type of reference (citation, person, service, specimen, periodical)',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string'),
                example: 'citation'
            ),
            new PathParameter(
                name: 'referenceID',
                description: 'ID of reference',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 31070
            ),
            new QueryParameter(
                name: 'scientificNameId',
                description: 'optional ID of scientific name',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer'),
                example: 46183
            ),
            new QueryParameter(
                name: 'hideScientificNameAuthors',
                description: 'hide authors name in scientific name (optional, default = use database',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer'),
                example: 1
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'parent entry of a given reference',
                content: [new MediaType(
                    mediaType: 'application/json',
                ),
                    new MediaType(
                        mediaType: 'application/xml',
                    )
                ]
            ),
            new \OpenApi\Attributes\Response(
                response: 400,
                description: 'Bad Request'
            )
        ]
    )]
    #[Route('/services/rest/classification/download/{referenceType}/{referenceID}', name: "services_rest_classification_download", methods: ['GET'])]
    public function download(string $referenceType, int $referenceID,#[MapQueryParameter] ?int $scientificNameId, #[MapQueryParameter] ?int $hideScientificNameAuthors ): Response
    {
        $data = $this->classificationDownloadFacade->getDownload($referenceType, $referenceID,$scientificNameId,$hideScientificNameAuthors);
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }

}
