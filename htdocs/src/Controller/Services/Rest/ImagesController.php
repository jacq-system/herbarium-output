<?php declare(strict_types=1);

namespace App\Controller\Services\Rest;

use App\Facade\Rest\IiifFacade;
use App\Service\IiifService;
use App\Service\ImageLinkMapper;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImagesController extends AbstractFOSRestController
{
    public function __construct(protected readonly ImageLinkMapper $imageLinkMapper)
    {
    }

    #[Get(
        path: '/services/rest/images/show/{specimenID}/{imageNr}',
        summary: 'et the uri to show the first image of a given specimen-ID',
        tags: ['images'],
        parameters: [
            new PathParameter(
                name: 'specimenID',
                description: 'ID of specimen',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 4354
            ),
            new PathParameter(
                name: 'imageNr',
                description: 'image number (defaults to 0=first image)',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 1
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'List of taxa names',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        properties: [
                            new Property(property: 'imagelink')
                        ]
                    )
                ),
                    new MediaType(
                        mediaType: 'application/xml',
                        schema: new Schema(
                            properties: [
                                new Property(property: 'imagelink')
                            ]
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
    #[Route('/services/rest/images/show/{specimenID}/{imageNr}.{_format}', name: "services_rest_images_show", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function show(int $specimenID, int $imageNr = 0): Response
    {
        //todo ignoring "withredirect" param
        $this->imageLinkMapper->setSpecimenId($specimenID);
        $results = $this->imageLinkMapper->getShowLink($imageNr);

        $view = $this->view($results, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/images/download/{specimenID}/{imageNr}',
        summary: 'et the uri to show the first image of a given specimen-ID',
        tags: ['images'],
        parameters: [
            new PathParameter(
                name: 'specimenID',
                description: 'ID of specimen',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 4354
            ),
            new PathParameter(
                name: 'imageNr',
                description: 'image number (defaults to 0=first image)',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 1
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'links to download',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        properties: [
                            new Property(property: 'imagelink')
                        ]
                    )
                ),
                    new MediaType(
                        mediaType: 'application/xml',
                        schema: new Schema(
                            properties: [
                                new Property(property: 'imagelink')
                            ]
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
    #[Route('/services/rest/images/download/{specimenID}/{imageNr}.{_format}', name: "services_rest_images_download", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function download(int $specimenID, int $imageNr = 0): Response
    {
        $this->imageLinkMapper->setSpecimenId($specimenID);
        $results = $this->imageLinkMapper->getDownloadLink($imageNr);

        $view = $this->view($results, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/images/europeana/{specimenID}/{imageNr}',
        summary: 'get the uri to download the first image of a given specimen-ID with resolution 1200',
        tags: ['images'],
        parameters: [
            new PathParameter(
                name: 'specimenID',
                description: 'ID of specimen',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 4354
            ),
            new PathParameter(
                name: 'imageNr',
                description: 'image number (defaults to 0=first image)',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 1
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'links',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        properties: [
                            new Property(property: 'imagelink')
                        ]
                    )
                ),
                    new MediaType(
                        mediaType: 'application/xml',
                        schema: new Schema(
                            properties: [
                                new Property(property: 'imagelink')
                            ]
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
    #[Route('/services/rest/images/europeana/{specimenID}/{imageNr}.{_format}', name: "services_rest_images_europeana", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function europeana(int $specimenID, int $imageNr = 0): Response
    {
        $this->imageLinkMapper->setSpecimenId($specimenID);
        $results = $this->imageLinkMapper->getEuropeanaLink($imageNr);

        $view = $this->view($results, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/images/thumb/{specimenID}/{imageNr}',
        summary: 'get the uri to download the first image of a given specimen-ID with resolution 160',
        tags: ['images'],
        parameters: [
            new PathParameter(
                name: 'specimenID',
                description: 'ID of specimen',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 4354
            ),
            new PathParameter(
                name: 'imageNr',
                description: 'image number (defaults to 0=first image)',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 1
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'links',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        properties: [
                            new Property(property: 'imagelink')
                        ]
                    )
                ),
                    new MediaType(
                        mediaType: 'application/xml',
                        schema: new Schema(
                            properties: [
                                new Property(property: 'imagelink')
                            ]
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
    #[Route('/services/rest/images/thumb/{specimenID}/{imageNr}.{_format}', name: "services_rest_images_thumb", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function thumb(int $specimenID, int $imageNr = 0): Response
    {
        $this->imageLinkMapper->setSpecimenId($specimenID);
        $results = $this->imageLinkMapper->getThumbLink($imageNr);

        $view = $this->view($results, 200);

        return $this->handleView($view);
    }

    #[Get(
        path: '/services/rest/images/list/{specimenID}',
        summary: 'get a list of all image-uris of a given specimen-ID',
        tags: ['images'],
        parameters: [
            new PathParameter(
                name: 'specimenID',
                description: 'ID of specimen',
                in: 'path',
                required: true,
                schema: new Schema(type: 'integer'),
                example: 4354
            )
        ],
        responses: [
            new \OpenApi\Attributes\Response(
                response: 200,
                description: 'links',
                content: [new MediaType(
                    mediaType: 'application/json',
                    schema: new Schema(
                        properties: [
                            new Property(property: 'imagelink')
                        ]
                    )
                ),
                    new MediaType(
                        mediaType: 'application/xml',
                        schema: new Schema(
                            properties: [
                                new Property(property: 'imagelink')
                            ]
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
    #[Route('/services/rest/images/list/{specimenID}.{_format}', name: "services_rest_images_list", defaults: ['_format' => 'json'], methods: ['GET'])]
    public function list(int $specimenID, int $imageNr = 0): Response
    {
        $this->imageLinkMapper->setSpecimenId($specimenID);
        $results = $this->imageLinkMapper->getList($imageNr);

        $view = $this->view($results, 200);

        return $this->handleView($view);
    }
}
