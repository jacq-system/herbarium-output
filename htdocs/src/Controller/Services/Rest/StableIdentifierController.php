<?php declare(strict_types=1);

namespace App\Controller\Services\Rest;

use App\Service\SpecimenService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Response;
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
                                    new Property(property: 'stableIdentifier',description: 'Stable identifier',type: 'string'),
                                    new Property(property: 'timestamp',description: 'Timestamp of the stable identifier',type: 'string',format: 'date-time'),
                                    new Property(property: 'link',description: 'Link to details page of JACQ (for convenience)',type: 'string',format: 'uri'),
                                ],
                                    type: 'object'
                                ),
                                new Property(
                                    property: 'stableIdentifierList',description: 'List of all found stable identifiers, ordered by timestamp',type: 'array',
                                    items: new Items(
                                        properties: [
                                            new Property(property: 'stableIdentifier',description: 'Stable identifier',type: 'string'),
                                            new Property(property: 'timestamp',description: 'Timestamp of the stable identifier',type: 'string',format: 'date-time'),
                                        ],
                                        type: 'object'
                                    )
                                ),
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
                                    new Property(property: 'specimenID', description: 'ID of specimen', type: 'integer', example: 1385945
                                    ),
                                    new Property(property: 'stableIdentifierLatest', description: 'Latest stable identifier', properties: [
                                        new Property(property: 'stableIdentifier',description: 'Stable identifier',type: 'string'),
                                        new Property(property: 'timestamp',description: 'Timestamp of the stable identifier',type: 'string',format: 'date-time'),
                                        new Property(property: 'link',description: 'Link to details page of JACQ (for convenience)',type: 'string',format: 'uri'),
                                    ],
                                        type: 'object'
                                    ),
                                    new Property(
                                        property: 'stableIdentifierList',description: 'List of all found stable identifiers, ordered by timestamp',type: 'array',
                                        items: new Items(
                                            properties: [
                                                new Property(property: 'stableIdentifier',description: 'Stable identifier',type: 'string'),
                                                new Property(property: 'timestamp',description: 'Timestamp of the stable identifier',type: 'string',format: 'date-time'),
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
    #[Route('/services/rest/stableIdentifier/sid/{specimenID}.{_format}', defaults: ['_format' => 'json'], methods: ['GET'])]
    public function sid(int $specimenID): Response
    {
        $sids = $this->specimenService->getAllStableIdentifiers($specimenID);
        if (!empty($sids)) {
            $results = array('specimenID' => $specimenID,
                'stableIdentifierLatest' => $sids['latest'],
                'stableIdentifierList' => $sids['list']);
        } else {
            $results = [];
        }
        $view = $this->view($results, 200);

        return $this->handleView($view);
    }

}
