<?php declare(strict_types=1);

namespace App\Facade\Rest;


use App\Service\ReferenceService;
use App\Service\Rest\SpecimenMapper;
use App\Service\SpecimenService;
use App\Service\TaxonService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

readonly class IiifFacade
{
    public function __construct(protected ReferenceService $referenceService, protected EntityManagerInterface $entityManager, protected TaxonService $taxonService, protected RouterInterface $router, protected SpecimenService $specimenService)
    {
    }

    /**
     * get the URI of the iiif manifest of a given specimen-ID
     */
    public function resolveManifestUri(int $specimenID): array
    {
        $sql = "SELECT iiif.manifest_uri
                                  FROM tbl_specimens s
                                   LEFT JOIN tbl_management_collections mc        ON mc.collectionID = s.collectionID
                                   LEFT JOIN herbar_pictures.iiif_definition iiif ON iiif.source_id_fk = mc.source_id
                                  WHERE specimen_ID = :specimenID";
        $specimen = $this->entityManager->getConnection()->executeQuery($sql, ['specimenID' => $specimenID])->fetchOne();

        if ($specimen === false) {
            return ['uri' => ''];
        }
        if ($specimen !== '') {
            return array('uri' => $this->specimenService->makeURI($specimenID, $this->parser($specimen)));
        } else {
            return ['uri' => ''];
        }
    }

    /**
     * parse text into parts and tokens (text within '<>')
     */
    protected function parser (string $text): array
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
     * act as a proxy and get the iiif manifest of a given specimen-ID from the backend (enriched with additional data) or the manifest server if no backend was defined
     *
     * @param int $specimenID ID of specimen
     * @return array received manifest
     */
    public function getManifest(int $specimenID)
    {
        $sql = "SELECT iiif.manifest_backend
                                  FROM tbl_specimens s
                                   LEFT JOIN tbl_management_collections mc        ON mc.collectionID = s.collectionID
                                   LEFT JOIN herbar_pictures.iiif_definition iiif ON iiif.source_id_fk = mc.source_id
                                  WHERE specimen_ID = :specimenID";
        $manifest_backend = $this->entityManager->getConnection()->executeQuery($sql, ['specimenID' => $specimenID])->fetchOne();

        if  ($manifest_backend === false) {
            return array();  // nothing found
        } elseif (empty($manifest_backend)) {  // no backend is defined, so fall back to manifest server
            $manifestBackend = $this->getManifestUri($specimenID)['uri'] ?? '';
            $fallback = true;
        } else {  // get data from backend
            $manifestBackend = $this->specimenService->makeURI($specimenID, $this->parser($manifest_backend));
            $fallback = false;
        }

        $result = array();
        if ($manifestBackend) {
            if (substr($manifestBackend,0,5) == 'POST:') {
                $result = $this->getManifestIiifServer($specimenID);
            } else {
                $curl = curl_init($manifestBackend);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $curl_response = curl_exec($curl);

                if ($curl_response !== false) {
                    $result = json_decode($curl_response, true);
                }
                curl_close($curl);
            }
            if ($result && !$fallback) {  // we used a true backend, so enrich the manifest with additional data
                //TODO breaking DI
                $specimen = new SpecimenMapper($this->entityManager->getConnection(), $specimenID, $this->router);

                $result['@id']         = $this->router->generate('services_rest_iiif_manifest', ['specimenID' => $specimenID], UrlGeneratorInterface::ABSOLUTE_URL);  // to point at ourselves
                $result['description'] = $specimen->getDescription();
                $result['label']       = $specimen->getLabel();
                $result['attribution'] = $specimen->getAttribution();
                $result['logo']        = array('@id' => $specimen->getLogoURI());
                $rdfLink               = array('@id'     => $specimen->getStableIdentifier(),
                    'label'   => 'RDF',
                    'format'  => 'application/rdf+xml',
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

    /**
     * get the URI of the iiif manifest of a given specimen-ID
     *
     * @param int $specimenID ID of specimen
     * @return array constructed uri or empty string if nothing found
     */
    public function getManifestUri(int $specimenID): array
    {
        $sql = "SELECT iiif.manifest_uri
                  FROM tbl_specimens s
                   LEFT JOIN tbl_management_collections mc        ON mc.collectionID = s.collectionID
                   LEFT JOIN herbar_pictures.iiif_definition iiif ON iiif.source_id_fk = mc.source_id
                  WHERE specimen_ID = :specimenID";
        $manifest_uri = $this->entityManager->getConnection()->executeQuery($sql, ['specimenID' => $specimenID])->fetchOne();

        if (!empty($specimen['manifest_uri'])) {
            return array('uri' => $this->specimenService->makeURI($specimenID, $this->parser($manifest_uri)));
        } else {
            return array('uri' => '');
        }
    }

    /**
     * get array of metadata for a given specimen from POST request
     *
     * @param int $specimenID specimen-ID
     * @return array metadata from iiif server
     */

    protected function getManifestIiifServer(int $specimenID): array
    {
        $sql = "SELECT iiif.manifest_uri, img.img_def_ID
                                  FROM tbl_specimens s
                                   LEFT JOIN tbl_management_collections mc        ON mc.collectionID = s.collectionID
                                   LEFT JOIN herbar_pictures.iiif_definition iiif ON iiif.source_id_fk = mc.source_id
                                   LEFT JOIN tbl_img_definition img               ON img.source_id_fk = mc.source_id
                                  WHERE specimen_ID = :specimenID";

        $specimen = $this->entityManager->getConnection()->executeQuery($sql, ['specimenID' => $specimenID])->fetchAssociative();

        $urlmanifestpre = $this->specimenService->makeURI($specimenID, $this->parser($specimen['manifest_uri']));
        $identifier = $this->getFilename($specimenID);

        return $this->createManifestFromExtendedCantaloupe($specimen['img_def_ID'], $identifier, $urlmanifestpre);
    }

    /**
     * get a clean filename for a given specimen-ID
     *
     * @param int $specimenID specimen-ID
     * @return string the constructed filename or an empty string
     */
    protected function getFilename(int $specimenID)
    {
        $sql = "SELECT s.`HerbNummer`, mc.`picture_filename`,  mc.`coll_short_prj`, id.`HerbNummerNrDigits`
                                FROM `tbl_specimens` s
                                 LEFT JOIN `tbl_management_collections` mc ON mc.`collectionID` = s.`collectionID`
                                 LEFT JOIN `tbl_img_definition` id ON id.`source_id_fk` = mc.`source_id`
                                WHERE s.`specimen_ID` = :specimenID";
        $result = $this->entityManager->getConnection()->executeQuery($sql, ['specimenID' => $specimenID])->fetchAssociative();

        // Fetch information for this image
        if ($result) {
            // Remove hyphens
            $HerbNummer = str_replace('-', '', $result['HerbNummer']);

            // Construct clean filename
            if (!empty($result['picture_filename'])) {   // special treatment for this collection is necessary
                $parts = $this-> parser($result['picture_filename']);
                $filename = '';
                foreach ($parts as $part) {
                    if ($part['token']) {
                        $tokenParts = explode(':', $part['text']);
                        $token = $tokenParts[0];
                        switch ($token) {
                            case 'coll_short_prj':                                      // use contents of coll_short_prj
                                $filename .= $result['coll_short_prj'];
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
                                    $filename .= sprintf("%0" . $result['HerbNummerNrDigits'] . ".0f", $number);
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
                $filename = sprintf("%s_%0" . $result['HerbNummerNrDigits'] . ".0f", $result['coll_short_prj'], $HerbNummer);
            }

            return $filename;
        } else {
            return "";
        }
    }

    /**
     * create image manifest as an array for a given image filename and server-ID with data from a Cantaloupe-Server extended with a Djatoka-Interface
     *
     * @param int $server_id ID of image server
     * @param string $filename name of image file
     * @return array manifest metadata
     */
    public function createManifestFromExtendedCantaloupeImage(int $server_id, string $identifier)
    {
        // check if this image identifier is already part of a specimen and return the correct manifest if so
        $sql = "SELECT specimen_ID
                  FROM herbar_pictures.djatoka_images
                  WHERE server_id = :server_id
                   AND filename = :identifier";
        $djatokaImage = $this->entityManager->getConnection()->executeQuery($sql, ['server_id' => $server_id,'identifier' => $identifier])->fetchAssociative();

        if (!empty($djatokaImage['specimen_ID'])) {  // we've hit an already existing specimen
            return $this->getManifest($djatokaImage['specimen_ID']);
        } else {
            $urlmanifestpre = $this->router->generate('services_rest_iiif_createManifest', ['serverID' => $server_id, 'imageIdentifier'=>$identifier], UrlGeneratorInterface::ABSOLUTE_URL);
            $result = $this->createManifestFromExtendedCantaloupe($server_id, $identifier, $urlmanifestpre);
            if (!empty($result)) {
                $result['@id'] = $urlmanifestpre;  // to point at ourselves
            }

            return $result;
        }
    }

    /**
     * create image manifest as an array for a given specimen with data from a Cantaloupe-Server extended with a Djatoka-Interface
     *
     * @param int $specimenID specimen-ID
     * @return array manifest metadata
     */
    protected function createManifestFromExtendedCantaloupe(int $server_id, string $identifier, string $urlmanifestpre)
    {
        $sql = "SELECT iiif.manifest_backend, img.imgserver_url, img.key
                                   FROM tbl_img_definition img
                                    LEFT JOIN herbar_pictures.iiif_definition iiif ON iiif.source_id_fk = img.source_id_fk
                                   WHERE img.img_def_ID = :server_id";
        $imgServer = $this->entityManager->getConnection()->executeQuery($sql, ['server_id' => $server_id])->fetchAssociative();

        if (empty($imgServer['manifest_backend'])) {
            return array();  // nothing found
        }

        // ask the enhanced djatoka server for resources with metadata
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
        curl_setopt($curl, CURLOPT_URL, substr($imgServer['manifest_backend'],5));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );

        $curl_response = curl_exec($curl);
        if($curl_response === false) {
            return [];
        }
        $obj = json_decode($curl_response, TRUE);
        curl_close($curl);

        if (empty($obj['result'])) {
            return array();  // nothing found
        }

        $result['@context'] = array('http://iiif.io/api/presentation/2/context.json',
            'http://www.w3.org/ns/anno.jsonld');
        //$result['@id']      = $urlmanifestpre.$urlmanifestpost;
        $result['@type']      = 'sc:Manifest';
        //$result['label']      = $specimenID;
        $canvases = array();
        for ($i = 0; $i < count($obj['result']); $i++) {
            $canvases[] =  array(
                '@id'    => $urlmanifestpre.'/c/'.$identifier.'_'.$i,
                '@type'  => 'sc:Canvas',
                'label'  =>  $obj['result'][$i]["identifier"],
                'height' =>  $obj['result'][$i]["height"],
                'width'  =>  $obj['result'][$i]["width"],
                'images' => array(
                    array(
                        '@id'        => $urlmanifestpre.'/i/'.$identifier.'_'.$i,
                        '@type'      => 'oa:Annotation',
                        'motivation' => 'sc:painting',
                        'on'         => $urlmanifestpre.'/c/'.$identifier.'_'.$i,
                        'resource'   => array(
                            '@id'     => $imgServer['imgserver_url'] . str_replace('/','!', substr($obj['result'][$i]["path"], 1)),
                            '@type'   => 'dctypes:Image',
                            'format'  => (((new \SplFileInfo($obj['result'][$i]['path']))->getExtension() == 'jp2') ? 'image/jp2' : 'image/jpeg'),
                            'height'  => $obj['result'][$i]["height"],
                            'width'   => $obj['result'][$i]["width"],
                            'service' => array(
                                '@context' => 'http://iiif.io/api/image/2/context.json',
                                '@id'      => $imgServer['imgserver_url'] . str_replace('/', '!', substr($obj['result'][$i]["path"], 1)),
                                'profile'  => 'http://iiif.io/api/image/2/level2.json',
                                'protocol' => 'http://iiif.io/api/image'
                            ),
                        ),
                    ),
                )
            );
        }
        $sequences = array(
            '@id'              => $urlmanifestpre.'#sequence-1',
            '@type'            => 'sc:Sequence',
            'canvases'         => $canvases,
            'label'            => 'Current order',
            'viewingDirection' => 'left-to-right'
        );
        $result['sequences'] = array($sequences);

        $result['thumbnail'] = array(
            '@id'     => $imgServer['imgserver_url'] . str_replace('/','!', substr($obj['result'][0]["path"],1)).'/full/400,/0/default.jpg',
            '@type'   => 'dctypes:Image',
            'format'  => 'image/jpeg',
            'service' => array(
                '@context' => 'http://iiif.io/api/image/2/context.json',
                '@id'      => $imgServer['imgserver_url'] . str_replace('/','!', substr($obj['result'][0]["path"],1)),
                'profile'  => 'http://iiif.io/api/image/2/level2.json',
                'protocol' => 'http://iiif.io/api/image'
            ),
        );

        return $result;
    }

    /**
     * get array of metadata for a given specimen, where values are not empty
     *
     * @param SpecimenMapper $specimen specimen to get metadata from
     * @param array $metadata already existing metadata in manifest (optional)
     * @return array metadata
     */
    protected function getMetadataWithValues(SpecimenMapper $specimen, array $metadata = array()): array
    {
        $meta = $this->getMetadata($specimen, $metadata);
        $result = array();
        foreach ($meta as $row) {
            if (!empty($row['value'])) {
                $result[] = $row;
            }
        }
        return $result;
    }

    /**
     * get array of metadata for a given specimen
     *
     * @param SpecimenMapper $specimen specimen to get metadata from
     * @param array $metadata already existing metadata in manifest (optional)
     * @return array metadata
     */
    protected function getMetadata(SpecimenMapper $specimen, array $metadata = array()): array
    {
        $meta = $metadata;

        $dcData = $specimen->getDC();
        foreach ($dcData as $label => $value) {
            $meta[] = array('label' => $label,
                'value' => $value);
        }

        $dwcData = $specimen->getDWC();
        foreach ($dwcData as $label => $value) {
            $meta[] = array('label' => $label,
                'value' => $value);
        }

        $specimenProperties = $specimen->getProperties();

        $meta[] = array('label' => 'CETAF_ID',          'value' => $specimenProperties['stableIdentifier']);
        $meta[] = array('label' => 'dwciri:recordedBy', 'value' => $specimenProperties['WIKIDATA_ID']);
        if (!empty($specimenProperties['HUH_ID'])) {
            $meta[] = array('label' => 'owl:sameAs', 'value' => $specimenProperties['HUH_ID']);
        }
        if (!empty($specimenProperties['VIAF_ID'])) {
            $meta[] = array('label' => 'owl:sameAs', 'value' => $specimenProperties['VIAF_ID']);
        }
        if (!empty($specimenProperties['ORCID'])) {
            $meta[] = array('label' => 'owl:sameAs', 'value' => $specimenProperties['ORCID']);
        }
        if (!empty($specimenProperties['WIKIDATA_ID'])) {
            $meta[] = array('label' => 'owl:sameAs', 'value' => $specimenProperties['WIKIDATA_ID']);
            $meta[] = array('label' => 'owl:sameAs', 'value' => "https://scholia.toolforge.org/author/" . basename($specimenProperties['WIKIDATA_ID']));
        }

        foreach ($meta as $key => $line) {
            if ($line['value'] !== null && (str_starts_with((string)$line['value'], 'http://') || str_starts_with((string)$line['value'], 'https://'))) {
                $meta[$key]['value'] = "<a href='" . $line['value'] . "'>" . $line['value'] . "</a>";
            }
        }

        return $meta;
    }

}
