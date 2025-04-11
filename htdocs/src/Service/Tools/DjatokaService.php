<?php declare(strict_types=1);

namespace App\Service\Tools;

use App\Service\ImageService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class DjatokaService
{
     public function __construct(protected EntityManagerInterface $entityManager, protected ImageService $imageService,  protected HttpClientInterface $client)
    {
    }

    protected function getAllDjatokaServers(?string $source): array
    { //TODO conditinioal build of constraint is hard to read
        $constraint = ' AND source_id_fk != 1 AND iiif_capable != 1';   // wu need special treatment, iiif-servers are not checked
        if ($source!==null) {
            if (is_numeric($source)) {
                $constraint .= " AND source_ID_fk = " . intval($source);
            } else {
                $sql = "SELECT source_id
                                  FROM meta
                                  WHERE source_code LIKE :source";
                $row = $this->entityManager->getConnection()->executeQuery($sql, ['source' => $source])->fetchAssociative();
                if (!empty($row['source_id'])) {
                    $constraint = " AND source_ID_fk = " . $row['source_id'];
                }
            }
        }

        $sql = "SELECT source_id_fk, img_coll_short
                        FROM tbl_img_definition
                        WHERE imgserver_type = 'djatoka'
                         $constraint
                        ORDER BY img_coll_short";

        return $this->entityManager->getConnection()->executeQuery($sql)->fetchAllAssociative();
    }

    protected function getExampleImage(int $sourceId): int|false
    {
        $sql = "SELECT s.specimen_ID
                              FROM tbl_specimens s, tbl_management_collections mc
                              WHERE s.collectionID = mc.collectionID
                               AND s.accessible = 1
                               AND s.digital_image = 1
                               AND mc.source_id = :sourceId
                              ORDER BY s.specimen_ID
                              LIMIT 1";
        return $this->entityManager->getConnection()->executeQuery($sql, ["sourceId"=>$sourceId])->fetchOne();
    }

    public function getData(?string $source): array
    {

        $checks = [
            'ok' => [],
            'fail' => [],
            'noPicture' => []
        ];

        $rows = $this->getAllDjatokaServers($source);
        if(empty($rows)) {throw new EntityNotFoundException('No eligible server available.');}
        foreach ($rows as $row) {

            $ok = true;
            $errorRPC = $warningRPC = $errorImage = "";

            $specimenID = $this->getExampleImage($row['source_id_fk']);
            if ($specimenID !== false) {
                $picdetails = $this->imageService->getPicDetails((string) $specimenID);
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
                        'verify_host' => false, //TODO https://symfony.com/doc/current/http_client.html#https-certificates
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
                    $checks['ok'][] = ['source_id'  => $row['source_id_fk'],
                        'source'     => $row['img_coll_short'],
                        'specimenID' => $specimenID
                    ];
                } elseif ($warningRPC) {
                    $checks['warn'][] = ['source_id'  => $row['source_id_fk'],
                        'source'     => $row['img_coll_short'],
                        'specimenID' => $specimenID,
                        'warningRPC' => $warningRPC,
                        'errorImage' => $errorImage
                    ];
                } else {
                    $checks['fail'][] = ['source_id'  => $row['source_id_fk'],
                        'source'     => $row['img_coll_short'],
                        'specimenID' => $specimenID,
                        'errorRPC'   => $errorRPC,
                        'errorImage' => $errorImage
                    ];
                }
            } else {
                $checks['noPicture'][] = ['source_id' => $row['source_id_fk'],
                    'source'    => $row['img_coll_short']
                ];
            }
        }
        return $checks;
    }

}
