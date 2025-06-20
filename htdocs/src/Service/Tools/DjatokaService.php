<?php declare(strict_types=1);

namespace App\Service\Tools;

use JACQ\Repository\Herbarinput\ImageDefinitionRepository;
use JACQ\Repository\Herbarinput\SpecimensRepository;
use JACQ\Service\ImageService;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class DjatokaService
{
     public function __construct(protected ImageService $imageService,  protected HttpClientInterface $client, protected SpecimensRepository $specimensRepository, protected ImageDefinitionRepository $imageDefinitionRepository)
    {
    }

    public function getData(?int $source): array
    {
        $checks = [
            'ok' => [],
            'fail' => [],
            'noPicture' => []
        ];

        $imageServers = $this->imageDefinitionRepository->getDjatokaServers($source);
        if(empty($imageServers)) {throw new EntityNotFoundException('No eligible server available.');}
        foreach ($imageServers as $server) {

            $ok = true;
            $errorRPC = $warningRPC = $errorImage = "";

            $specimen = $this->specimensRepository->getExampleSpecimenWithImage($server->getInstitution());
            if ($specimen !== null) {
                $picdetails = $this->imageService->getPicDetails((string) $specimen->getId());
                $filename   = $picdetails['originalFilename'];

                try{
                    $response1 = $this->client->request('POST', $picdetails['url'] . 'jacq-servlet/ImageServer', [
                        'json'   => ['method' => 'listResources',
                            'params' => [$picdetails['key'],
                                [ $picdetails['filename'],
                                    $picdetails['filename'] . "_%",
                                    $picdetails['filename'] . "A",
                                    $picdetails['filename'] . "B",
                                    "tab_" . $picdetails['specimenID'],
                                    "obs_" . $picdetails['specimenID'],
                                    "tab_" . $picdetails['specimenID'] . "_%",
                                    "obs_" . $picdetails['specimenID'] . "_%"
                                ]
                            ],
                            'id'     => 1
                        ],
                        'verify_host' => false,
                        'verify_peer' => false
                    ]);
                    $data = json_decode($response1->getContent(), true);
                    if (!empty($data['error'])) {
                        $ok = false;
                        $errorRPC = $data['error'];
                    } elseif (empty($data['result'][0])) {
                        $ok = false;
                        $errorRPC = "FAIL: called '" . $picdetails['filename'] . "', returned empty result";
                    } elseif ($data['result'][0] != $picdetails['filename']) {
                        $ok = false;
                        if (substr(mb_strtolower($data['result'][0]), 0, mb_strlen($picdetails['filename'])) != mb_strtolower($picdetails['filename'])) {
                            $errorRPC = "FAIL: called '" . $picdetails['filename'] . "', returned '" . $data['result'][0] . "'";
                        } else {
                            $warningRPC = "WARNING: called '" . $picdetails['filename'] . "', returned '" . $data['result'][0] . "'";
                        }
                        $filename = $data['result'][0];
                    }
                }
                catch( \Exception $e ) {
                    $ok = false;
                    $errorRPC = $e->getMessage();
                }

                try {
                    // Construct URL to djatoka-resolver
                    $url = preg_replace('/([^:])\/\//', '$1/', $picdetails['url'] . "adore-djatoka/resolver"
                        . "?url_ver=Z39.88-2004"
                        . "&rft_id=$filename"
                        . "&svc_id=info:lanl-repo/svc/getRegion"
                        . "&svc_val_fmt=info:ofi/fmt:kev:mtx:jpeg2000"
                        . "&svc.format=image/jpeg"
                        . "&svc.scale=0.1");
                    $response2 = $this->client->request('GET', $url, [
                        'verify_host'      => false, //TODO dtto
                        'verify_peer'      => false
                    ]);
//            $data = json_decode($response2->getContent(), true);
                    $statusCode = $response2->getStatusCode();
                    if ($statusCode != 200) {
                        $ok = false;
                        if ($statusCode == 404) {
                            $errorImage = "FAIL: <404> Image not found";
                        } elseif ($statusCode == 500) {
                            $errorImage = "FAIL: <500> Server Error";
                        } else {
                            $errorImage = "FAIL: Status Code <$statusCode>";
                        }
                    }
                }
                catch(\Exception $e ) {
                    $ok = false;
                    $errorImage = htmlentities($e->getMessage());
                }
                if ($ok) {
                    $checks['ok'][] = ['source_id'  => $server->getInstitution()->getId(),
                        'source'     => $server->getAbbreviation(),
                        'specimenID' => $specimen->getId()
                    ];
                } elseif ($warningRPC) {
                    $checks['warn'][] = ['source_id'  => $server->getInstitution()->getId(),
                        'source'     => $server->getAbbreviation(),
                        'specimenID' => $specimen->getId(),
                        'warningRPC' => $warningRPC,
                        'errorImage' => $errorImage
                    ];
                } else {
                    $checks['fail'][] = ['source_id'  => $server->getInstitution()->getId(),
                        'source'     => $server->getAbbreviation(),
                        'specimenID' => $specimen->getId(),
                        'errorRPC'   => $errorRPC,
                        'errorImage' => $errorImage
                    ];
                }
            } else {
                $checks['noPicture'][] = ['source_id' => $server->getInstitution()->getId(),
                    'source'    => $server->getAbbreviation()
                ];
            }
        }
        return $checks;
    }

}
