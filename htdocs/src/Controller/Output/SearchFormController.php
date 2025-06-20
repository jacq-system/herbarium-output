<?php declare(strict_types=1);

namespace App\Controller\Output;

use App\Facade\SearchFormFacade;
use JACQ\Repository\Herbarinput\HerbCollectionRepository;
use JACQ\Repository\Herbarinput\InstitutionRepository;
use App\Service\Output\ExcelService;
use App\Service\Output\SearchFormSessionService;
use JACQ\Service\SpecimenService;
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

    public function __construct(protected readonly HerbCollectionRepository $herbCollectionRepository, protected readonly InstitutionRepository $institutionRepository, protected readonly SearchFormFacade $searchFormFacade, protected readonly SearchFormSessionService $sessionService, protected readonly SpecimenService $specimenService, protected readonly ExcelService $excelService, protected LoggerInterface $statisticsLogger, protected LoggerInterface $appLogger, private CacheInterface $cache)
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
            $collections = $this->herbCollectionRepository->getAllAsPairs((int) $this->sessionService->getFilter('institution'));
        }


        return $this->render('output/searchForm/database.html.twig', ["institutions" => $institutions, 'collections' => $collections, 'sessionService' => $this->sessionService]);
    }

    #[Route('/databaseSearch', name: 'output_databaseSearch', methods: ['POST'])]
    public function databaseSearch(Request $request): Response
    {
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

        $pagination = $this->searchFormFacade->providePaginationInfo();

        return $this->render('output/searchForm/databaseSearch.html.twig', [
            'records' => $this->searchFormFacade->search(),
            'recordsCount' => $pagination["totalRecords"],
            'totalPages' => $pagination['totalPages'],
            'pages' => $pagination['pages'],
            'recordsPerPage' => self::RECORDS_PER_PAGE,
            'sessionService' => $this->sessionService]);
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
        $specimen = $this->specimenService->findAccessibleForPublic($specimenId);
        $this->statisticsLogger->info('Specimen [{id},{institution}] detail shown.', [
            'id' => $specimen->getId(),
            'institution' => $specimen->getHerbCollection()->getInstitution()->getAbbreviation()
        ]);
        return $this->render('output/searchForm/detail.html.twig', [
            'specimen' => $specimen,
            'pid' => $this->specimenService->getStableIdentifier($specimen)
        ]);
    }

    #[Route('/exportKml', name: 'output_exportKml', methods: ['GET'])]
    public function exportKml(): Response
    {
        $kmlContent = $this->searchFormFacade->getKmlExport();
        $response = new Response($kmlContent);
        $response->headers->set('Content-Type', 'application/vnd.google-earth.kml+xml');
        $response->headers->set('Content-Disposition', 'attachment; filename="specimens_download.kml"');

        return $response;
    }

    #[Route('/exportExcel', name: 'output_exportExcel', methods: ['GET'])]
    public function exportExcel(): Response
    {
        $spreadsheet = $this->excelService->prepareExcel();
        $filledSpreadsheet = $this->excelService->easyFillExcel($spreadsheet, ExcelService::HEADER, $this->searchFormFacade->getSpecimenDataforExport());

        $response = new StreamedResponse(function () use ($filledSpreadsheet) {
            $writer = new Xlsx($filledSpreadsheet);
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
        $spreadsheet = $this->excelService->prepareExcel();
        $filledSpreadsheet = $this->excelService->easyFillExcel($spreadsheet, ExcelService::HEADER, $this->searchFormFacade->getSpecimenDataforExport());

        $response = new StreamedResponse(function () use ($filledSpreadsheet) {
            $writer = new Csv($filledSpreadsheet);
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
        $spreadsheet = $this->excelService->prepareExcel();
        $filledSpreadsheet = $this->excelService->easyFillExcel($spreadsheet, ExcelService::HEADER, $this->searchFormFacade->getSpecimenDataforExport());

        $response = new StreamedResponse(function () use ($filledSpreadsheet) {
            $writer = new Ods($filledSpreadsheet);
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
