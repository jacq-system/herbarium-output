<?php declare(strict_types=1);

namespace App\Controller\Front;

use App\Facade\SearchFormFacade;
use App\Service\CollectionService;
use App\Service\ExcelService;
use App\Service\ImageService;
use App\Service\InstitutionService;
use App\Service\SearchFormSessionService;
use App\Service\SpecimenService;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class SearchFormController extends AbstractController
{

    public const array RECORDS_PER_PAGE = array(10, 30, 50, 100);

    //TODO the name of taxon is not part of the query now, hard to sort
    public const array SORT = ["taxon" => '', 'collector' => 's.collector'];

    public function __construct(protected readonly CollectionService $collectionService, protected readonly InstitutionService $herbariumService, protected readonly SearchFormFacade $searchFormFacade, protected readonly SearchFormSessionService $sessionService, protected readonly ImageService $imageService, protected readonly SpecimenService $specimenService, protected readonly ExcelService $excelService)
    {
    }

    #[Route('/database', name: 'app_front_database')]
    public function database(Request $request, #[MapQueryParameter] bool $reset = false): Response
    {
        if ($reset) {
            $this->sessionService->reset();
            return $this->redirectToRoute('app_front_database');
        }
        $getData = $request->query->all();
        if (!empty($getData)) {
            $this->sessionService->setFilters($getData);
        }

        $institutions = $this->herbariumService->getAllPairsCodeName();
        $collections = $this->collectionService->getAllAsPairs();
        return $this->render('front/home/database.html.twig', ["institutions" => $institutions, 'collections' => $collections, 'sessionService' => $this->sessionService]);
    }

    #[Route('/databaseSearch', name: 'app_front_databaseSearch', methods: ['POST'])]
    public function databaseSearch(Request $request): Response
    {
        $postData = $request->request->all();
        $this->sessionService->setFilters($postData)->
            setSetting('page', 1);

        $pagination = $this->searchFormFacade->providePaginationInfo();

        return $this->render('front/home/databaseSearch.html.twig', [
            'records' => $this->searchFormFacade->search(),
            'recordsCount' => $pagination["totalRecords"],
            'totalPages' => $pagination['totalPages'],
            'pages' => $pagination['pages'],
            'recordsPerPage' => self::RECORDS_PER_PAGE,
            'sessionService' => $this->sessionService]);
    }

    #[Route('/databaseSearchSettings', name: 'app_front_databaseSearchSettings', methods: ['GET'])]
    public function databaseSearchSettings(#[MapQueryParameter] string $feature, #[MapQueryParameter] string $value): Response
    {
        switch ($feature) {
            case "page":
                $this->sessionService->setSetting('page', $value);
                break;
            case "recordsPerPage":
                $this->sessionService->setSetting('recordsPerPage', $value);
                break;
            case "sort":
                $this->sessionService->setSetting('sort', $value);
                break;
            default:
                break;
        }
        return new Response();
    }

    #[Route('/collectionsSelectOptions', name: 'app_front_collectionsSelectOptions', methods: ['GET'])]
    public function collectionsSelectOptions(#[MapQueryParameter] string $herbariumID): Response
    {
        $result = $this->collectionService->getAllFromHerbariumAsPairsByAbbrev($herbariumID);

        return new JsonResponse($result);
    }

    #[Route('/image', name: 'app_front_image_endpoint', methods: ['GET'])]
    public function showImage(#[MapQueryParameter] string $filename, #[MapQueryParameter] ?string $sid, #[MapQueryParameter] string $method, #[MapQueryParameter] ?string $format): Response
    {
        if ($_SERVER['REMOTE_ADDR'] == '94.177.9.139' && !empty($sid) && $method == 'download' && strrpos($filename, '_') == strpos($filename, '_')) {
            // kulturpool is calling...
            // Redirect to new location
            $this->redirectToRoute("services_rest_images_europeana", ["specimenID" => $sid], 303);
        }

        //TODO only due Djatoka in the getPicDetails() is needed to have this format, otherwise could be deriobed from the streamed response headers, see bellow
        switch ($format) {
            case 'jpeg2000':
                $contentType = 'image/jp2';
                break;
            case'tiff':
                $contentType = 'image/tiff';
                break;
            default:
                $contentType = 'image/jpeg';
                break;
        }

        $picDetails = $this->imageService->getPicDetails($filename, $contentType, $sid);

        if (!empty($picDetails['url'])) {
            switch ($method) {
                default:
                    $url = $this->imageService->getSourceUrl($picDetails, $contentType, 0);
                    break;
                case 'download':    // detail
                       $url = $this->imageService->getSourceUrl($picDetails, $contentType, 0);
                    break;
                case 'thumb':       // detail
                    $url = $this->imageService->getSourceUrl($picDetails, $contentType, 1);
                    break;
                case 'resized':     // create_xml.php
                    $url = $this->imageService->getSourceUrl($picDetails, $contentType, 2);
                    break;
                case 'europeana':   // NOTE: not supported on non-djatoka servers (yet)
                    if (strtolower(substr($picDetails['requestFileName'], 0, 3)) == 'wu_' && $this->imageService->checkPhaidra((int)$picDetails['specimenID'])) {
                        // Phaidra (only WU)
                        $picDetails['imgserver_type'] = 'phaidra';
                    } else {
                        // Djatoka
                        $picinfo = $this->imageService->getPicInfo($picDetails);
                        if (!empty($picinfo['pics'][0]) && !in_array($picDetails['originalFilename'], $picinfo['pics'])) {
                            $picDetails['originalFilename'] = $picinfo['pics'][0];
                        }
                    }
                    $url = $this->imageService->getSourceUrl($picDetails, $contentType, 3);
                    break;
                case 'nhmwthumb':   // NOTE: not supported on legacy image server scripts
                    $url = $this->imageService->getSourceUrl($picDetails, $contentType, 4);
                    break;
                case 'thumbs':      // unused
                    return $this->json($this->imageService->getPicInfo($picDetails));
                case 'show':        // detail, ajax/results.php
                    $url = $this->imageService->getExternalImageViewerUrl($picDetails);
                    return new RedirectResponse($url, 303, ['X-Content-Type-Options' => 'nosniff']);
            }

            $streamContext = stream_context_create([
                'http' => ['follow_location' => true,
                    'timeout' => 60],
                'ssl' => ["verify_peer" => false,
                    "verify_peer_name" => false]
            ]);
            $imageStream = @fopen($url, 'rb', false, $streamContext);

            $headers = get_headers($url, true);

//                    $contentType = $headers['Content-Type'] ?? 'image/jpeg'; // Fallback na JPEG
            $contentLength = $headers['Content-Length'] ?? null;
            $response = new StreamedResponse(function () use ($imageStream) {
                fpassthru($imageStream);
                fclose($imageStream);
            });

            $response->headers->set('Content-Type', $contentType);
            if ($contentLength) {
                $response->headers->set('Content-Length', $contentLength);
            }

            return $response;

        } else {
            switch ($method) {
                case 'download':
                case 'thumb':
                    $filePath = $this->getParameter('kernel.project_dir') . '/public/recordIcons/404.png';

                    if (!file_exists($filePath) || mime_content_type($filePath) !== 'image/png') {
                        throw $this->createNotFoundException('Sorry, this image does not exist.');
                    }

                    return new Response(file_get_contents($filePath), 200, [
                        'Content-Type' => 'image/png',
                        'Content-Length' => filesize($filePath),
                    ]);
                case 'thumbs':
                    return new JsonResponse(['error' => 'not found'], 404);
                default:
                    return new Response('not found', 404);

            }
        }
    }

    #[Route('/detail/{specimenId}', name: 'app_front_specimenDetail', methods: ['GET'])]
    public function detail(int $specimenId): Response
    {
        return $this->render('front/home/detail.html.twig', ['specimen' => $this->specimenService->findAccessibleForPublic($specimenId)]);
    }

    #[Route('/exportKml', name: 'app_front_exportKml', methods: ['GET'])]
    public function exportKml(): Response
    {
        $kmlContent = $this->searchFormFacade->getKmlExport();
        $response = new Response($kmlContent);
        $response->headers->set('Content-Type', 'application/vnd.google-earth.kml+xml');
        $response->headers->set('Content-Disposition', 'attachment; filename="specimens_download.kml"');

        return $response;
    }

    #[Route('/exportExcel', name: 'app_front_exportExcel', methods: ['GET'])]
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

    #[Route('/exportCsv', name: 'app_front_exportCsv', methods: ['GET'])]
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

    #[Route('/exportOds', name: 'app_front_exportOds', methods: ['GET'])]
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

}
