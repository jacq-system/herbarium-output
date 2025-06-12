<?php declare(strict_types=1);

namespace App\Controller\Output;

use App\Exception\InvalidStateException;
use App\Facade\SearchFormFacade;
use App\Repository\Herbarinput\InstitutionRepository;
use App\Service\CollectionService;
use App\Service\ImageService;
use App\Service\Output\ExcelService;
use App\Service\Output\SearchFormSessionService;
use App\Service\SpecimenService;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class ImageController extends AbstractController
{

    public const array RECORDS_PER_PAGE = array(10, 30, 50, 100);

    public function __construct(protected readonly ImageService $imageService, protected LoggerInterface $appLogger)
    {
    }

    #[Route('/image', name: 'app_front_image_endpoint', methods: ['GET'])]
    public function showImage(#[MapQueryParameter] string $filename, #[MapQueryParameter] ?string $sid, #[MapQueryParameter] string $method, #[MapQueryParameter] ?string $format): Response
    {
        if ($_SERVER['REMOTE_ADDR'] == '94.177.9.139' && !empty($sid) && $method == 'download' && strrpos($filename, '_') == strpos($filename, '_')) {
            // kulturpool is calling...
            // Redirect to new location
            $this->redirectToRoute("services_rest_images_europeana", ["specimenID" => $sid], 303);
        }

        //TODO only due Djatoka in the getPicDetails() is needed to have this format, otherwise could be derived from the streamed response headers, see bellow
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
            if ($imageStream === false) {
                $this->appLogger->warning('Image [{filename}] not found.', [
                    'url' => $url,
                    'filename' => $filename,
                    'sid' => $sid,
                    'method' => $method,
                    'format' => $format
                ]);
                throw new InvalidStateException('Unable to open image stream from '.$url.' route');
            }


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


}
