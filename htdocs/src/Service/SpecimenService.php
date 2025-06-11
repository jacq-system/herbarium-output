<?php declare(strict_types=1);

namespace App\Service;


use App\Entity\Jacq\Herbarinput\Specimens;
use App\Repository\Herbarinput\SpecimensRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SpecimenService extends BaseService
{
    public const string JACQID_PREFIX = "JACQID";

    /**
     * get specimen-id of a given stable identifier
     */
    public function findSpecimenIdUsingSid(string $sid): ?int
    {
        $pos = strpos($sid, self::JACQID_PREFIX);
        if ($pos !== false) {  // we've found a sid with JACQID in it, so check the attached specimen-ID and return it, if valid
            $specimenID = intval(substr($sid, $pos + strlen(self::JACQID_PREFIX)));
            $id = $this->findAccessibleForPublic($specimenID)->getId();
        } else {
            $id = $this->findBySid($sid);
        }
        return $id === 0 ? null : $id;
    }

    public function findAccessibleForPublic(int $id): Specimens
    {
        $specimen = $this->entityManager->getRepository(Specimens::class)->findOneBy(["id" => $id, 'accessibleForPublic' => true]);
        if ($specimen === null) {
            throw new EntityNotFoundException('Specimen not found');
        }
        return $specimen;
    }

    public function getRepository(): SpecimensRepository
    {
        return $this->entityManager->getRepository(Specimens::class);
    }

    public function findBySid(string $sid): int
    {
        $sql = "SELECT specimen_ID
                     FROM tbl_specimens_stblid
                     WHERE stableIdentifier = :sid";
        return $this->query($sql, ['sid' => $sid])->fetchOne();
    }

    public function find(int $id): Specimens
    {
        $specimen = $this->entityManager->getRepository(Specimens::class)->find($id);
        if ($specimen === null) {
            throw new EntityNotFoundException('Specimen not found');
        }
        return $specimen;
    }

    /**
     * get a list of all errors which prevent the generation of stable identifier
     */
    public function getEntriesWithErrors(?int $sourceID): array
    {
        $data = [];
        $specimens = $this->specimensWithErrors($sourceID);
        $data['total'] = count($specimens);
        foreach ($specimens as $line => $specimen) {
            $data['result'][$line] = ['specimenID' => $specimen->getId(), 'link' => $this->router->generate('app_front_specimenDetail', ['specimenId' => $specimen->getId()], UrlGeneratorInterface::ABSOLUTE_URL)];
            $data['result'][$line]['errorList'] = $this->sids2array($specimen);
        }

        return $data;
    }

    protected function specimensWithErrors(?int $sourceID): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder = $queryBuilder->select('DISTINCT s')->from(Specimens::class, 's')->join('s.stableIdentifiers', 'sid')->where('sid.identifier IS NULL');

        if ($sourceID !== null) {
            $queryBuilder = $queryBuilder->join('s.herbCollection', 'col')->andWhere('col.institution = :sourceID')->setParameter('sourceID', $sourceID);
        }
        return $queryBuilder->getQuery()->getResult();
    }

    public function sids2array(Specimens $specimen): array
    {
        $ret = [];
        $sids = $specimen->getStableIdentifiers();
        foreach ($sids as $key => $stableIdentifier) {
            $ret[$key]['stableIdentifier'] = $stableIdentifier->getIdentifier();
            $ret[$key]['timestamp'] = $stableIdentifier->getTimestamp()->format('Y-m-d H:i:s');

            if (!empty($stableIdentifier->getError())) {
                $ret[$key]['error'] = $stableIdentifier->getError();

                preg_match("/already exists \((?P<number>\d+)\)$/", $stableIdentifier->getError(), $parts);

                $ret[$key]['link'] = (!empty($parts['number'])) ? $this->router->generate('app_front_specimenDetail', ['specimenId' => $parts['number']], UrlGeneratorInterface::ABSOLUTE_URL) : '';

            }
        }

        return $ret;
    }

    /**
     * get a list of all specimens with multiple stable identifiers of a given source
     */
    public function getMultipleEntriesFromSource(int $sourceID): array
    {
        $sql = "SELECT ss.specimen_ID AS specimenID, count(ss.specimen_ID) AS `numberOfEntries`
                              FROM tbl_specimens_stblid ss
                               JOIN tbl_specimens s ON ss.specimen_ID = s.specimen_ID
                               JOIN tbl_management_collections mc ON s.collectionID = mc.collectionID
                              WHERE ss.stableIdentifier IS NOT NULL
                               AND mc.source_id = :sourceID
                              GROUP BY ss.specimen_ID
                              HAVING numberOfEntries > 1
                              ORDER BY numberOfEntries DESC, specimenID";

        $rows = $this->query($sql, ['sourceID' => $sourceID])->fetchAllAssociative();

        $data = array('total' => count($rows));
        foreach ($rows as $line => $row) {
            $data['result'][$line] = $row;
            $data['result'][$line]['stableIdentifierList'] = $this->getAllStableIdentifiers($row['specimenID']);
        }

        return $data;
    }

    /**
     * get all stable identifiers and their respective timestamps of a given specimen-id
     */
    public function getAllStableIdentifiers(int $specimenID): array
    {
        $specimen = $this->findAccessibleForPublic($specimenID);
        if (empty($specimen->getStableIdentifiers())) {
            return [];
        }
        $ret['latest'] = $this->sid2array($specimen);
        $ret['list'] = $this->sids2array($specimen);
        return $ret;
    }

    public function sid2array(Specimens $specimen): array
    {
        /**
         * SID is assigned asynchron, could happen it does not exists (and the timestamp is not clear))
         */
        return ['stableIdentifier' => $this->getStableIdentifier($specimen), 'timestamp' => $specimen->getMainStableIdentifier()?->getTimestamp()->format('Y-m-d H:i:s'), 'link' => $this->router->generate('app_front_specimenDetail', ['specimenId' => $specimen->getId()], UrlGeneratorInterface::ABSOLUTE_URL)

        ];
    }

    public function getStableIdentifier(Specimens $specimen): string
    {
        if (!empty($specimen->getMainStableIdentifier()?->getIdentifier())) {
            return $specimen->getMainStableIdentifier()->getIdentifier();
        } else {
            return $this->constructStableIdentifier($specimen);
        }

    }

    /**
     * try to construct PID
     */
    public function constructStableIdentifier(Specimens $specimen): string
    {
        $sourceId = $specimen->getHerbCollection()->getId();
        if (!empty($sourceId) && !empty($specimen->getHerbNumber())) {
            $modifiedHerbNumber = str_replace(' ', '', $specimen->getHerbNumber());

            if ($sourceId == '29') { // B
                if (strlen(trim($modifiedHerbNumber)) > 0) {
                    $modifiedHerbNumber = str_replace('-', '', $modifiedHerbNumber);
                } else {
                    $modifiedHerbNumber = self::JACQID_PREFIX . $specimen->getId();
                }
                return "https://herbarium.bgbm.org/object/" . $modifiedHerbNumber;
            } elseif ($sourceId == '27') { // LAGU
                return "https://lagu.jacq.org/object/" . $modifiedHerbNumber;
            } elseif ($sourceId == '48') { // TBI
                return "https://tbi.jacq.org/object/" . $modifiedHerbNumber;
            } elseif ($sourceId == '50') { // HWilling
                if (strlen(trim($modifiedHerbNumber)) > 0) {
                    $modifiedHerbNumber = str_replace('-', '', $modifiedHerbNumber);
                } else {
                    $modifiedHerbNumber = self::JACQID_PREFIX . $specimen->getId();
                }
                return "https://willing.jacq.org/object/" . $modifiedHerbNumber;
            }
        }
        return '';
    }

    /**
     * get a list of all specimens with multiple stable identifiers
     *
     * @param int $page optional page number, defaults to first page
     * @param int $entriesPerPage optional number of items, defaults to 50
     * @return array list of results
     */
    public function getMultipleEntries(int $page = 0, int $entriesPerPage = 50): array
    {
        if ($entriesPerPage <= 0) {
            $entriesPerPage = 50;
        } else if ($entriesPerPage > 100) {
            $entriesPerPage = 100;
        }

        $sql = "SELECT count(*) FROM (SELECT specimen_ID AS specimenID, count(specimen_ID) AS `numberEntries`
                                FROM tbl_specimens_stblid
                                WHERE stableIdentifier IS NOT NULL
                                GROUP BY specimen_ID
                                HAVING numberEntries > 1) AS subquery";
        $rowCount = $this->query($sql)->fetchOne();

        $lastPage = (int)floor(($rowCount - 1) / $entriesPerPage);
        if ($page > $lastPage) {
            $page = $lastPage;
        } elseif ($page < 0) {
            $page = 0;
        }

        $data = array('page' => $page + 1, 'previousPage' => $this->urlHelperRouteMulti((($page > 0) ? ($page - 1) : 0), $entriesPerPage), 'nextPage' => $this->urlHelperRouteMulti((($page < $lastPage) ? ($page + 1) : $lastPage), $entriesPerPage), 'firstPage' => $this->urlHelperRouteMulti(0, $entriesPerPage), 'lastPage' => $this->urlHelperRouteMulti($lastPage, $entriesPerPage), 'totalPages' => $lastPage + 1, 'total' => $rowCount,);
        $offset = ($page * $entriesPerPage);
        $sql = "SELECT specimen_ID AS specimenID, count(specimen_ID) AS `numberOfEntries`
                              FROM tbl_specimens_stblid
                              WHERE stableIdentifier IS NOT NULL
                              GROUP BY specimen_ID
                              HAVING numberOfEntries > 1
                              ORDER BY numberOfEntries DESC, specimenID
                              LIMIT :entriesPerPage OFFSET :offset";

        $rows = $this->query($sql, ["offset" => $offset, "entriesPerPage" => $entriesPerPage], ['offset' => ParameterType::INTEGER, "entriesPerPage" => ParameterType::INTEGER])->fetchAllAssociative();

        foreach ($rows as $line => $row) {
            $data['result'][$line] = $row;
            $data['result'][$line]['stableIdentifierList'] = $this->getAllStableIdentifiers($row['specimenID']);
        }

        return $data;
    }

    protected function urlHelperRouteMulti(int $page, int $entriesPerPage): string
    {
        return $this->router->generate('services_rest_sid_multi', ['page' => $page, 'entriesPerPage' => $entriesPerPage], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * get the specimen-ID of a given HerbNumber and source-id
     */
    public function getSpecimenIdFromHerbNummer(string $herbNummer, int $source_id): ?int
    {
        $sql = "SELECT specimen_ID
                             FROM tbl_specimens s
                              LEFT JOIN tbl_management_collections mc on s.collectionID = mc.collectionID
                             WHERE s.HerbNummer = :herbNummer
                              AND mc.source_id = :source_id";
        $id = $this->query($sql, ["herbNummer" => $herbNummer, "source_id" => $source_id])->fetchOne();

        if ($id !== false) {
            return $id;
        }
        return null;

    }

    public function getCollectionText(Specimens $specimen): string
    {
        $text = $specimen->getCollector()?->getName();
        if (!empty($specimen->getCollector2())) {
            if (strstr($specimen->getCollector2()->getName(), "&") || strstr($specimen->getCollector2()->getName(), "et al.")) {
                $text .= " et al.";
            } else {
                $text .= " & " . $specimen->getCollector2()->getName();
            }
        }

        if (!empty($specimen->getSeriesNumber())) {
            if (!empty($specimen->getNumber())) {
                $text .= " " . $specimen->getNumber();
            }
            if (!empty($specimen->getAltNumber()) && $specimen->getAltNumber() != "s.n.") {
                $text .= " " . $specimen->getAltNumber();
            }
            if (!empty($specimen->getSeries()?->getName())) {
                $text .= " " . $specimen->getSeries()?->getName();
            }
            $text .= " " . $specimen->getSeriesNumber();
        } else {
            if (!empty($specimen->getSeries()?->getName())) {
                $text .= " " . $specimen->getSeries()?->getName();
            }
            if (!empty($specimen->getNumber())) {
                $text .= " " . $specimen->getNumber();
            }
            if (!empty($specimen->getAltNumber()) && $specimen->getAltNumber() != "s.n.") {
                $text .= " " . $specimen->getAltNumber();
            }
        }

        return trim($text);
    }

    /**
     * get the properties of this specimen with Dublin Core Names (dc:...)
     */
    public function getDublinCore(Specimens $specimen): array
    {
        $scientificName = $this->getScientificName($specimen);
        return array('dc:title' => $scientificName,
            'dc:description' => $this->getSpecimenDescription($specimen),
            'dc:creator' => $specimen->getCollectorsTeam(),
            'dc:created' => $specimen->getDatesAsString(),
            'dc:type' => $specimen->getBasisOfRecordField());
    }

    public function getScientificName(Specimens $specimen): string
    {
        $sql = "SELECT herbar_view.GetScientificName(:species, 0) AS scientificName";
        return $this->entityManager->getConnection()->executeQuery($sql, ['species' => $specimen->getSpecies()->getId()])->fetchOne();

    }

    public function getSpecimenDescription(Specimens $specimen): string
    {
        $scientificName = $this->getScientificName($specimen);
        return "A " . $specimen->getBasisOfRecordField() . " of " . $scientificName . " collected by {$specimen->getCollectorsTeam()}";
    }

    /**
     * get the properties of this specimen with Darwin Core Names (dwc:...)
     */
    public function getDarwinCore(Specimens $specimen): array
    {

        return [
            'dwc:materialSampleID' => $this->getStableIdentifier($specimen),
            'dwc:basisOfRecord' => $specimen->getBasisOfRecordField(),
            'dwc:collectionCode' => $specimen->getHerbCollection()->getInstitution()->getAbbreviation(),
            'dwc:catalogNumber' => ($specimen->getHerbNumber()) ?: ('JACQ-ID ' . $specimen->getId()),
            'dwc:scientificName' => $this->getScientificName($specimen),
            'dwc:previousIdentifications' => $specimen->getTaxonAlternative(),
            'dwc:family' => $specimen->getSpecies()->getGenus()->getFamily()->getName(),
            'dwc:genus' => $specimen->getSpecies()->getGenus()->getName(),
            'dwc:specificEpithet' => $specimen->getSpecies()->getEpithetSpecies()?->getName(),
            'dwc:country' => $specimen->getCountry()?->getNameEng(),
            'dwc:countryCode' => $specimen->getCountry()?->getIsoCode3(),
            'dwc:locality' => $specimen->getLocality(),
            'dwc:decimalLatitude' => $specimen->getLatitude() !== null ? round($specimen->getLatitude(), 5) : null,
            'dwc:decimalLongitude' => $specimen->getLongitude() !== null ? round($specimen->getLongitude(), 5) : null,
            'dwc:verbatimLatitude' => $specimen->getVerbatimLatitude(),
            'dwc:verbatimLongitude' => $specimen->getVerbatimLongitude(),
            'dwc:eventDate' => $specimen->getDatesAsString(),
            'dwc:recordNumber' => ($specimen->getHerbNumber()) ?: ('JACQ-ID ' . $specimen->getId()),
            'dwc:recordedBy' => $specimen->getCollectorsTeam(),
            'dwc:fieldNumber' => trim($specimen->getNumber() . ' ' . $specimen->getAltNumber())
        ];

    }

    /**
     * get the properties of this specimen with JACQ Names (jacq:...)
     */
    public function getJACQ(Specimens $specimen): array
    {
//TODO - using german terms as identifiers - but probably nobody use this service

        if ($specimen->hasImage() || $specimen->hasImageObservation()) {
            $firstImageLink = $this->router->generate('services_rest_images_show', ['specimenID' => $specimen->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $firstImageDownloadLink = $this->router->generate('services_rest_images_download', ['specimenID' => $specimen->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        } else {
            $firstImageLink = $firstImageDownloadLink = '';
        }

        return [
            'jacq:stableIdentifier' => $this->getStableIdentifier($specimen),
            'jacq:specimenID' => $specimen->getId(),
            'jacq:scientificName' => $this->getScientificName($specimen),
            'jacq:family' => $specimen->getSpecies()->getGenus()->getFamily()->getName(),
            'jacq:genus' => $specimen->getSpecies()->getGenus()->getName(),
            'jacq:epithet' => $specimen->getSpecies()->getEpithetSpecies()->getName(),
            'jacq:HerbNummer' => $specimen->getHerbNumber(),
            'jacq:CollNummer' => $specimen->getCollectionNumber(),
            'jacq:observation' => $specimen->isObservation() ? '1' : '0',
            'jacq:taxon_alt' => $specimen->getTaxonAlternative(),
            'jacq:Fundort' => $specimen->getLocality(),
            'jacq:decimalLatitude' => $specimen->getLatitude(),
            'jacq:decimalLongitude' => $specimen->getLongitude(),
            'jacq:verbatimLatitude' => $specimen->getVerbatimLatitude(),
            'jacq:verbatimLongitude' => $specimen->getVerbatimLongitude(),
            'jacq:collectorTeam' => $specimen->getCollectorsTeam(),
            'jacq:created' => $specimen->getDatesAsString(),
            'jacq:Nummer' => $specimen->getNumber(),
            'jacq:series' => $specimen->getSeries()?->getName(),
            'jacq:alt_number' => $specimen->getAltNumber(),
            'jacq:WIKIDATA_ID' => $specimen->getCollector()->getWikidataId(),
            'jacq:HUH_ID' => $specimen->getCollector()->getHuhId(),
            'jacq:VIAF_ID' => $specimen->getCollector()->getViafId(),
            'jacq:ORCID' => $specimen->getCollector()->getOrcidId(),
            'jacq:OwnerOrganizationAbbrev' => $specimen->getHerbCollection()->getInstitution()->getAbbreviation(),
            'jacq:OwnerLogoURI' => $specimen->getHerbCollection()->getInstitution()->getOwnerLogoUri(),
            'jacq:LicenseURI' => $specimen->getHerbCollection()->getInstitution()->getLicenseUri(),
            'jacq:nation_engl' => $specimen->getCountry()?->getNameEng(),
            'jacq:iso_alpha_3_code' => $specimen->getCountry()?->getIsoCode3(),
            'jacq:image' => $firstImageLink,
            'jacq:downloadImage' => $firstImageDownloadLink

        ];
    }
}
