<?php declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityNotFoundException;
use JACQ\Application\Specimen\Export\ExcelService;
use JACQ\Application\Specimen\Export\GeojsonService;
use JACQ\Application\Specimen\Export\KmlService;
use JACQ\Application\Specimen\Search\SpecimenBatchProvider;
use JACQ\Application\Specimen\Search\SpecimenSearchParameters;
use JACQ\Application\Specimen\Search\SpecimenSearchQueryFactory;
use JACQ\Repository\Herbarinput\HerbCollectionRepository;
use JACQ\Repository\Herbarinput\InstitutionRepository;
use JACQ\Service\SpecimenService;
use JACQ\UI\Http\SearchFormSessionService;
use JACQ\UI\Http\SpecimenSearchParametersFromSessionFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SearchFormController extends AbstractController
{

    public const array RECORDS_PER_PAGE = array(10, 30, 50, 100);
    public const int PAGINATION_RANGE = 3;

    public function __construct(protected readonly HerbCollectionRepository $herbCollectionRepository, protected readonly InstitutionRepository $institutionRepository, protected readonly SearchFormSessionService $sessionService, protected readonly SpecimenService $specimenService, protected LoggerInterface $statisticsLogger, protected LoggerInterface $appLogger, private CacheInterface $cache, protected SpecimenSearchParametersFromSessionFactory $fromSessionFactory, protected SpecimenSearchQueryFactory $searchQueryFactory, protected SpecimenBatchProvider $specimenBatchProvider, protected KmlService $kmlService, protected ExcelService $excelService, protected GeojsonService $geojsonService)
    {
    }

    #[Route('/database', name: 'output_database')]
    public function database(Request $request, #[MapQueryParameter] bool $reset = false): Response
    {
        $this->sessionService->setSetting('page', 1);
        if ($reset) {
            $this->sessionService->reset();
            return $this->redirectToRoute('output_database');
        }
        $getData = $request->query->all();
        if (!empty($getData)) {
            $this->sessionService->setFilters($getData);
        }

        $institutions = $this->cache->get('institutions_pairs_code_name', function (ItemInterface $item) {
            $item->expiresAfter(36000);
            return $this->institutionRepository->getAllPairsCodeName();
        });

        if (empty($this->sessionService->getFilter('institution'))) {
            $collections = $this->cache->get('herb_collections_pairs', function (ItemInterface $item) {
                $item->expiresAfter(36000);
                return $this->herbCollectionRepository->getAllAsPairs();
            });
        } else {
            $collections = $this->herbCollectionRepository->getAllAsPairs((int)$this->sessionService->getFilter('institution'));
        }


        return $this->render('output/searchForm/database.html.twig', ["institutions" => $institutions, 'collections' => $collections, 'sessionService' => $this->sessionService]);
    }

    protected function getOffset()
    {
        $page = (int)$this->sessionService->getSetting('page', 1);
        return ($page - 1) * (int)$this->sessionService->getSetting('recordsPerPage', self::RECORDS_PER_PAGE[0]);
    }

    #[Route('/databaseSearch', name: 'output_databaseSearch', methods: ['POST'])]
    public function databaseSearch(Request $request): Response
    {
        //set up search criteria
        $postData = $request->request->all();
        $allEmpty = true;
        foreach ($postData as $value) {
            if ($value !== null && $value !== '') {
                $allEmpty = false;
                break;
            }
        }
        if ($allEmpty) {
            return $this->render('output/searchForm/databaseSearchEmpty.html.twig');
        }
        $this->sessionService->setFilters($postData);

        if ($this->sessionService->getSort() === null) {
            $this->sessionService->setSort('sciname');
        }

        $parameters = $this->fromSessionFactory->create();
        $specimenSearchQuery = $this->searchQueryFactory->createForPublic();
        $queryBuilder = $specimenSearchQuery->build($parameters);

        $pagination = $this->providePaginationInfo($parameters);

        return $this->render('output/searchForm/databaseSearch.html.twig', [
            'records' => $this->specimenBatchProvider->iterate($queryBuilder, $this->getOffset(), (int)$this->sessionService->getSetting('recordsPerPage', self::RECORDS_PER_PAGE[0])),
            'recordsCount' => $pagination["totalRecords"],
            'totalPages' => $pagination['totalPages'],
            'pages' => $pagination['pages'],
            'recordsPerPage' => self::RECORDS_PER_PAGE,
            'sessionService' => $this->sessionService]);
    }

    protected function providePaginationInfo(SpecimenSearchParameters $parameters): array
    {
        $specimenSearchQuery = $this->searchQueryFactory->createForPublic();
        $totalRecordCount = $specimenSearchQuery->countResults($parameters);
        $recordsPerPage = (int)$this->sessionService->getSetting('recordsPerPage', self::RECORDS_PER_PAGE[0]);
        $currentPage = (int)$this->sessionService->getSetting('page', 1);

        $totalPages = ceil($totalRecordCount / $recordsPerPage);

        if ($currentPage > $totalPages) {
            $currentPage = 1;
            $this->sessionService->setSetting('page', 1);
        }

        $pages[] = 1;
        if ($currentPage > 1) {

            if ($currentPage > self::PAGINATION_RANGE + 2) {
                $pages[] = '...';
            }
        }

        $start = max(2, $currentPage - self::PAGINATION_RANGE);
        $end = min($totalPages - 1, $currentPage + self::PAGINATION_RANGE);

        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        if ($currentPage < $totalPages) {
            if ($currentPage < $totalPages - self::PAGINATION_RANGE - 1) {
                $pages[] = '...';
            }
        }
        if ($totalPages > 1) {
            $pages[] = $totalPages;
        }
        return ["totalRecords" => $totalRecordCount, "totalPages" => $totalPages, "pages" => $pages];

    }

    #[Route('/databaseSearchSettings', name: 'output_databaseSearchSettings', methods: ['GET'])]
    public function databaseSearchSettings(#[MapQueryParameter] string $feature, #[MapQueryParameter] string $value): Response
    {
        switch ($feature) {
            case "page":
                $this->sessionService->setSetting('page', $value);
                break;
            case "recordsPerPage":
                $this->sessionService->setSetting('recordsPerPage', $value);
                $this->sessionService->setSetting('page', 1);
                break;
            case "sort":
                $this->sessionService->setSort($value);
                break;
            default:
                break;
        }
        return new Response();
    }

    #[Route('/collectionsSelectOptions', name: 'output_collectionsSelectOptions', methods: ['GET'])]
    public function collectionsSelectOptions(#[MapQueryParameter] ?int $herbariumID): Response
    {
        $result = $this->herbCollectionRepository->getAllAsObjectPairs($herbariumID);

        return new JsonResponse($result);
    }

    #[Route('/detail/{specimenId}', name: 'output_specimenDetail', requirements: ['specimenId' => '\d+'], methods: ['GET'])]
    public function detail(int $specimenId): Response
    {
        try {
            $specimen = $this->specimenService->findAccessibleForPublic($specimenId);
        } catch (EntityNotFoundException) {
            try {
                $specimen = $this->specimenService->findNonAccessibleForPublic($specimenId);
            } catch (EntityNotFoundException) {
                return $this->render('output/searchForm/detail_404.html.twig', [], new Response(status: Response::HTTP_NOT_FOUND));
            }

            return $this->render('output/searchForm/detail_mids0.html.twig', [
                'specimen' => $specimen,
                'pid' => $this->specimenService->getStableIdentifier($specimen),
            ]);
        }
        $this->statisticsLogger->info('Specimen [{id},{institution}] detail shown.', [
            'id' => $specimen->id,
            'institution' => $specimen->herbCollection->institution->abbreviation
        ]);
        return $this->render('output/searchForm/detail.html.twig', [
            'specimen' => $specimen,
            'pid' => $this->specimenService->getStableIdentifier($specimen)
        ]);
    }

    #[Route('/exportKml', name: 'output_exportKml', methods: ['GET'])]
    public function exportKml(): Response
    {
        $parameters = $this->fromSessionFactory->create();
        $specimenSearchQuery = $this->searchQueryFactory->createForPublic();
        $queryBuilder = $specimenSearchQuery->build($parameters);

        return new StreamedResponse(function () use ($queryBuilder) {
            try {
                foreach ($this->kmlService->KmlRecords($queryBuilder, $this->getOffset()) as $chunk) {
                    echo $chunk;
                }
            } catch (\Throwable $e) {
                error_log($e->getMessage());
                throw $e;
            }
        },
            200,
            [
                'Content-Type' => 'application/vnd.google-earth.kml+xml',
                'Content-Disposition' => 'attachment; filename="specimens_download.kml"',
            ]
        );
    }

    #[Route('/exportGeoJson', name: 'output_exportGeoJson', methods: ['GET'])]
    public function exportGeoJson(): Response
    {
        $parameters = $this->fromSessionFactory->create();
        $specimenSearchQuery = $this->searchQueryFactory->createForPublic();
        $queryBuilder = $specimenSearchQuery->build($parameters)
            ->resetDQLPart('orderBy')
            ->orderBy('specimen.id');

        return new StreamedResponse(function () use ($queryBuilder) {
            try {
                foreach ($this->geojsonService->GeojsonRecords($queryBuilder, $this->getOffset(), 1000 * 1000) as $chunk) {
                    echo $chunk;
                }
            } catch (\Throwable $e) {
                error_log($e->getMessage());
                throw $e;
            }
        },
            200,
            [
                'Content-Type' => 'application/json',
            ]
        );
    }

    #[Route('/exportExcel', name: 'output_exportExcel', methods: ['GET'])]
    public function exportExcel(): Response
    {
        $parameters = $this->fromSessionFactory->create();
        $specimenSearchQuery = $this->searchQueryFactory->createForPublic();
        $queryBuilder = $specimenSearchQuery->build($parameters);
        $spreadsheet = $this->excelService->createSpecimenExport($queryBuilder, $this->getOffset());

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="specimens_download.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    #[Route('/exportCsv', name: 'output_exportCsv', methods: ['GET'])]
    public function exportCsv(): Response
    {
        $parameters = $this->fromSessionFactory->create();
        $specimenSearchQuery = $this->searchQueryFactory->createForPublic();
        $queryBuilder = $specimenSearchQuery->build($parameters);
        $spreadsheet = $this->excelService->createSpecimenExport($queryBuilder, $this->getOffset());

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Csv($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment;filename="specimens_download.csv"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    #[Route('/exportOds', name: 'output_exportOds', methods: ['GET'])]
    public function exportOds(): Response
    {
        $parameters = $this->fromSessionFactory->create();
        $specimenSearchQuery = $this->searchQueryFactory->createForPublic();
        $queryBuilder = $specimenSearchQuery->build($parameters);
        $spreadsheet = $this->excelService->createSpecimenExport($queryBuilder, $this->getOffset());

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Ods($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="specimens_download.ods"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    #[Route('/specimenLinks/{specimenId}', name: 'output_specimenLinksD3', requirements: ['specimenId' => '\d+'], methods: ['GET'])]
    public function specimenLinks(int $specimenId): Response
    {
        $startSpecimen = $this->specimenService->findAccessibleForPublic($specimenId);
        $specimens = $this->specimenService->collectSpecimenLinksTree($startSpecimen);
        $d3Data = $this->specimenService->buildD3GraphData($specimens, $startSpecimen);

        return $this->json($d3Data);
    }

}
