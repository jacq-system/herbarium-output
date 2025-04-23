<?php declare(strict_types=1);

namespace App\Facade\Rest;


use App\Entity\Jacq\Herbarinput\Specimens;
use App\Service\Rest\HerbNumberScan;
use App\Service\SpecimenService;
use App\Service\TaxonService;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

readonly class ObjectsFacade
{
    //TODO refactored a little, but no much happy with the result :(
    public function __construct(protected  EntityManagerInterface $entityManager, protected RouterInterface $router, protected  TaxonService $taxonService, protected HerbNumberScan $herbNumberScan, protected SpecimenService  $specimenService)
    {
    }

    /**
     * search for all specimens which fit given criteria
     * possible taxon-IDs have to be given as a list, as the search service for taxons need a special key and only the main rest-function has this key
     * params are all optional
     *      p (page to display, default first page),
     *      rpp (records per page, default 50),
     *      list (return just a list of specimen-IDs, default 1),
     *      term (search for taxon)
     *      herbnr (search for herbarium nuber)
     *      collnr (search for collection number))
     *      sc (search for source code)
     *      cltr (search for collector)
     *      nation (search for nation)
     *      type (type records only, default 0)
     *      withImages (records with images only, default 0)
     *      sort (sort order, default sciname, herbnr)
     *
     * @param array $params any parameters of the search
     * @param array $taxonIDList search for taxon terms has already finished, this is the list of results; defaults to empty array
     * @return array
     */
    public function resolveSpecimens(int $p, int $rpp, int $listOnly, string $term, string $sc, string $coll, int $type, string $sort, string $herbnr, string $nation, int $withImages, string $cltr): array
    {

        $taxonIDList = [];
        if (!empty($term)) {
            $taxa = $this->taxonService->fulltextSearch($term);

            foreach ($taxa as $taxon) {
                $taxonIDList[] = $taxon['taxonID'];
            }
        }
        $baseQuery = $this->buildSpecimensQuery($taxonIDList, $term, $herbnr, $sc, $cltr, $nation, $type, $withImages, $sort);

        $query = $baseQuery . " LIMIT " . ($rpp * $p) . "," . $rpp;
        /** providing all possible params to the query */
        $params = [
            "taxonIDList" => $taxonIDList,
            "herbNr" => strtr($herbnr, '*', '%'),
            "herbNrPure" => $herbnr,
            "collNr" => strtr($coll, '*', '%'),
            "collNrPure" => $coll,
            "sc" => $sc,
            "cltr" => $cltr . "%",
            "cltrBothSide" => "%" . $cltr . "%",
            "nation" => $nation,
        ];

        $parameterTypes = [
            "taxonIDList" => ArrayParameterType::INTEGER
        ];

        $list = $this->entityManager->getConnection()->executeQuery($query, $params, $parameterTypes)->fetchAllAssociative();
        $nrRows = (int)$this->entityManager->getConnection()->executeQuery("SELECT FOUND_ROWS()")->fetchOne();

        // get the number of pages and check the active page again
        $lastPage = floor(($nrRows - 1) / $rpp);
        if ($p > $lastPage) {   // if the page number was wrongly set to a too large value
            $p = $lastPage + 1; // reset it to the page after the last page
        }

        $originalParameters = [
            'p' => $p,               // page, default: display first page
            'rpp' => $rpp,              // records per page, default: 50
            'list' => $listOnly,               // return just a list of specimen-IDs?, default: yes
            'term' => $term,              // search for scientific name (joker = *)
            'herbnr' => $herbnr,              // search for herbarium number (joker = *)
            'collnr' => $coll,              // search for collection number (joker = *)
            'sc' => $sc,              // search for a source-code
            'cltr' => $cltr,              // search for a collector
            'nation' => $nation,              // search for a nation
            'type' => $type,               // switch, search only for type records (default: no)
            'withImages' => $withImages,               // switch, search only for records with images (default: no)
            'sort' => $sort            // sorting of result, default: order scinames and herbnumbers
        ];

        $newParameters = '&' . http_build_query($originalParameters, '', '&', PHP_QUERY_RFC3986);
        $url = $this->router->generate('services_rest_objects_specimens', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $data = array('total' => $nrRows,
            'itemsPerPage' => $originalParameters['rpp'],
            'page' => $p + 1,
            'previousPage' => $url . '?p=' . (($p > 0) ? ($p - 1) : 0) . $newParameters,
            'nextPage' => $url . '?p=' . (($p < $lastPage) ? ($p + 1) : $lastPage) . $newParameters,
            'firstPage' => $url . '?p=0' . $newParameters,
            'lastPage' => $url . '?p=' . $lastPage . $newParameters,
            'totalPages' => $lastPage + 1,
            'result' => array()
        );
        foreach ($list as $item) {
            $specimen = $this->specimenService->findAccessibleForPublic($item['specimenID']);
            $data['result'][] = (!empty($listOnly)) ? intval($item['specimenID']) : $this->resolveSpecimen($specimen);
        }

        return $data;
    }

    protected function buildSpecimensQuery(array $taxonIDList, string $term, string $herbnr, string $sc, string $cltr, string $nation, int $type, int $withImages, string $sort): string
    {
        // prepare the parts of the query string
        $sql = "SELECT SQL_CALC_FOUND_ROWS s.specimen_ID AS specimenID
            FROM tbl_specimens s ";
        $joins = array();
        $constraint = "WHERE s.accessible != '0' ";
        $order = "ORDER BY ";

        // what to search for
        if ($term !== '') {
            if (count($taxonIDList) > 0) {
                $constraint .= " AND s.taxonID IN (:taxonIDList)";
            } else { // there is no scientific name which fits the search criterea, so there can be no result
                $constraint .= " AND 0 ";
            }
        }
        if (!empty($herbnr)) {
            if (str_contains($herbnr, '*')) {
                $constraint .= " AND s.HerbNummer LIKE :herbNr ";
            } else {
                $constraint .= " AND s.HerbNummer = :herbNrPure ";
            }
        }
        if (!empty($collnr)) {
            if (str_contains($collnr, '*')) {
                $constraint .= " AND s.CollNummer LIKE :collNr ";
            } else {
                $constraint .= " AND s.CollNummer = :collNrPure";
            }
        }
        if (!empty($sc)) {
            $joins['m'] = true;
            //TODO why LIKE?
            $constraint .= " AND m.SourceInstitutionID LIKE :sc ";
        }
        if (!empty($cltr)) {
            $joins['c'] = true;
            $constraint .= " AND (c.Sammler LIKE :cltr OR c2.Sammler_2 LIKE :cltrBothSide) ";
        }
        if (!empty($nation)) {
            $joins['gn'] = true;
            //TODO why LIKE?
            $constraint .= " AND (n.nation_engl LIKE :nation OR n.nation LIKE :nation OR n.nation_deutsch LIKE :nation) ";
        }
        if (!empty($type)) {
            $joins['tst'] = true;
            $constraint .= " AND tst.typusID IS NOT NULL ";
        }
        if (!empty($withImages)) {
            $constraint .= " AND (s.digital_image = 1 OR s.digital_image_obs = 1) ";
        }

        // order the result
        $parts = explode(',', $sort);
        foreach ($parts as $part) {
            $t_part = trim($part);
            if (str_starts_with($t_part, '-')) {
                $key = substr($t_part, 1);
                $orderSequence = " DESC";
            } elseif (str_starts_with($t_part, '+')) {
                $key = substr($t_part, 1);
                $orderSequence = " ASC";
            } else {
                $key = $t_part;
                $orderSequence = '';
            }
            switch ($key) {
                case 'sciname':
                    $joins['sn'] = true;
                    $order .= "sn.scientificName{$orderSequence},";
                    break;
                case 'cltr':
                    $joins['c'] = true;
                    $order .= "c.Sammler{$orderSequence},c2.Sammler_2{$orderSequence},";
                    break;
                case 'ser':
                    $joins['ss'] = true;
                    $order .= "ss.series{$orderSequence},";
                    break;
                case 'num':
                    $order .= "s.Nummer{$orderSequence},";
                    break;
                case 'herbnr':
                    $order .= "s.HerbNummer{$orderSequence},";
                    break;
            }
        }
        $order .= "s.specimen_ID";  // as last resort, order according to specimen-ID

        // add all activated joins
        foreach ($joins as $join => $val) {
            if ($val) {
                switch ($join) {
                    case 'tst':
                        $sql .= " LEFT JOIN tbl_specimens_types tst       ON tst.specimenID  = s.specimen_ID ";
                        break;
                    case 'm':
                        $sql .= " LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                      LEFT JOIN metadata m                        ON m.MetadataID     = mc.source_ID ";
                        break;
                    case 'gn':
                        $sql .= " LEFT JOIN tbl_geo_nation n              ON n.nationID      = s.NationID ";
                        break;
                    case 'c':
                        $sql .= " LEFT JOIN tbl_collector c               ON c.SammlerID     = s.SammlerID
                                      LEFT JOIN tbl_collector_2 c2            ON c2.Sammler_2ID  = s.Sammler_2ID ";
                        break;
                    case 'sn':
                        $sql .= " LEFT JOIN tbl_tax_sciname sn            ON sn.taxonID      = s.taxonID ";
                        break;
                    case 'ss':
                        $sql .= " LEFT JOIN tbl_specimens_series ss       ON ss.seriesID     = s.seriesID ";
                        break;
                }
            }
        }
        return $sql . $constraint . $order;
    }

    /**
     * get all or some properties of a specimen with given ID
     *
     * @param int $specimenID ID of specimen
     * @param string $fieldGroups which groups should be returned (dc, dwc, jacq), defaults to all
     * @return array properties (dc, dwc and jacq)
     */
    public function resolveSpecimen(Specimens $specimen, string $fieldGroups = '', bool $removeEmptyValues = false): array
    {

        if (!str_contains($fieldGroups, "dc") && !str_contains($fieldGroups, "dwc") && !str_contains($fieldGroups, "jacq")) {
            $fieldGroups = "dc, dwc, jacq";
        }

        $ret = array();
        if (str_contains($fieldGroups, "dc")) {
            $ret['dc'] = $this->specimenService->getDublinCore($specimen);
        }
        if (str_contains($fieldGroups, "dwc")) {
            $ret['dwc'] = $this->specimenService->getDarwinCore($specimen);
        }
        if (str_contains($fieldGroups, "jacq")) {
            $ret['jacq'] = $this->specimenService->getJACQ($specimen);
        }

        if ($removeEmptyValues) {
            $resultsFiltered = [];
            foreach ($ret as $format => $group) {
                foreach ($group as $key => $value) {
                    if (!empty($value)) {
                        $resultsFiltered[$format][$key] = $value;
                    }
                }
            }
            return $resultsFiltered;
        }

        return $ret;
    }

}
