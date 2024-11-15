<?php declare(strict_types=1);

namespace App\Service;


use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class SpecimenMapper
{

    protected int $specimenID = 0;
    protected bool $isValid = false;

    /** holds the specimen properties     */
    protected array $properties = array();
    public function __construct(protected readonly Connection $connection, int $specimenID, protected readonly RouterInterface $router)
    {

        if ($specimenID == 0) {
            throw new \InvalidArgumentException('Specimen ID cannot be empty');
        }
        $this->specimenID = $specimenID;

        /**
         * first get the stable identifier
         */
        $sql = "SELECT stableIdentifier
                                 FROM tbl_specimens_stblid
                                 WHERE specimen_ID = :specimenID
                                 ORDER BY timestamp DESC
                                 LIMIT 1";
        $sid = $this->connection->executeQuery($sql, ['specimenID' => $this->specimenID])->fetchOne();

        $this->properties['stableIdentifier'] = $sid ?? '';

        /**
         * then get all other properties of the specimen if it is accessible
         */
        $sql = "SELECT herbar_view.GetScientificName(s.taxonID, 0) AS sciName, tf.family, tg.genus, te.epithet,
                              s.HerbNummer, s.CollNummer, s.observation, s.Datum, s.Datum2, s.taxon_alt, s.Fundort, s.Nummer, s.alt_number,
                              s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec, s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
                              s.digital_image, s.digital_image_obs,
                              c.Sammler, c.WIKIDATA_ID, c.HUH_ID, c.VIAF_ID, c.ORCID, c2.Sammler_2,
                              md.OwnerOrganizationName, md.OwnerOrganizationAbbrev, md.OwnerLogoURI, md.LicenseURI,
                              ss.series,
                              gn.nation_engl, gn.iso_alpha_3_code
                             FROM tbl_specimens s
                              LEFT JOIN tbl_collector c               ON c.SammlerID     = s.SammlerID
                              LEFT JOIN tbl_collector_2 c2            ON c2.Sammler_2ID  = s.Sammler_2ID
                              LEFT JOIN tbl_tax_species ts            ON ts.taxonID      = s.taxonID
                              LEFT JOIN tbl_tax_rank ttr              ON ttr.tax_rankID  = ts.tax_rankID
                              LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                              LEFT JOIN metadata md                   ON md.MetadataID   = mc.source_id
                              LEFT JOIN tbl_specimens_series ss       ON ss.seriesID     = s.seriesID
                              LEFT JOIN tbl_tax_epithets te           ON te.epithetID    = ts.speciesID
                              LEFT JOIN tbl_tax_genera tg             ON tg.genID        = ts.genID
                              LEFT JOIN tbl_tax_families tf           ON tf.familyID     = tg.familyID
                              LEFT JOIN tbl_geo_nation gn             ON gn.nationID     = s.NationID
                             WHERE s.specimen_ID = :specimenID
                              AND s.`accessible` != '0'";
        $row = $this->connection->executeQuery($sql, ['specimenID' => $this->specimenID])->fetchAssociative();

        if (!empty($row)) {
            $this->isValid = true;  // we have found valid data
            /**
             * do any neccessary calculations
             */
            /**
             * CollectorTeam
             */
            $collectorTeam = $row['Sammler'];
            if ($row['Sammler_2']!== null && (strstr($row['Sammler_2'], "et al.") || strstr($row['Sammler_2'], "alii"))) {
                $collectorTeam .= " et al.";
            } elseif ($row['Sammler_2']) {
                $parts = explode(',', $row['Sammler_2']);           // some people forget the final "&"
                if (count($parts) > 2) {                            // so we have to use an alternative way
                    $collectorTeam .= ", " . $row['Sammler_2'];
                } else {
                    $collectorTeam .= " & " . $row['Sammler_2'];
                }
            }
            /**
             * created
             */
            if (trim($row['Datum']) == "s.d.") {
                $created = '';
            } else {
                $created = trim($row['Datum']);
                if ($created) {
                    if (trim($row['Datum2'])) {
                        $created .= " - " . trim($row['Datum2']);
                    }
                } else {
                    $created = trim($row['Datum2']);
                }
            }

            if ($row['Coord_S'] > 0 || $row['S_Min'] > 0 || $row['S_Sec'] > 0) {
                $decimalLatitude  = -round($row['Coord_S'] + $row['S_Min'] / 60 + $row['S_Sec'] / 3600, 5);
                $verbatimLatitude = $row['Coord_S'] . "d " . (($row['S_Min']) ?: '?') . "m " . (($row['S_Sec']) ?: '?') . 's S';
            } else if ($row['Coord_N'] > 0 || $row['N_Min'] > 0 || $row['N_Sec'] > 0) {
                $decimalLatitude  = round($row['Coord_N'] + $row['N_Min'] / 60 + $row['N_Sec'] / 3600, 5);
                $verbatimLatitude = $row['Coord_N'] . "d " . (($row['N_Min']) ?: '?') . "m " . (($row['N_Sec']) ?: '?') . 's N';
            } else {
                $decimalLatitude  = null;
                $verbatimLatitude = '';
            }
            if ($row['Coord_W'] > 0 || $row['W_Min'] > 0 || $row['W_Sec'] > 0) {
                $decimalLongitude  = -round($row['Coord_W'] + $row['W_Min'] / 60 + $row['W_Sec'] / 3600, 5);
                $verbatimLongitude = $row['Coord_W'] . "d " . (($row['W_Min']) ?: '?') . "m " . (($row['W_Sec']) ?: '?') . 's W';
            } else if ($row['Coord_E'] > 0 || $row['E_Min'] > 0 || $row['E_Sec'] > 0) {
                $decimalLongitude  = round($row['Coord_E'] + $row['E_Min'] / 60 + $row['E_Sec'] / 3600, 5);
                $verbatimLongitude = $row['Coord_E'] . "d " . (($row['E_Min']) ?: '?') . "m " . (($row['E_Sec']) ?: '?') . 's E';
            } else {
                $decimalLongitude  = null;
                $verbatimLongitude = '';
            }


            if (!empty($row['digital_image']) || !empty($row['digital_image_obs'])) {
                $firstImageLink = $this->router->generate('services_rest_images_show', ['specimenID' => $this->specimenID], UrlGeneratorInterface::ABSOLUTE_URL);
                $firstImageDownloadLink = $this->router->generate('services_rest_images_download', ['specimenID' => $this->specimenID], UrlGeneratorInterface::ABSOLUTE_URL);
            } else {
                $firstImageLink = $firstImageDownloadLink = '';
            }


            /**
             * store all properties
             */
            $this->properties['specimenID']              = $this->specimenID;
            $this->properties['scientificName']          = $row['sciName'];
            $this->properties['family']                  = $row['family'];
            $this->properties['genus']                   = $row['genus'];
            $this->properties['epithet']                 = $row['epithet'];
            $this->properties['HerbNummer']              = $row['HerbNummer'];
            $this->properties['CollNummer']              = $row['CollNummer'];
            $this->properties['observation']             = $row['observation'];
            $this->properties['taxon_alt']               = $row['taxon_alt'];
            $this->properties['Fundort']                 = $row['Fundort'];
            $this->properties['decimalLatitude']         = $decimalLatitude;
            $this->properties['decimalLongitude']        = $decimalLongitude;
            $this->properties['verbatimLatitude']        = $verbatimLatitude;
            $this->properties['verbatimLongitude']       = $verbatimLongitude;
            $this->properties['collectorTeam']           = $collectorTeam;
            $this->properties['created']                 = $created;
            $this->properties['Nummer']                  = $row['Nummer'];
            $this->properties['series']                  = $row['series'];
            $this->properties['alt_number']              = $row['alt_number'];
            $this->properties['WIKIDATA_ID']             = $row['WIKIDATA_ID'];
            $this->properties['HUH_ID']                  = $row['HUH_ID'];
            $this->properties['VIAF_ID']                 = $row['VIAF_ID'];
            $this->properties['ORCID']                   = $row['ORCID'];
            $this->properties['OwnerOrganizationAbbrev'] = $row['OwnerOrganizationAbbrev'];
            $this->properties['OwnerLogoURI']            = $row['OwnerLogoURI'] ?? '';
            $this->properties['LicenseURI']              = $row['LicenseURI'] ?? '';
            $this->properties['nation_engl']             = $row['nation_engl'];
            $this->properties['iso_alpha_3_code']        = $row['iso_alpha_3_code'];
            $this->properties['image']                   = $firstImageLink;
            $this->properties['downloadImage']           = $firstImageDownloadLink;
        }
    }

    /**
     * get all properties of this specimen
     *
     * @return array properties
     */
    public function getProperties(): array
    {
        return $this->properties;
    }



    /**
     * get the description of this specimen
     *
     * @return string description
     */
    public function getDescription(): string
    {
        return "A " . (($this->properties['observation'] > 0) ? "HumanObservation" : "PreservedSpecimen")
            . " of " . $this->properties['scientificName'] . " collected by {$this->properties['collectorTeam']}";
    }

    /**
     * get the label of this specimen
     *
     * @return string label
     */
    public function getLabel(): string
    {
        return $this->properties['scientificName'];
    }

    /**
     * get the attribution of this specimen
     *
     * @return string attribution
     */
    public function getAttribution(): string
    {
        return $this->properties['LicenseURI'];
    }

    /**
     * get the logo URI of this specimen
     *
     * @return string logo URI
     */
    public function getLogoURI(): string
    {
        return $this->properties['OwnerLogoURI'];
    }

    /**
     * get the stable identifier of this specimen
     *
     * @return string stable identifier
     */
    public function getStableIdentifier(): string
    {
        return $this->properties['stableIdentifier'];
    }

    /**
     * get the properties of this specimen with Dublin Core Names (dc:...)
     *
     * @return array dc-data
     */
    public function getDC(): array
    {
        if ($this->isValid) {
            $basisOfRecord = ($this->properties['observation'] > 0) ? "HumanObservation" : "PreservedSpecimen";

            return array('dc:title' => $this->properties['scientificName'],
                'dc:description' => "A $basisOfRecord of " . $this->properties['scientificName'] . " collected by {$this->properties['collectorTeam']}",
                'dc:creator' => $this->properties['collectorTeam'],
                'dc:created' => $this->properties['created'],
                'dc:type' => $basisOfRecord);
        } else {
            return array();
        }
    }

    /**
     * get the properties of this specimen with Darwin Core Names (dwc:...)
     *
     * @return array dwc-data
     */
    public function getDWC(): array
    {
        if ($this->isValid) {
            return array('dwc:materialSampleID' => $this->properties['stableIdentifier'],
                'dwc:basisOfRecord' => ($this->properties['observation'] > 0) ? "HumanObservation" : "PreservedSpecimen",
                'dwc:collectionCode' => $this->properties['OwnerOrganizationAbbrev'],
                'dwc:catalogNumber' => ($this->properties['HerbNummer']) ?: ('JACQ-ID ' . $this->properties['specimenID']),
                'dwc:scientificName' => $this->properties['scientificName'],
                'dwc:previousIdentifications' => $this->properties['taxon_alt'],
                'dwc:family' => $this->properties['family'],
                'dwc:genus' => $this->properties['genus'],
                'dwc:specificEpithet' => $this->properties['epithet'],
                'dwc:country' => $this->properties['nation_engl'],
                'dwc:countryCode' => $this->properties['iso_alpha_3_code'],
                'dwc:locality' => $this->properties['Fundort'],
                'dwc:decimalLatitude' => $this->properties['decimalLatitude'],
                'dwc:decimalLongitude' => $this->properties['decimalLongitude'],
                'dwc:verbatimLatitude' => $this->properties['verbatimLatitude'],
                'dwc:verbatimLongitude' => $this->properties['verbatimLongitude'],
                'dwc:eventDate' => $this->properties['created'],
                'dwc:recordNumber' => ($this->properties['HerbNummer']) ?: ('JACQ-ID ' . $this->properties['specimenID']),
                'dwc:recordedBy' => $this->properties['collectorTeam'],
                'dwc:fieldNumber' => trim($this->properties['Nummer'] . ' ' . $this->properties['alt_number']));
        } else {
            return array();
        }
    }

    /**
     * get the properties of this specimen with JACQ Names (jacq:...)
     *
     * @return array jacq-data
     */
    public function getJACQ(): array
    {
        $result = array();
        foreach ($this->properties as $key => $value) {
            $result["jacq:{$key}"] = $value;
        }

        return $result;
    }

}
