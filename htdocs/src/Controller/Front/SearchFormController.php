<?php declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\User;
use App\Enum\CoreObjectsEnum;
use App\Enum\TimeIntervalEnum;
use App\Facade\SearchFormFacade;
use App\Service\CollectionService;
use App\Service\DjatokaService;
use App\Service\ImageService;
use App\Service\InstitutionService;
use App\Service\Rest\DevelopersService;
use App\Service\Rest\StatisticsService;
use App\Service\SearchFormSessionService;
use App\Service\SpecimenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class SearchFormController extends AbstractController
{

    public const array RECORDS_PER_PAGE = array(10, 30, 50, 100);

    //TODO the name of taxon is not part of the query now, hard to sort
    public const array SORT = ["taxon"=> '', 'collector'=>'s.collector'];

    public function __construct( protected readonly CollectionService $collectionService, protected readonly InstitutionService $herbariumService, protected readonly SearchFormFacade $searchFormFacade, protected readonly SearchFormSessionService $sessionService, protected readonly ImageService $imageService, protected readonly SpecimenService $specimenService)
    {
    }

    #[Route('/database', name: 'app_front_database')]
    public function database(Request $request, #[MapQueryParameter] bool $reset = false): Response
    {
        if ($reset) {
            $this->sessionService->reset();
            return $this->redirectToRoute($request->get('_route'));
        }
        $getData = $request->query->all();
        if (!empty($getData)) {
            $this->sessionService->setFilters($getData);
        }

        $institutions = $this->herbariumService->getAllAsPairs();
        $collections = $this->collectionService->getAllAsPairs();
        return $this->render('front/home/database.html.twig', ["institutions" => $institutions, 'collections' => $collections, 'sessionService' => $this->sessionService]);
    }

    #[Route('/databaseSearch', name: 'app_front_databaseSearch', methods: ['POST'])]
    public function databaseSearch(Request $request): Response
    {
        $postData = $request->request->all();
        $this->sessionService->setFilters($postData);

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
    public function collectionsSelectOptions(#[MapQueryParameter] int $herbariumID): Response
    {
        $result = $this->collectionService->getAllFromHerbariumAsPairs($herbariumID);

        return new JsonResponse($result);
    }

    #[Route('/image', name: 'app_front_image_endpoint', methods: ['GET'])]
    public function showImage(#[MapQueryParameter] string $filename,#[MapQueryParameter] string $sid,#[MapQueryParameter] string $method,#[MapQueryParameter] string $format): Response
    {//todo split in more endpoints, redirect is done inside the function called!!
        if ($_SERVER['REMOTE_ADDR'] == '94.177.9.139' && !empty($sid) && $method == 'download' && strrpos($filename, '_') == strpos($filename, '_')) {
            // kulturpool is calling...
            // Redirect to new location
            $this->redirectToRoute("services_rest_images_europeana", ["specimenID"=>$sid], 303);
        }

        $picDetails = $this->imageService->getPicDetails($filename, $sid);

        if (!empty($picdetails['url'])) {
            switch ($method) {
                default:
                    $this->imageService->doRedirectDownloadPic($picdetails, $method, 0);
                    exit;
                case 'download':    // detail

                    return new StreamedResponse(function () use ($picdetails, $format) {
                        $this->imageService->doRedirectDownloadPic($picdetails, $format, 0);
                    });
                case 'thumb':       // detail
                    $this->imageService->doRedirectDownloadPic($picdetails, $format, 1);
                    exit;
                case 'resized':     // create_xml.php
                    $this->imageService->doRedirectDownloadPic($picdetails, $format, 2);
                    exit;
                case 'europeana':   // NOTE: not supported on non-djatoka servers (yet)
                    if (strtolower(substr($picdetails['requestFileName'], 0, 3)) == 'wu_' && $this->imageService->checkPhaidra((int)$picdetails['specimenID'])) {
                        // Phaidra (only WU)
                        $picdetails['imgserver_type'] = 'phaidra';
                    } else {
                        // Djatoka
                        $picinfo = $this->imageService->getPicInfo($picdetails);
                        if (!empty($picinfo['pics'][0]) && !in_array($picdetails['originalFilename'], $picinfo['pics']))  {
                            $picdetails['originalFilename'] = $picinfo['pics'][0];
                        }
                    }
                    $this->imageService->doRedirectDownloadPic($picdetails, $format, 3);
                    exit;
                case 'nhmwthumb':   // NOTE: not supported on legacy image server scripts
                    $this->imageService->doRedirectDownloadPic($picdetails, $format, 4);
                    exit;
                case 'thumbs':      // unused
                    return $this->json($this->imageService->getPicInfo($picdetails));
                case 'show':        // detail, ajax/results.php
                    return $this->redirect($this->imageService->doRedirectShowPic($picDetails));
            }

        } else {
            switch ($method) {
                case 'download':
                case 'thumb':
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/recordIcons/404.png';
                $response = new BinaryFileResponse($imagePath, 404, ['Content-Type'=> 'image/png']);
                $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT, // INLINE pro zobrazení, ATTACHMENT pro stažení
                    basename($imagePath)
                );
                return $response;
                case 'thumbs':
                    return new JsonResponse(['error' => 'not found'], 404);
                default:
                    return new Response('not found', 404);

            }
        }
    }

    #[Route('/detail', name: 'app_front_specimenDetail', methods: ['GET'])]
    public function detail(#[MapQueryParameter] int $id): Response
    {
        return $this->render('front/home/detail.html.twig', ['specimen'=> $this->specimenService->find($id)]);
    }

    #[Route('/exportKml', name: 'app_front_exportKml', methods: ['GET'])]
    public function exportKml(): Response
    {
        return new Response();
    }
    #[Route('/exportExcel', name: 'app_front_exportExcel', methods: ['GET'])]
    public function exportExcel(): Response
    {
        return new Response();
    }
    #[Route('/exportCsv', name: 'app_front_exportCsv', methods: ['GET'])]
    public function exportCsv(): Response
    {
        return new Response();
    }
    #[Route('/exportOds', name: 'app_front_exportOds', methods: ['GET'])]
    public function exportOds(): Response
    {
        return new Response();
    }
}
