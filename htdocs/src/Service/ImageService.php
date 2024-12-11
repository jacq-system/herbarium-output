<?php declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class ImageService
{
    public function __construct(protected EntityManagerInterface $entityManager, protected HttpClientInterface $client)
    {
    }

    /**
     * get all details of a given picture
     *
     * @param mixed $id either the specimen_ID or the wanted filename
     * @param string $specimenId specimenID (optional, default=empty)
     */
    public function getPicDetails(string $id, ?string $specimenId = null):array
    {
         $originalFilename = null;

            //specimenid
            if (is_numeric($id)) {
                // request is numeric
                $specimenID = $id;
            } else if (str_contains($id, 'tab_')) {
                // request is a string and contains "tab_" at the beginning
                $result = preg_match('/tab_((?P<specimenID>\d+)[\._]*(.*))/', $id, $matches);
                if ($result == 1) {
                    $specimenID = $matches['specimenID'];
                }
                $originalFilename = $id;
            } else if (str_contains($id, 'obs_')) {
                // request is a string and contains "obs_" at the beginning
                $result = preg_match('/obs_((?P<specimenID>\d+)[\._]*(.*))/', $id, $matches);
                if ($result == 1) {
                    $specimenID = $matches['specimenID'];
                }
                $originalFilename = $id;
            } else {
                // anything else
                $originalFilename = $id;
                $matches = array();
                // Remove file-extension
                if (preg_match('/([^\.]+)/', $id, $matches) > 0) {
                    $originalFilename = $matches[1];
                }

                if (!empty($sid) && intval($sid)) {
                    // we've got a specimen-ID, so use it
                    $specimenID = intval($sid);
                } else {
                    // no specimen-ID included in call, so use old method and try to find one via HerbNummer
                    if (str_starts_with($originalFilename, 'KIEL')) {
                        // source_id 59 uses no "_" between coll_short_prj and HerbNummer (see also line 149)
                        $coll_short_prj = 'KIEL';
                        preg_match('/^([^_]+)/', substr($originalFilename, 4), $matches);
                        $HerbNummer = $matches[1];
                        $HerbNummerAlternative = substr($HerbNummer, 0, 4) . '-' . substr($HerbNummer, 4);
                    } elseif (str_starts_with($originalFilename, 'FT')) {
                        // source_id 47 uses no "_" between coll_short_prj and HerbNummer (see also line 149)
                        $coll_short_prj = 'FT';
                        preg_match('/^([^_]+)/', substr($originalFilename, 2), $matches);
                        $HerbNummer = $matches[1];
                        $HerbNummerAlternative = substr($HerbNummer, 0, 2) . '-' . substr($HerbNummer, 4);
                    } else {
                        // Extract HerbNummer and coll_short_prj from filename and use it for finding the specimen_ID
                        if (preg_match('/^([^_]+)_([^_]+)/', $originalFilename, $matches) > 0) {
                            // Extract HerbNummer and construct alternative version
                            $coll_short_prj = $matches[1];
                            $HerbNummer = $matches[2];
                            $HerbNummerAlternative = substr($HerbNummer, 0, 4) . '-' . substr($HerbNummer, 4);
                        } else {
                            $coll_short_prj = '';
                            $HerbNummer = $HerbNummerAlternative = 0;  // nothing found
                        }
                    }
                    if ($HerbNummer) {
                        // Find entry in specimens table and return specimen ID for it
                        $sql = "SELECT s.`specimen_ID`
                        FROM `tbl_specimens` s
                         LEFT JOIN `tbl_management_collections` mc ON mc.`collectionID` = s.`collectionID`
                        WHERE (   s.`HerbNummer` = :HerbNummer
                               OR s.`HerbNummer` = :HerbNummerAlternative
                               OR (mc.source_id = 6
                                   AND (   s.`CollNummer` = :HerbNummer
                                        OR s.`CollNummer` = :HerbNummerAlternative
                                   ))
                                )
                         AND mc.`coll_short_prj` = :$coll_short_prj";
                        $result = $this->entityManager->getConnection()->executeQuery($sql, ['HerbNummer' => $HerbNummer, 'HerbNummerAlternative'=>$HerbNummerAlternative, 'coll_short_prj'=>$coll_short_prj])->fetchOne();

                        if ($result!== false) {
                            $specimenID = $result;
                        }
                    }
                }
            }

            $sql = "SELECT id.`imgserver_url`, id.`imgserver_type`, id.`HerbNummerNrDigits`, id.`key`, id.`iiif_capable`,
                   mc.`coll_short_prj`, mc.`source_id`, mc.`collectionID`, mc.`picture_filename`,
                   s.`HerbNummer`, s.`Bemerkungen`
            FROM `tbl_specimens` s
             LEFT JOIN `tbl_management_collections` mc ON mc.`collectionID` = s.`collectionID`
             LEFT JOIN `tbl_img_definition` id ON id.`source_id_fk` = mc.`source_id`
            WHERE s.`specimen_ID` = :specimenID";
        $row = $this->entityManager->getConnection()->executeQuery($sql, ['specimenID' => $specimenID])->fetchAssociative();

            // Fetch information for this image
            if ($row!==false) {
                $url = $row['imgserver_url'];

                // Remove hyphens
                $HerbNummer = str_replace('-', '', $row['HerbNummer']);



                // Construct clean filename
                if ($row['imgserver_type'] == 'bgbm') {
                    // Remove spaces for B HerbNumber
                    $HerbNummer = ($row['HerbNummer']) ?: ('JACQID' . $specimenID);
                    $HerbNummer = str_replace(' ', '', $HerbNummer);
                    $filename = sprintf($HerbNummer);
                    $key = $row['key'];
                } elseif ($row['imgserver_type'] == 'baku') {       // depricated
                    $html = $row['Bemerkungen'];

                    // fetch image uris
                    try {
                        $uris = $this->fetchUris($html);
                    } catch (\Exception $e) {
                        echo 'an error occurred: ', $e->getMessage(), "\n";
                        die();
                    }

                    // do something with uris
                    foreach ($uris as $uriSubset) {
                        $newHtmlCode = '<a href="' . $uriSubset["image"] . '" target="_blank"><img src="' . $uriSubset["preview"] . '"/></a>';
                    }

                    $url = $uriSubset["base"];
                    #$url .= ($row['img_service_directory']) ? '/' . $row['img_service_directory'] . '/' : '';
                    if (!str_ends_with($url, '/')) {
                        $url .= '/';  // to ensure that $url ends with a slash
                    }
                    $filename = $uriSubset["filename"];
                    $originalFilename = $uriSubset["thumb"];
                    $key = $uriSubset["html"];
                } else {
                    if ($row['collectionID'] == 90 || $row['collectionID'] == 92 || $row['collectionID'] == 123) { // w-krypt needs special treatment
                        /* TODO
                         * specimens of w-krypt are currently under transition from the old numbering system (w-krypt_1990-1234567) to the new
                         * numbering system (w_1234567). During this time, new HerbNumbers are given to the specimens and the entries
                         * in tbl_specimens are changed accordingly.
                         * So, this script should first look for pictures, named after the new system before searching for pictures, named after the old system
                         * When the transition is finished, this code-part (the whole elseif-block) should be removed
                         * Johannes Schachner, 25.9.2021
                         */
                        $sql = "SELECT filename
                                         FROM herbar_pictures.djatoka_images
                                         WHERE specimen_ID = :specimenID
                                          AND filename LIKE 'w\_%'
                                         ORDER BY filename
                                         LIMIT 1";
                        $image = $this->entityManager->getConnection()->executeQuery($sql, ['specimenID' => $specimenID])->fetchOne();

                        $filename = (!empty($image)) ? $image['filename'] : sprintf("w-krypt_%0" . $row['HerbNummerNrDigits'] . ".0f", $HerbNummer);
                        // since the Services of the W-Pictureserver anren't reliable, we use the database instead

                    } elseif (!empty($row['picture_filename'])) {   // special treatment for this collection is necessary
                        $parts = $this->parser($row['picture_filename']);
                        $filename = '';
                        foreach ($parts as $part) {
                            if ($part['token']) {
                                $tokenParts = explode(':', $part['text']);
                                $token = $tokenParts[0];
                                switch ($token) {
                                    case 'coll_short_prj':                                      // use contents of coll_short_prj
                                        $filename .= $row['coll_short_prj'];
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
                                            $filename .= sprintf("%0" . $row['HerbNummerNrDigits'] . ".0f", $number);
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
                        $filename = sprintf("%s_%0" . $row['HerbNummerNrDigits'] . ".0f", $row['coll_short_prj'], $HerbNummer);
                    }
                    $key = $row['key'];
                }

                // Set original file-name if we didn't pass one (required for djatoka)
                // (required for pictures with suffixes)
                if ($originalFilename == null) {
                    $originalFilename = $filename;
                }

                return array(
                    'url'              => $url,
                    'requestFileName'  => $id,
                    'originalFilename' => str_replace('-', '', $originalFilename),
                    'filename'         => $filename,
                    'specimenID'       => $specimenID,
                    'imgserver_type'   => (($row['iiif_capable']) ? 'iiif' : $row['imgserver_type']),
                    'key'              => $key
                );
            } else {
                return array(
                    'url'              => null,
                    'requestFileName'  => null,
                    'originalFilename' => null,
                    'filename'         => null,
                    'specimenID'       => null,
                    'imgserver_type'   => null,
                    'key'              => null
                );
            }
        }

    // extracts URIs from HTML code like <a href="http://...">image|</a>
    // returns array with URIs which were found
    protected function extractObjectUrisFromHtml($html)
    {
        preg_match_all("/<a[^>]+href=\"([^\"]+)\"[^>]*>[^<]*image[^<]*<\/a>/i", $html, $matches, PREG_PATTERN_ORDER);

        return $matches[1];
    }

    // extracts image and preview URI parts from HTML website
    protected function extracImageUriPartsFromHtml($html)
    {
        preg_match_all("/<div class=\"item\">[^<]*<a[^>]+href=\"([^\"]+)\"[^>]*>[^<]*<img[^>]+src=\"([^\"]+)\"[^>]*\/>[^<]*<\/a>([^<]|\n|\r)*<\/div>/ims", $html, $matches, PREG_PATTERN_ORDER);
        $result = array();
        foreach ($matches[1] as $key => $value) {
            $imageset = array("image" => $matches[1][$key], "preview" => $matches[2][$key]);
            array_push($result, $imageset);
        }
        return $result;
    }

    protected function generateUrisFromParts($objectUri, $uriParts)
    {
        $result = array();
        $parsed = parse_url($objectUri);
        $parsed["path"] = "";
        $parsed["query"] = "";
        $parsed["fragment"] = "";
        $baseUri = $parsed["scheme"] . "://" . $parsed["host"];

        foreach ($uriParts as $value) {
            $imageset = array(
                "html" => $objectUri,
                "image" => $baseUri . $value["image"],
                "filename" => $value["image"],
                "thumb" => $value["preview"],
                "preview" => $baseUri . $value["preview"],
                "base" => $baseUri
            );
            array_push($result, $imageset);
        }

        return $result;

    }

    protected function fetch($uri)
    {
        $html = "";
        $statusCode = 0;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $uri);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // return body as string
        $response = curl_exec($curl);
        if (!curl_errno($curl)) {
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
        } else {
            throw new \Exception("Connection failed: " . curl_error($curl));
        }

        if ($statusCode == 404) {
            $html = "";
        } else if ($statusCode == 200) {
            $html = $response;
        } else {
            // unknown response
            throw new \Exception("unknown response (responseCode=" . $response->responseCode . ")");
        }

        return $html;
    }

    // fetches and extracts URIs
    // returns associative array
    protected function fetchUris($html)
    {
        $imagesets = array();
        $uris = $this->extractObjectUrisFromHtml($html);
        foreach ($uris as $uri) {
            $html = $this->fetch($uri);
            $parts = $this->extracImageUriPartsFromHtml($html);
            $newImagesets = $this->generateUrisFromParts($uri, $parts);
            $imagesets = array_merge($imagesets, $newImagesets);
        }
        return $imagesets;
    }


    /**
     * parse text into parts and tokens (text within '<>')
     *
     * @param string $text text to tokenize
     * @return array found parts
     */
    protected function parser ($text)
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

    function doRedirectShowPic($picdetails): string
    {
        if ($picdetails['imgserver_type'] == 'iiif') {
            $url = "https://services.jacq.org/jacq-services/rest/images/show/{$picdetails['specimenID']}?withredirect=1";
        } elseif ($picdetails['imgserver_type'] == 'djatoka') {
            // Get additional identifiers (if available)
            $picinfo = $this->getPicInfo($picdetails);
            $identifiers = implode(',', $picinfo['pics']);

            // Construct URL to viewer
            if (in_array($picdetails['originalFilename'], $picinfo['pics'])) {
                // the filename is in the list returend by the picture-server
                $url = $picdetails['url'] . '/jacq-viewer/viewer.html?rft_id=' . $picdetails['originalFilename'] . '&identifiers=' . $identifiers;
            } elseif (!empty($identifiers)) {
                // the filename is not in the list, but there is a list
                $url = $picdetails['url'] . '/jacq-viewer/viewer.html?rft_id=' . $picinfo['pics'][0] . '&identifiers=' . $identifiers;
            } else {
                // the picture-server didn't respond or the returned list is empty, so we guess a name...
                $url = $picdetails['url'] . '/jacq-viewer/viewer.html?rft_id=' . $picdetails['originalFilename'] . '&identifiers=' . $picdetails['originalFilename'];
            }
        } elseif ($picdetails['imgserver_type'] == 'bgbm') {
            // Construct URL to viewer
            $url = $picdetails['url'] . '/jacq_image.cfm?Barcode=' . $picdetails['originalFilename'];
        } elseif ($picdetails['imgserver_type'] == 'baku') {  // depricated
            // Get additional identifiers (if available)
            //$picinfo = getPicInfo($picdetails);
            //$identifiers = implode($picinfo['pics'], ',');
            // Construct URL to viewer

            $url = $picdetails['key'];
        } else {                                               // depricated
            $q = '';
            foreach ($_GET as $k => $v) {
                if (in_array($k, array('method', 'filename', 'format')) === false) {
                    $q .= "&{$k}=" . rawurlencode($v);
                }
            }
            $url = $picdetails['url'] . 'img/imgBrowser.php?name=' . $picdetails['requestFileName'] . $q;
        }

        return $url;
    }

    /**
     * ask the picture server for information about pictures
     * in case of error an additional field "error" is filled in the array
     *
     * @param array $picdetails result of getPicDetails
     * @return array decoded response of the picture server
     */
    function getPicInfo($picdetails)
    {
        $return = array('output' => '',
            'pics'   => array(),
            'error'  => '');

        if ($picdetails['imgserver_type'] == 'djatoka') {
            // Construct URL to servlet
            $url = $picdetails['url'] . 'jacq-servlet/ImageServer';

            // Create a client instance and send requests to jacq-servlet
            try {
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
                    'verify' => false
                ]);
                $data = json_decode($response1->getContent(), true);
                if (!empty($data['result'])) {
                    $return['pics'] = $data['result'];
                }
                if (!empty($data['error'])) {
                    throw new \Exception($data['error']);
                } elseif (empty($data['result'][0])) {
                    throw new \Exception("FAIL: '{$picdetails['filename']}' returned empty result");
                }
                // since the error-response is JSON-RPC v.1 instead ov v.2.0 we can't use this client
//            $service = new JsonRPC\Client($url);
//            $return['pics'] = $service->execute('listResources',
//                                                [
//                                                    $picdetails['key'],
//                                                    [
//                                                        $picdetails['filename'],
//                                                        $picdetails['filename'] . "_%",
//                                                        $picdetails['filename'] . "A",
//                                                        $picdetails['filename'] . "B",
//                                                        "tab_" . $picdetails['specimenID'],
//                                                        "obs_" . $picdetails['specimenID'],
//                                                        "tab_" . $picdetails['specimenID'] . "_%",
//                                                        "obs_" . $picdetails['specimenID'] . "_%"
//                                                    ]
//                                                ]);
            }
            catch( \Exception $e ) {
                $return['error'] = 'Unable to connect to ' . $url . " with Error: " . $e->getMessage();
            }

            /*
            // Prepare json-rpc conform request structure
            $jsonrpc_request = json_encode(array(
                'id' => 1234,
                'method' => 'listSpecimenImages',
                'params' => array($picdetails['key'], $picdetails['specimenID'], $picdetails['filename'])
            ));

            // Prepare the context for the HTTP request
            $opts = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-type: application/json',
                    'content' => $jsonrpc_request
                )
            );
            $context = stream_context_create($opts);

            // Finally try to reach the djatoka server and ask for details
            if (($fp = fopen($url, 'r', false, $context))) {
                $response = '';
                while ($row = fgets($fp)) {
                    $response .= trim($row) . "\n";
                }
                $response_decoded = json_decode($response, true);

                $return['pics'] = $response_decoded['result'];
            } else {
                $return['error'] = 'Unable to connect to ' . $url;
            }
            */

            // finally add any old filenames which are in "herbar_pictures.djatoka_images" but not already in the list
            if (!empty($return['pics'])) {
                $sql = "SELECT filename
                                    FROM herbar_pictures.djatoka_images
                                    WHERE specimen_ID = :specimen
                                     AND filename NOT IN (:excluded)";
                $rows = $this->entityManager->getConnection()->executeQuery($sql, ['specimen' => $picdetails['specimenID'], 'excluded' => $return['pics']])->fetchAllAssociative();
            } else {
                $sql = "SELECT filename
                                    FROM herbar_pictures.djatoka_images
                                    WHERE specimen_ID = :specimen";
                $rows = $this->entityManager->getConnection()->executeQuery($sql, ['specimen' => $picdetails['specimenID']])->fetchAllAssociative();
            }
            if (!empty($rows)) {
                foreach($rows as $row) {
                    $return['pics'][] = $row['filename'];
                }
            }
        } else if ($picdetails['imgserver_type'] == 'bgbm') {
            // Construct URL to servlet
            $HerbNummer = str_replace('-', '', $picdetails['filename']);

            $url = 'http://ww2.bgbm.org/rest/herb/thumb/' . $HerbNummer;

            $fp = fopen($url, "r");
            if ($fp) {
                $response = '';
                while ($row = fgets($fp)) {
                    $response .= trim($row) . "\n";
                }
                $response_decoded = json_decode($response, true);
                $return['pics'] = $response_decoded['result'];
                fclose($fp);
            }
        } else if ($picdetails['imgserver_type'] == 'iiif') {   // should never be reached...
            // so, do nothing, just return
        } else if ($picdetails['imgserver_type'] == 'baku') {   // depricated
            $return['pics'] = $picdetails['filename'];
        } else {  // old legacy, depricated
            $url = "{$picdetails['url']}/detail_server.php?key=DKsuuewwqsa32czucuwqdb576i12&ID={$picdetails['specimenID']}";

            $response = file_get_contents($url);
            $response_decoded = unserialize($response);

            $return = array('output' => $response_decoded['output'],
                'pics'   => $response_decoded['pics'],
                'error'  => '');
        }

        return $return;
    }
}
