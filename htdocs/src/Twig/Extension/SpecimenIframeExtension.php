<?php declare(strict_types=1);

namespace App\Twig\Extension;

use JACQ\Entity\Jacq\Herbarinput\Institution;
use JACQ\Entity\Jacq\Herbarinput\Specimens;
use JACQ\Enum\JacqRoutesNetwork;
use JACQ\Repository\Herbarinput\ImageDefinitionRepository;
use JACQ\Service\Legacy\IiifFacade;
use JACQ\Service\ImageService;
use JACQ\Service\JacqNetworkService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SpecimenIframeExtension extends AbstractExtension
{
    public function __construct(protected readonly RouterInterface $router, protected readonly IIIFFacade $iiifFacade, protected readonly ImageService $imageService, protected LoggerInterface $logger, protected readonly JacqNetworkService $jacqNetworkService, protected ImageDefinitionRepository $imageDefinitionRepository)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('photoIframe', [$this, 'getPhotoIframe']),
        ];
    }

    public function getPhotoIframe(Specimens $specimen): string
    {
        if (!$specimen->imageObservation && !$specimen->image) {
            return '';
        }
        $sourceId = $specimen->herbCollection->institution->id;
        $imageDefinition = $this->imageDefinitionRepository->getImageDefiniton($specimen->herbCollection->institution);
        $phaidra = false;
        if ($sourceId === Institution::WU) {
            // ask phaidra server if it has the desired picture. If not, use old method
            $picname = sprintf("WU%0" . $imageDefinition->herbNummerNrDigits . ".0f", str_replace('-', '', $specimen->herbNumber ?? ''));
            $ch = curl_init("https://app05a.phaidra.org/viewer/" . $picname);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $curl_response = curl_exec($ch);
            if ($curl_response) {
                $info = curl_getinfo($ch);
                if ($info['http_code'] == 200) {
                    $phaidra = true;
                    $phaidraManifest = $this->jacqNetworkService->generateUrl(JacqRoutesNetwork::services_rest_iiif_manifest, (string) $specimen->id);
                }
            }
        }
        if ($phaidra) {  // phaidra picture found, use iiif
            return $this->includeIiif($phaidraManifest);
        } elseif ($imageDefinition->iiifCapable) {
            return $this->includeIiif($this->iiifFacade->resolveManifestUri($specimen));
        } elseif ($imageDefinition->serverType === 'djatoka') {   // but not iiif_capable, so the original one
            $picdetails = $this->imageService->getPicDetails((string)$specimen->id);
            $transfer = $this->imageService->getPicInfo($picdetails);

            $djatokaOptions = [];
            $djatokaError = null;
            $djatokaTransferOutput = null;
            if ($transfer) {
                if (!empty($transfer['error'])) {
                    $djatokaError = "Picture server list error. Falling back to original image name.";
                    $djatokaOptions[] = 'filename=' . rawurlencode(basename($picdetails['filename'])) . '&sid=' . $specimen->id;
                    $this->logger->info('Specimen {id} had transfer error {e}.', [
                        'id' => $specimen->id,
                        'e'=>$transfer['error']
                    ]);
                } else {
                    if (count($transfer['pics'] ?? array()) > 0) {
                        foreach ($transfer['pics'] as $v) {
                            $djatokaOptions[] = 'filename=' . rawurlencode(basename($v)) . '&sid=' . $specimen->id;
                        }
                    } else {
                        $djatokaError = "no pictures found";
                    }
                    if (trim($transfer['output'])) {
                        $djatokaTransferOutput = "\n" . $transfer['output'] . "\n";
                    }
                }
            } else {
                $djatokaError = "transmission error";
            }
            return $this->includeDjatoka($djatokaOptions, $djatokaTransferOutput, $djatokaError);
        }
        return 'no pictures available';

    }

    protected function includeIiif(string $manifestUrl): string
    {
        return "
                <div class='col s12 m12'>
                    <div id='mirador' style='position: relative;' data-manifestId='" . $manifestUrl . "'></div>
                </div>
        ";
    }

    protected function includeDjatoka(array $options, ?string $transferInfo, ?string $error): string
    {
        $text = '';
        if ($error !== null) {
            $text .= $error;
        }
        if (!empty($options)) {
            $text .= "<table><tr>";

            $baseUrl = $this->router->generate("output_image_endpoint");
            foreach ($options as $option) {
                $text .= "<td>
                          <a href = '". $baseUrl."?". $option . "&method=show' target = 'imgBrowser'>
                            <img src = '". $baseUrl."?". $option . "&method=thumb' style = 'border: 2px;'>
                          </a>
                          <br>
                          (<a href = '". $baseUrl."?". $option . "&method=download&format=jpeg2000' > JPEG2000</a>,
                           <a href = '". $baseUrl."?". $option . "&method=download&format=tiff' > TIFF</a>)
                        </td>";
            }
            $text .= "</tr></table>";
        }

        if ($transferInfo !== null) {
            $text.= nl2br($transferInfo);
        }

        return $text;
    }
}
