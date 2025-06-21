<?php declare(strict_types=1);

namespace App\Twig\Extension;

use JACQ\Entity\Jacq\Herbarinput\ImageDefinition;
use JACQ\Entity\Jacq\Herbarinput\Institution;
use JACQ\Entity\Jacq\Herbarinput\Specimens;
use JACQ\Service\Legacy\IiifFacade;
use JACQ\Service\ImageService;
use JACQ\Service\JacqNetworkService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SpecimenIframeExtension extends AbstractExtension
{
    public function __construct(protected readonly RouterInterface $router, protected readonly IIIFFacade $iiifFacade, protected readonly ImageService $imageService, protected LoggerInterface $logger, protected readonly JacqNetworkService $jacqNetworkService)
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
        if (!$specimen->hasImageObservation() && !$specimen->hasImage()) {
            return '';
        }
        $sourceId = $specimen->getHerbCollection()->getInstitution()->getId();
        $imageDefinition = $specimen->getHerbCollection()->getInstitution()->getImageDefinition();
        $phaidra = false;
        if ($sourceId === Institution::WU) {
            // ask phaidra server if it has the desired picture. If not, use old method
            $picname = sprintf("WU%0" . $imageDefinition->getHerbNummerNrDigits() . ".0f", str_replace('-', '', $specimen->getHerbNumber() ?? ''));
            $ch = curl_init("https://app05a.phaidra.org/viewer/" . $picname);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $curl_response = curl_exec($ch);
            if ($curl_response) {
                $info = curl_getinfo($ch);
                if ($info['http_code'] == 200) {
                    $phaidra = true;
                    $phaidraManifest = $this->jacqNetworkService->translateSymfonyToRealServicePath($this->router->generate('services_rest_iiif_manifest', ['specimenID' => $specimen->getId()], UrlGeneratorInterface::ABSOLUTE_URL));
                }
            }
            curl_close($ch);
        }
        if ($phaidra) {  // phaidra picture found, use iiif
            return $this->includeIiif($imageDefinition, $phaidraManifest);
        } elseif ($imageDefinition->isIiifCapable()) {
            return $this->includeIiif($imageDefinition, $this->iiifFacade->resolveManifestUri($specimen));
        } elseif ($imageDefinition->getServerType() === 'bgbm') {  // but not iiif_capable
            $bgbm = '?filename=' . rawurlencode(basename((string)$specimen->getId())) . '&sid=' . $specimen->getId();
            return $this->includeBgbm($bgbm);
        } elseif ($imageDefinition->getServerType() === 'djatoka') {   // but not iiif_capable, so the original one
            $picdetails = $this->imageService->getPicDetails((string)$specimen->getId());
            $transfer = $this->imageService->getPicInfo($picdetails);

            $djatokaOptions = [];
            $djatokaError = null;
            $djatokaTransferOutput = null;
            if ($transfer) {
                if (!empty($transfer['error'])) {
                    $djatokaError = "Picture server list error. Falling back to original image name.";
                    $djatokaOptions[] = 'filename=' . rawurlencode(basename($picdetails['filename'])) . '&sid=' . $specimen->getId();
                    $this->logger->info('Specimen {id} had transfer error {e}.', [
                        'id' => $specimen->getId(),
                        'e'=>$transfer['error']
                    ]);
                } else {
                    if (count($transfer['pics'] ?? array()) > 0) {
                        foreach ($transfer['pics'] as $v) {
                            $djatokaOptions[] = 'filename=' . rawurlencode(basename($v)) . '&sid=' . $specimen->getId();
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

    protected function includeIiif(ImageDefinition $imageDefinition, string $manifestUrl): string
    {
        return "<table>
            <tr>
                <td>
                    <a href='" . $imageDefinition->getIiifUrl() . "?manifest=" . $manifestUrl . "' target='_blank'>
                        <img height='15' width='15' src='/recordIcons/logo-iiif.png'>
                    </a>
                </td>
                <td>
                    <iframe title='Mirador' width='100%' height='800px' src='" . $imageDefinition->getIiifUrl() . "?manifest=" . $manifestUrl . "' allowfullscreen='true' webkitallowfullscreen='true' mozallowfullscreen='true'>
                    </iframe>
                </td>
            </tr>
        </table>";
    }

    protected function includeBgbm(string $param): string
    {
        $baseUrl = $this->router->generate("output_image_endpoint");

        return "<table>
          <tr>
            <td>
              <a href='". $baseUrl."?". $param . "&method=show' target='imgBrowser'>
                <img src='". $baseUrl."?". $param . "&method=thumb' style='border: 2px;'>
              </a><br>
            (<a href='". $baseUrl."?". $param . "&method=show'>Open viewer</a>)
            </td>
          </tr>
        </table>";
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
