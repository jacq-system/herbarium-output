<?php declare(strict_types=1);

namespace App\Facade\Rest;


use App\Entity\Jacq\Herbarinput\Specimens;
use App\Service\ReferenceService;
use App\Service\SpecimenService;
use App\Service\TaxonService;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use SimpleXMLElement;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use function PHPUnit\Framework\isEmpty;

readonly class IiifFacade extends BaseFacade
{
    public function __construct(EntityManagerInterface $entityManager, RouterInterface $router, protected TaxonService $taxonService, protected ReferenceService $referenceService, protected SpecimenService $specimenService, protected ClientInterface $client)
    {
        parent::__construct($entityManager, $router);
    }

    /**
     * create image manifest as an array for a given image filename and server-ID with data from a Cantaloupe-Server extended with a Djatoka-Interface
     *
     * @param int $server_id ID of image server
     * @param string $filename name of image file
     * @return array manifest metadata
     */
    public function createManifestFromExtendedCantaloupeImage(int $server_id, string $identifier): array
    {
        // check if this image identifier is already part of a specimen and return the correct manifest if so
        $sql = "SELECT specimen_ID
                  FROM herbar_pictures.djatoka_images
                  WHERE server_id = :server_id
                   AND filename = :identifier";
        $djatokaImage = $this->entityManager->getConnection()->executeQuery($sql, ['server_id' => $server_id, 'identifier' => $identifier])->fetchOne();

        if (!empty($djatokaImage)) {  // we've hit an already existing specimen
            return $this->getManifest($this->specimenService->findAccessibleForPublic($djatokaImage));
        } else {
            $urlmanifestpre = $this->router->generate('services_rest_iiif_createManifest', ['serverID' => $server_id, 'imageIdentifier' => $identifier], UrlGeneratorInterface::ABSOLUTE_URL);
            $result = $this->createManifestFromExtendedCantaloupe($server_id, $identifier, $urlmanifestpre);
            if (!empty($result)) {
                $result['@id'] = $urlmanifestpre;  // to point at ourselves
            }

            return $result;
        }
    }

    /**
     * act as a proxy and get the iiif manifest of a given specimen-ID from the backend (enriched with additional data) or the manifest server if no backend was defined
     *
     * @param int $specimenID ID of specimen
     * @return array received manifest
     */
    public function getManifest(Specimens $specimen): array
    {
        $manifest_backend = $specimen->getHerbCollection()?->getIiifDefinition()?->getManifestBackend();


        if ($manifest_backend === null) {
            return array();  // nothing found
        } elseif (empty($manifest_backend)) {  // no backend is defined, so fall back to manifest server
            $manifestBackend = $this->resolveManifestUri($specimen) ?? '';
            $fallback = true;
        } else {  // get data from backend
            $manifestBackend = $this->makeURI($specimen, $manifest_backend);
            $fallback = false;
        }

        $result = array();
        if ($manifestBackend) {
            if (str_starts_with($manifestBackend, 'POST:')) {
                $result = $this->getManifestIiifServer($specimen);
            } else {
                $request = new Request('GET', $manifestBackend);
                $response = $this->client->sendRequest($request)->getBody()->getContents();
                $result = (!empty($response)) ? json_decode($response, true) : array();
            }
            if ($result && !$fallback) {  // we used a true backend, so enrich the manifest with additional data
                $result['@id'] = $this->router->generate('services_rest_iiif_manifest', ['specimenID' => $specimen->getId()], UrlGeneratorInterface::ABSOLUTE_URL);  // to point at ourselves
                $result['description'] = $this->specimenService->getSpecimenDescription($specimen);
                $result['label'] = $this->specimenService->getScientificName($specimen);
                $result['attribution'] = $specimen->getHerbCollection()->getInstitution()->getLicenseUri();
                $result['logo'] = array('@id' => $specimen->getHerbCollection()->getInstitution()->getOwnerLogoUri());
                $rdfLink = array('@id' => $specimen->getMainStableIdentifier()->getIdentifier(),
                    'label' => 'RDF',
                    'format' => 'application/rdf+xml',
                    'profile' => 'https://cetafidentifiers.biowikifarm.net/wiki/CSPP');
                if (empty($result['seeAlso'])) {
                    $result['seeAlso'] = array($rdfLink);
                } else {
                    $result['seeAlso'][] = $rdfLink;
                }
                $result['metadata'] = $this->getMetadataWithValues($specimen, (isset($result['metadata'])) ? $result['metadata'] : array());
            }
        }
        return $result;
    }

    public function resolveManifestUri(Specimens $specimen): string
    {
        $manifestUri = $specimen->getHerbCollection()?->getIiifDefinition()?->getManifestUri();

        if ($manifestUri === null || $manifestUri === '') {
            return '';
        }
        return $this->makeURI($specimen, $manifestUri);

    }

    /**
     * generate an uri out of several parts of a given specimen-ID. Understands tokens (specimenID, HerbNummer, fromDB, ...) and normal text
     *
     * @param int $specimenID ID of specimen
     * @param array $parts text and tokens
     */
    protected function makeURI(Specimens $specimen, ?string $manifestUri=''): ?string
    {
        $uri = '';
        foreach ($this->parser($manifestUri) as $part) {
            if ($part['token']) {
                $tokenParts = explode(':', $part['text']);
                $token = $tokenParts[0];
                $subtoken = (isset($tokenParts[1])) ? $tokenParts[1] : '';
                switch ($token) {
                    case 'specimenID':
                        $uri .= $specimen->getId();
                        break;
                    case 'stableIdentifier':    // use stable identifier, options are either :last or :https
                        $stableIdentifier = $specimen->getMainStableIdentifier()->getIdentifier();

                        if (!empty($stableIdentifier)) {
                            switch ($subtoken) {
                                case 'last':
                                    $uri .= substr($stableIdentifier, strrpos($stableIdentifier, '/') + 1);
                                    break;
                                case 'https':
                                    $uri .= str_replace('http:', 'https:', $stableIdentifier);
                                    break;
                                default:
                                    $uri .= $stableIdentifier;
                                    break;
                            }
                        }
                        break;
                    case 'herbNumber':  // use HerbNummer with removed hyphens and spaces, options are :num and/or :reformat
                        $imageDefinition = $specimen->getHerbCollection()->getInstitution()?->getImageDefinition();
                        $HerbNummer = str_replace(['-', ' '], '', $specimen->getHerbNumber()); // remove hyphens and spaces
                        // first check subtoken :num
                        if (in_array('num', $tokenParts)) {                         // ignore text with digits within, only use the last number
                            if (preg_match("/\d+$/", $HerbNummer, $matches)) {  // there is a number at the tail of HerbNummer, so use it
                                $HerbNummer = $matches[0];
                            } else {                                                       // HerbNummer ends with text
                                $HerbNummer = 0;
                            }
                        }
                        // and second :reformat
                        if (in_array("reformat", $tokenParts)) {                    // correct the number of digits with leading zeros
                            $uri .= sprintf("%0" . $imageDefinition->getHerbNummerNrDigits() . ".0f", $HerbNummer);
                        } else {                                                           // use it as it is
                            $uri .= $HerbNummer;
                        }
                        break;
                    case 'fromDB':
                        // first subtoken must be the table name in db "herbar_pictures", second subtoken must be the column name to use for the result.
                        // where-clause is always the stable identifier and its column must be named "stableIdentifier".
                        if ($subtoken && !empty($tokenParts[2])) {
                            $stableIdentifier = $specimen->getMainStableIdentifier()->getIdentifier();

                            // SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(manifest, '/', -2), '/', 1) AS derivate_ID FROM `stblid_manifest` WHERE 1
                            $sql = "SELECT " . $tokenParts[2] . "
                                                 FROM herbar_pictures.$subtoken
                                                 WHERE stableIdentifier LIKE :stableIdentifier
                                                 LIMIT 1";
                            // TODO using variables as part of SQL !! - forcing replica at least..
                            $connection = $this->entityManager->getConnection();
                            if ($connection instanceof PrimaryReadReplicaConnection) {
                                $connection->ensureConnectedToReplica();
                            }
                            $row = $connection->executeQuery($sql, ["stableIdentifier" => $stableIdentifier])->fetchAssociative();

                            $uri .= $row[$tokenParts[2]];
                        }
                        break;
                }
            } else {
                $uri .= $part['text'];
            }
        }

        return $uri;
    }

    /**
     * parse text into parts and tokens (text within '<>')
     */
    public function parser(string $text): array
    {

        $parts = explode('<', $text);
        $result = array(array('text' => $parts[0], 'token' => false));
        for ($i = 1; $i < count($parts); $i++) {
            $subparts = explode('>', $parts[$i]);
            $result[] = array('text' => $subparts[0], 'token' => true);
            if (!empty($subparts[1])) {
                $result[] = array('text' => $subparts[1], 'token' => false);
            }
        }
        return $result;
    }

    /**
     * get array of metadata for a given specimen from POST request
     *
     * @param int $specimenID specimen-ID
     * @return array metadata from iiif server
     */

    protected function getManifestIiifServer(Specimens $specimen): array
    {
        $serverId = $specimen->getHerbCollection()->getInstitution()->getImageDefinition()->getId();

        $urlmanifestpre = $this->makeURI($specimen, $specimen->getHerbCollection()?->getIiifDefinition()?->getManifestUri());
        $urlmanifestBackend = substr($this->makeURI($specimen, $specimen->getHerbCollection()?->getIiifDefinition()?->getManifestBackend()), 5);
        $identifier = $this->getFilename($specimen);

        return $this->createManifestFromExtendedCantaloupe($serverId, $identifier, $urlmanifestpre, $urlmanifestBackend);
    }

    /**
     * get a clean filename for a given specimen-ID
     */
    protected function getFilename(Specimens $specimen): string
    {
        $filename = '';
        $HerbNummer = str_replace('-', '', $specimen->getHerbNumber());

            // Construct clean filename
            if (!empty($specimen->getHerbCollection()->getPictureFilename())) {   // special treatment for this collection is necessary
                $parts = $this->parser($specimen->getHerbCollection()->getPictureFilename());

                foreach ($parts as $part) {
                    if ($part['token']) {
                        $tokenParts = explode(':', $part['text']);
                        $token = $tokenParts[0];
                        switch ($token) {
                            case 'coll_short_prj':                                      // use contents of coll_short_prj
                                $filename .= $specimen->getHerbCollection()->getCollShortPrj();
                                break;
                            case 'HerbNummer':                                          // use HerbNummer with removed hyphens, options are :num and :reformat
                                if (in_array('num', $tokenParts)) {                     // ignore text with digits within, only use the last number
                                    if (preg_match("/\d+$/", $HerbNummer, $matches)) {  // there is a number at the tail of HerbNummer
                                        $number = $matches[0];
                                    } else {                                            // HerbNummer ends with text
                                        $number = 0;
                                    }
                                } else {
                                    $number = $HerbNummer;                              // use the complete HerbNummer
                                }
                                if (in_array("reformat", $tokenParts)) {                // correct the number of digits with leading zeros
                                    $filename .= sprintf("%0" .  $specimen->getHerbCollection()->getInstitution()->getImageDefinition()->getHerbNummerNrDigits() . ".0f", $number);
                                } else {                                                // use it as it is
                                    $filename .= $number;
                                }
                                break;
                        }
                    } else {
                        $filename .= $part['text'];
                    }
                }
            } else {    // standard filename, would be "<coll_short_prj>_<HerbNummer:reformat>"
                $filename = sprintf("%s_%0" . $specimen->getHerbCollection()->getInstitution()->getImageDefinition()->getHerbNummerNrDigits() . ".0f", $specimen->getHerbCollection()->getCollShortPrj(), $HerbNummer);
            }

        return $filename;
    }

    /**
     * create image manifest as an array for a given specimen with data from a Cantaloupe-Server with a djatoka-extension or another api
     */
    protected function createManifestFromExtendedCantaloupe(int $server_id, string $identifier, string $urlmanifestpre, ?string $urlmanifestBackend = ''):array
    {
        $sql = "SELECT iiif.manifest_backend, iiif.extension, img.imgserver_url, img.key
                                   FROM tbl_img_definition img
                                    LEFT JOIN herbar_pictures.iiif_definition iiif ON iiif.source_id_fk = img.source_id_fk
                                   WHERE img.img_def_ID = :server_id";
        $imgServer = $this->entityManager->getConnection()->executeQuery($sql, ['server_id' => $server_id])->fetchAssociative();

        if (empty($urlmanifestBackend)) {
            $urlmanifestBackend = substr($imgServer['manifest_backend'], 5);
        }

        // TODO example for curl request debugging
//        curl_setopt($curl, CURLOPT_PROXY, 'http://mitmproxy:8080');

        switch ($imgServer['extension']) {
            case 'djatoka': // ask the djatoka extension for resources with metadata
                $data = array(
                    'id' => '1',
                    'method' => 'listResourcesWithMetadata',
                    'params' => array(
                        $imgServer['key'],
                        array(
                            $identifier,
                            $identifier . "_%",
                            $identifier . "A",
                            $identifier . "B",
                            "tab_" . $identifier,
                            "obs_" . $identifier,
                            "tab_" . $identifier . "_%",
                            "obs_" . $identifier . "_%"
                        )
                    )
                );

                $data_string = json_encode($data);
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $urlmanifestBackend);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
                        'Content-Length: ' . strlen($data_string))
                );

                $curl_response = curl_exec($curl);
                $obj = json_decode($curl_response, TRUE);
                curl_close($curl);

                break;
            case 's3proxy':  // the iiif-server uses a s3-backend via a proxy, which we can use


                try {
                    $request = new Request('GET', $urlmanifestBackend . "?prefix=$identifier");
                    $response = $this->client->sendRequest($request)->getBody()->getContents();

                    $xml = new SimpleXMLElement($response);

                    $obj['result'] = array();
                    // "Contents" gives an array of objects
                    foreach ($xml->Contents as $contents) {
                        if (!empty($contents->Key[0])) {
                            $filename = pathinfo((string) $contents->Key, PATHINFO_FILENAME);
                            // the backend only gives the filenames, to get the width and height we need to ask the iiif-server
                            $request = new Request('GET', $imgServer['imgserver_url'] . $filename . "/info.json");
                            $response = $this->client->sendRequest($request)->getBody()->getContents();
                            $data = json_decode($response, true);
                            $obj['result'][] = [
                                'identifier' => $filename,
                                'path' => '/' . $filename,
                                'width' => $data['width'],
                                'height' => $data['height']
                            ];
                        }
                    }
                }
                catch (\Exception) {
                    return array();  //something went wrong, so consider it as "nothing found"
                }

                break;
            default:    // no extension or api present, so use the identifier as filename and ask the iiif-server for width and height
                try {
                    $request = new Request('GET', $imgServer['imgserver_url'] . $identifier . "/info.json");
                    $response = $this->client->sendRequest($request)->getBody()->getContents();
                    if (isEmpty($response)){
                        return array();
                    }
                    $data = json_decode($response, true);
                    $obj['result'][0] = [
                        'identifier' => $identifier,
                        'path' => '/' . $identifier,
                        'width' => $data['width'],
                        'height' => $data['height']
                    ];
                }
                catch (GuzzleException) {
                    return array();  //something went wrong, so consider it as "nothing found"
                }
        }

        if (empty($obj['result'])) {
            return array();  // nothing found
        }

        $result['@context'] = array('http://iiif.io/api/presentation/2/context.json',
            'http://www.w3.org/ns/anno.jsonld');
        //$result['@id']      = $urlmanifestpre.$urlmanifestpost;
        $result['@type'] = 'sc:Manifest';
        //$result['label']      = $specimenID;
        $canvases = array();
        for ($i = 0; $i < count($obj['result']); $i++) {
            $canvases[] = array(
                '@id' => $urlmanifestpre . '/c/' . $identifier . '_' . $i,
                '@type' => 'sc:Canvas',
                'label' => $obj['result'][$i]["identifier"],
                'height' => $obj['result'][$i]["height"],
                'width' => $obj['result'][$i]["width"],
                'images' => array(
                    array(
                        '@id' => $urlmanifestpre . '/i/' . $identifier . '_' . $i,
                        '@type' => 'oa:Annotation',
                        'motivation' => 'sc:painting',
                        'on' => $urlmanifestpre . '/c/' . $identifier . '_' . $i,
                        'resource' => array(
                            '@id' => $imgServer['imgserver_url'] . str_replace('/', '!', substr($obj['result'][$i]["path"], 1)),
                            '@type' => 'dctypes:Image',
                            'format' => (((new \SplFileInfo($obj['result'][$i]['path']))->getExtension() == 'jp2') ? 'image/jp2' : 'image/jpeg'),
                            'height' => $obj['result'][$i]["height"],
                            'width' => $obj['result'][$i]["width"],
                            'service' => array(
                                '@context' => 'http://iiif.io/api/image/2/context.json',
                                '@id' => $imgServer['imgserver_url'] . str_replace('/', '!', substr($obj['result'][$i]["path"], 1)),
                                'profile' => 'http://iiif.io/api/image/2/level2.json',
                                'protocol' => 'http://iiif.io/api/image'
                            ),
                        ),
                    ),
                )
            );
        }
        $sequences = array(
            '@id' => $urlmanifestpre . '#sequence-1',
            '@type' => 'sc:Sequence',
            'canvases' => $canvases,
            'label' => 'Current order',
            'viewingDirection' => 'left-to-right'
        );
        $result['sequences'] = array($sequences);

        $result['thumbnail'] = array(
            '@id' => $imgServer['imgserver_url'] . str_replace('/', '!', substr($obj['result'][0]["path"], 1)) . '/full/400,/0/default.jpg',
            '@type' => 'dctypes:Image',
            'format' => 'image/jpeg',
            'service' => array(
                '@context' => 'http://iiif.io/api/image/2/context.json',
                '@id' => $imgServer['imgserver_url'] . str_replace('/', '!', substr($obj['result'][0]["path"], 1)),
                'profile' => 'http://iiif.io/api/image/2/level2.json',
                'protocol' => 'http://iiif.io/api/image'
            ),
        );

        return $result;
    }


    /**
     * get array of metadata for a given specimen, where values are not empty
     */
    protected function getMetadataWithValues(Specimens $specimenEntity,  array $originalMetadata = array()): array
    {
        $data = $this->getMetadata($specimenEntity, $originalMetadata);
        $result = array();
        foreach ($data as $row) {
            if (!empty($row['value'])) {
                $result[] = $row;
            }
        }
        return $result;
    }

    /**
     * get array of metadata for a given specimen
     */
    protected function getMetadata(Specimens $specimenEntity, array $metadata = array()): array
    {

        $dcData = $this->specimenService->getDublinCore($specimenEntity);
        foreach ($dcData as $label => $value) {
            $metadata[] = array('label' => $label,
                'value' => $value);
        }

        $dwcData = $this->specimenService->getDarwinCore($specimenEntity);
        foreach ($dwcData as $label => $value) {
            if (is_array($value)) {
                foreach ($value as $subValue) {
                    $metadata[] = array('label' => $label,
                        'value' => $subValue);
                }
            } else {
                $metadata[] = array('label' => $label,
                    'value' => $value);
            }
        }

        $collector =$specimenEntity->getCollector();
        $metadata[] = array('label' => 'CETAF_ID', 'value' => $specimenEntity->getMainStableIdentifier()->getIdentifier());
        $metadata[] = array('label' => 'dwciri:recordedBy', 'value' => $collector->getWikidataId());
        if (!empty($collector->getHuhId())) {
            $metadata[] = array('label' => 'owl:sameAs', 'value' => $collector->getHuhId());
        }
        if (!empty($collector->getViafId())) {
            $metadata[] = array('label' => 'owl:sameAs', 'value' => $collector->getViafId());
        }
        if (!empty($collector->getOrcidId())) {
            $metadata[] = array('label' => 'owl:sameAs', 'value' => $collector->getOrcidId());
        }
        if (!empty($collector->getWikidataId())) {
            $metadata[] = array('label' => 'owl:sameAs', 'value' => $collector->getWikidataId());
            $metadata[] = array('label' => 'owl:sameAs', 'value' => "https://scholia.toolforge.org/author/" . basename($collector->getWikidataId()));
        }

        foreach ($metadata as $key => $line) {
            if ($line['value'] !== null && (str_starts_with((string)$line['value'], 'http://') || str_starts_with((string)$line['value'], 'https://'))) {
                $metadata[$key]['value'] = "<a href='" . $line['value'] . "'>" . $line['value'] . "</a>";
            }
        }

        return $metadata;
    }
}
