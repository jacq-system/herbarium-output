<?php declare(strict_types=1);

namespace App\Controller\Services\Rest;

use App\Service\Rest\CoordinateBoundaryService;
use App\Service\Rest\CoordinateConversionService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class GeoController extends AbstractFOSRestController
{
    public function __construct(protected readonly CoordinateConversionService $coordinateConversionService, protected readonly CoordinateBoundaryService $coordinateBoundaryService)
    {
    }

    #[Get(
        path: '/services/rest/geo/convert',
        summary: 'convert one system (e.g. Coordinates) into another (e.g. UTM), using WGS 84',
        tags: ['geo'],
        parameters: [
            new QueryParameter(
                name: 'lat',
                description: 'This is latitude',
                in: 'query',
                required: false,
                schema: new Schema(type: 'number', format: 'float'),
                example: 48.21
            ),
            new QueryParameter(
                name: 'lon',
                description: 'This is longitude',
                in: 'query',
                required: false,
                schema: new Schema(type: 'number', format: 'float'),
                example: 16.37
            ),
            new QueryParameter(
                name: 'utm',
                description: 'convert from UTM',
                in: 'query',
                required: false,
                schema: new Schema(type: 'string'),
                example: "33 N 601779 5340548"
            ),
            new QueryParameter(
                name: 'mgrs',
                description: 'convert from MGRS',
                in: 'query',
                required: false,
                schema: new Schema(type: 'string'),
                example: "33UXP0177940548"
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
                                new Property(property: 'zone', type: 'integer', example: 33),
                                new Property(property: 'hemisphere', type: 'string', example: "N"),
                                new Property(property: 'easting', type: 'integer', example: 601779),
                                new Property(property: 'northing', type: 'integer', example: 5340548),
                                new Property(property: 'string', type: 'string', example: "33U 601779 5340548"),
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
                                    new Property(property: 'zone', type: 'integer', example: 33),
                                    new Property(property: 'hemisphere', type: 'string', example: "N"),
                                    new Property(property: 'easting', type: 'integer', example: 601779),
                                    new Property(property: 'northing', type: 'integer', example: 5340548),
                                    new Property(property: 'string', type: 'string', example: "33U 601779 5340548"),
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
    #[Route('/services/rest/geo/convert', name: "services_rest_geo_convert", methods: ['GET'])]
    public function convert(#[MapQueryParameter] ?float $lat, #[MapQueryParameter] ?float $lon, #[MapQueryParameter] ?string $utm, #[MapQueryParameter] ?string $mgrs): Response
    {
        if (isset($lat) && isset($lon)) {   // from lat/lon
            $data = array('utm' => $this->coordinateConversionService->latlon2utm($lat, $lon));
        } elseif (isset($utm)) {                      // from UTM
            $data = array('latlon' => $this->coordinateConversionService->utm2latlon($utm));
        } elseif (isset($mgrs)) {                     // from MGRS
            $conv = $this->coordinateConversionService->mgrs2utm($mgrs);
            $data = array('utm' => $conv,
                'latlon' => $this->coordinateConversionService->utm2latlon($conv['string']));
        } else {
            $data = array('error' => "nothing to do");
        }
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/geo/checkBoundaries',
        summary: 'check if lat/lon coordinates are within boundaries of a given nation',
        tags: ['geo'],
        parameters: [
            new QueryParameter(
                name: 'lat',
                description: 'This is latitude',
                in: 'query',
                required: true,
                schema: new Schema(type: 'number', format: 'float'),
                example: 48.21
            ),
            new QueryParameter(
                name: 'lon',
                description: 'This is longitude',
                in: 'query',
                required: true,
                schema: new Schema(type: 'number', format: 'float'),
                example: 16.37
            ),
            new QueryParameter(
                name: 'nationID',
                description: 'nationID',
                in: 'query',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 70
            ),
            new QueryParameter(
                name: 'province',
                description: 'province ID',
                in: 'query',
                required: false,
                schema: new Schema(type: 'integer'),
                example: 622
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
                                    property: 'nation',
                                    properties: [
                                        new Property(property: 'nrBoundaries', type: 'integer', example: 1),
                                        new Property(property: 'inside', type: 'boolean', example: true),
                                    ],
                                    type: 'object'
                                )
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
                                    new Property(
                                        property: 'nation',
                                        properties: [
                                            new Property(property: 'nrBoundaries', type: 'integer', example: 1),
                                            new Property(property: 'inside', type: 'boolean', example: true),
                                        ],
                                        type: 'object'
                                    )
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
    #[Route('/services/rest/geo/checkBoundaries', name: "services_rest_geo_checkBoundaries", methods: ['GET'])]
    public function checkBoundaries(#[MapQueryParameter] float $lat, #[MapQueryParameter] float $lon, #[MapQueryParameter] int $nationID, #[MapQueryParameter] ?int $province): Response
    {
        $data = array();
        $data['nation'] = $this->coordinateBoundaryService->nationBoundaries($nationID, $lat, $lon);
        if (isset($provinceID)) {
            $data['province'] = $this->coordinateBoundaryService->provinceBoundaries($provinceID, $lat, $lon);
        }
        //TODO better to use http codes, left for backward compatibility
        $data['error'] = (empty($data)) ? "nothing to do" : '';
        $view = $this->view($data, 200);

        return $this->handleView($view);
    }
}
