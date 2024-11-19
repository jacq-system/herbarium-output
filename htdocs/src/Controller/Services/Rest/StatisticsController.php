<?php declare(strict_types=1);

namespace App\Controller\Services\Rest;

use App\Enum\CoreObjectsEnum;
use App\Enum\TimeIntervalEnum;
use App\Service\Rest\StatisticsService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatisticsController extends AbstractFOSRestController
{
    public function __construct(protected readonly StatisticsService $statisticsService)
    {
    }

    #[Get(
        path: '/services/rest/statistics/results/{periodStart}/{periodEnd}/{updated}/{type}/{interval}',
        summary: 'Get statistics result for given type, interval and period',
        tags: ['statistics'],
        parameters: [
            new PathParameter(
                name: 'periodStart',
                description: 'start of period (yyyy-mm-dd)',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string'),
                example: '2014-01-01'
            ),
            new PathParameter(
                name: 'periodEnd',
                description: 'end of period (yyyy-mm-dd)',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string'),
                example: '2014-02-01'
            ),
            new PathParameter(
                name: 'updated',
                description: 'new (0) or updated (1) types only',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 0
            ),
            new PathParameter(
                name: 'type',
                description: 'type of statistics analysis',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string', enum: ["names", "citations", "names_citations", "specimens", "type_specimens", "names_type_specimens", "types_name", "synonyms", "classifications"]),
                example: "specimens"
            ),
            new PathParameter(
                name: 'interval',
                description: 'resolution of statistics analysis',
                in: 'path',
                required: true,
                schema: new Schema(type: 'string', enum: ["day", "week", "month", "year"]),
                example: "week"
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
    #[Route('/services/rest/statistics/results/{periodStart}/{periodEnd}/{updated}/{type}/{interval}.{_format}', name: "services_rest_statistics_results", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function results(string $periodStart, string $periodEnd, int $updated, CoreObjectsEnum $type, TimeIntervalEnum $interval): Response
    {
        $data = $this->statisticsService->getResults($periodStart, $periodEnd, $updated, $type, $interval);
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }


}
