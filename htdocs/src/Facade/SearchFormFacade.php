<?php declare(strict_types=1);

namespace App\Facade;

use App\Controller\Front\SearchFormController;
use App\Entity\Jacq\Herbarinput\Specimens;
use App\Entity\Jacq\Herbarinput\StableIdentifier;
use App\Entity\Jacq\Herbarinput\Typus;
use App\Service\ExcelService;
use App\Service\InstitutionService;
use App\Service\KmlService;
use App\Service\SearchFormSessionService;
use App\Service\SpecimenService;
use App\Service\TypusService;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Exception;

class SearchFormFacade
{
    public const int PAGINATION_RANGE = 3;
    protected QueryBuilder $queryBuilder;

    public function __construct(protected readonly EntityManagerInterface $entityManager, protected readonly InstitutionService $institutionService, protected readonly SearchFormSessionService $searchFormSessionService, protected readonly SpecimenService $specimenService, protected readonly TypusService $typusService, protected readonly KmlService $kmlService)
    {
    }

    public function search(): array
    {
        $this->buildQuery();

        $recordsPerPage = $this->searchFormSessionService->getSetting('recordsPerPage', SearchFormController::RECORDS_PER_PAGE[0]);
        $page = $this->searchFormSessionService->getSetting('page', 1);
        $offset = ($page - 1) * $recordsPerPage;

        $sort = $this->searchFormSessionService->getSetting('sort');

        $this->queryBuilder
            ->setFirstResult((int)$offset)
            ->setMaxResults((int)$recordsPerPage);
        return $this->queryBuilder->getQuery()->getResult();
    }

    protected function buildQuery(): void
    {
        $this->queryBuilder = $this->entityManager->getRepository(Specimens::class)
            ->createQueryBuilder('s')
            ->join('s.species', 'species')
            ->join('species.genus', 'genus')
            ->leftJoin('species.author', 'author')
            ->leftJoin('species.epithet', 'epithet')
            ->join('s.herbCollection', 'c')
            ->orderBy('genus.name', Order::Ascending->value)
            ->addOrderBy('epithet.name', Order::Ascending->value)
            ->addOrderBy('author.name', Order::Ascending->value);

        if (!empty($this->searchFormSessionService->getFilter('institution'))) {
            $this->queryInstitution((int)$this->searchFormSessionService->getFilter('institution'));
        }

        if (!empty($this->searchFormSessionService->getFilter('herbNr'))) {
            $this->queryHerbNr($this->searchFormSessionService->getFilter('herbNr'));
        }

        if (!empty($this->searchFormSessionService->getFilter('collection'))) {
            $this->queryBuilder->andWhere('c.id = :collection')
                ->setParameter('collection', $this->searchFormSessionService->getFilter('collection'));
        }

        if (!empty($this->searchFormSessionService->getFilter('collectorNr'))) {
            $this->queryCollectorNr($this->searchFormSessionService->getFilter('collectorNr'));
        }

        if (!empty($this->searchFormSessionService->getFilter('collector'))) {
            $this->queryCollector($this->searchFormSessionService->getFilter('collector'));
        }

        if (!empty($this->searchFormSessionService->getFilter('collectionDate'))) {
            $this->queryBuilder->andWhere('s.date LIKE :collectionDate')
                ->setParameter('collectionDate', '%' . $this->searchFormSessionService->getFilter('collectionDate') . "%");
        }

        if (!empty($this->searchFormSessionService->getFilter('collectionNr'))) {
            $this->queryBuilder->andWhere('s.collectionNumber LIKE :collectionNr')
                ->setParameter('collectionNr', '%' . $this->searchFormSessionService->getFilter('collectionNr') . "%");
        }

        if (!empty($this->searchFormSessionService->getFilter('series'))) {
            $this->querySeries($this->searchFormSessionService->getFilter('series'));
        }

        if (!empty($this->searchFormSessionService->getFilter('locality'))) {
            $this->queryLocality($this->searchFormSessionService->getFilter('locality'));
        }

        if (!empty($this->searchFormSessionService->getFilter('habitus'))) {
            $this->queryBuilder->andWhere('s.habitus LIKE :habitus')
                ->setParameter('habitus', '%' . $this->searchFormSessionService->getFilter('habitus') . '%');
        }

        if (!empty($this->searchFormSessionService->getFilter('habitat'))) {
            $this->queryBuilder->andWhere('s.habitat LIKE :habitat')
                ->setParameter('habitat', '%' . $this->searchFormSessionService->getFilter('habitat') . '%');
        }

        if (!empty($this->searchFormSessionService->getFilter('taxonAlternative'))) {
            $this->queryBuilder->andWhere('s.taxonAlternative LIKE :taxonAlternative')
                ->setParameter('taxonAlternative', '%' . $this->searchFormSessionService->getFilter('taxonAlternative') . '%');
        }

        if (!empty($this->searchFormSessionService->getFilter('annotation'))) {
            $this->queryBuilder->andWhere('s.annotation LIKE :annotation')
                ->setParameter('annotation', '%' . $this->searchFormSessionService->getFilter('annotation') . '%');
        }

        if (!empty($this->searchFormSessionService->getFilter('country'))) {
            $this->queryCountry($this->searchFormSessionService->getFilter('country'));
        }

        if (!empty($this->searchFormSessionService->getFilter('province'))) {
            $this->queryProvince($this->searchFormSessionService->getFilter('province'));
        }

        if (!empty($this->searchFormSessionService->getFilter('onlyType'))) {
            $this->queryType();
        }

        if (!empty($this->searchFormSessionService->getFilter('onlyImages'))) {
            $this->queryImages();
        }

        if (!empty($this->searchFormSessionService->getFilter('family'))) {
            $this->queryFamily($this->searchFormSessionService->getFilter('family'));
        }

        if (!empty($this->searchFormSessionService->getFilter('taxon'))) {
            $this->queryTaxon($this->searchFormSessionService->getFilter('taxon'));
        }

    }

    protected function queryInstitution(int $id): void
    {
        $this->queryBuilder
            ->join('c.institution', 'i')
            ->andWhere('i.id = :institution')
            ->setParameter('institution', $id);
    }

// TODO table ts2 ommited from original query,  why rejoin the same table for (?probably) same query

    /**
     * simplified. Original code searched also in collectorNr for example. Let's use just trim the institution code and do simple fulltext
     */
    protected function queryHerbNr(string $value): void
    {
        $pattern = '/^(?<code>[a-zA-Z]+)\s+(?<rest>.*)$/';
        $this->queryBuilder->andWhere('s.herbNumber LIKE :herbNr');
        if (preg_match($pattern, $value, $matches) && empty($this->searchFormSessionService->getFilter('institution'))) {
            try {
                $institution = $this->institutionService->findByCode($matches['code']);
                $this->queryInstitution($institution->getId());
            } catch (Exception $exception) {
            }
            $this->queryBuilder->setParameter('herbNr', '%' . $matches['rest']);
        } else {
            $this->queryBuilder->setParameter('herbNr', '%' . $value);
        }

    }

    protected function queryCollectorNr(string $id): void
    {
        $likeParameter = "%" . $id . "%";
        $this->queryBuilder
            ->andWhere(
                $this->queryBuilder->expr()->orX(
                    $this->queryBuilder->expr()->eq('s.number', ':id'),
                    $this->queryBuilder->expr()->like('s.altNumber', ':idLike'),
                    $this->queryBuilder->expr()->like('s.seriesNumber', ':idLike')
                )
            )
            ->setParameter('id', $id)
            ->setParameter('idLike', $likeParameter);
    }

    protected function queryCollector(string $id): void
    {
        $conditions = [];
        if (!empty($this->getCollectorIds($id))) {
            $conditions[] = $this->queryBuilder->expr()->in('s.collector', $this->getCollectorIds($id));
        }

        if (!empty($this->getCollector2Ids($id))) {
            $conditions[] = $this->queryBuilder->expr()->in('s.collector2', $this->getCollector2Ids($id));
        }

        $this->queryBuilder->andWhere(
            $this->queryBuilder->expr()->orX(...$conditions)
        );
    }

    protected function getCollectorIds(string $id): array
    {
        $value = $id . '%';
        $sql = "SELECT SammlerID
                FROM tbl_collector
                WHERE Sammler LIKE :value";
        return $this->entityManager->getConnection()->executeQuery($sql, ['value' => $value])->fetchFirstColumn();
    }

    protected function getCollector2Ids(string $id): array
    {
        $value = '%' . $id . '%';
        $sql = "SELECT Sammler_2ID FROM tbl_collector_2 WHERE Sammler_2 LIKE :value";
        return $this->entityManager->getConnection()->executeQuery($sql, ['value' => $value])->fetchFirstColumn();
    }

    protected function querySeries(string $id): void
    {
        $this->queryBuilder
            ->join('s.series', 'series')
            ->andWhere('series.name LIKE :series')
            ->setParameter('series', '%' . $id . '%');
    }

    protected function queryLocality(string $id): void
    {
        $this->queryBuilder
            ->andWhere(
                $this->queryBuilder->expr()->orX(
                    $this->queryBuilder->expr()->like('s.locality', ':locality'),
                    $this->queryBuilder->expr()->like('s.localityEng', ':locality')
                )
            )
            ->setParameter('locality', "%" . $id . "%");
    }

    protected function queryCountry(string $id): void
    {
        $this->queryBuilder
            ->join('s.country', 'country')
            ->andWhere($this->queryBuilder->expr()->orX(
                $this->queryBuilder->expr()->like('country.name', ':country'),
                $this->queryBuilder->expr()->like('country.nameEng', ':country'),
                $this->queryBuilder->expr()->andX(
                    $this->queryBuilder->expr()->like('country.variants', ':country'),
                    $this->queryBuilder->expr()->notLike('country.variants', ':countryExcluded'),
                )
            ))
            ->setParameter('country', $id . '%')
            ->setParameter('countryExcluded', '%(%' . $id . '%)%');
    }

    protected function queryProvince(string $id): void
    {
        $this->queryBuilder
            ->join('s.province', 'province')
            ->andWhere($this->queryBuilder->expr()->orX(
                $this->queryBuilder->expr()->like('province.name', ':province'),
                $this->queryBuilder->expr()->like('province.nameLocal', ':province')
            ))
            ->setParameter('province', $id . '%');
    }

    protected function queryType(): void
    {
        $this->queryBuilder
            ->andWhere(
                $this->queryBuilder->expr()->exists(
                    $this->entityManager->createQueryBuilder()
                        ->select('1')
                        ->from(Typus::class, 'hasType')
                        ->where('hasType.specimen = s')
                        ->getDQL()
                )
            );

    }

    protected function queryImages(): void
    {
        $this->queryBuilder
            ->andWhere($this->queryBuilder->expr()->orX(
                $this->queryBuilder->expr()->eq('s.image', 1),
                $this->queryBuilder->expr()->eq('s.imageObservation', 1)
            ));
    }

    protected function queryFamily(string $id): void
    {
        $this->queryBuilder
            ->join('genus.family', 'family');

        $this->queryBuilder
            ->andWhere($this->queryBuilder->expr()->orX(
                $this->queryBuilder->expr()->like('family.name', ':family'),
                $this->queryBuilder->expr()->like('family.nameAlternative', ':family')))
            ->setParameter('family', $id . '%');
    }

    protected function queryTaxon(string $id): void
    {
        $taxaIds = $this->getTaxonIds($id);
        $conditions = [];
        //result includes NULL rows that need to be excluded
        $taxonId = array_filter(array_column($taxaIds, 'taxonID'), fn($value) => $value !== null);
        $basID = array_filter(array_column($taxaIds, 'basID'), fn($value) => $value !== null);
        $synID = array_filter(array_column($taxaIds, 'synID'), fn($value) => $value !== null);
        if (!empty($this->searchFormSessionService->getFilter('includeSynonym'))) {
            if (!empty($taxonId)) {
                $conditions[] = $this->queryBuilder->expr()->orX(
                    $this->queryBuilder->expr()->in('species.id', $taxonId),
                    $this->queryBuilder->expr()->in('species.basID', $taxonId),
                    $this->queryBuilder->expr()->in('species.synID', $taxonId)
                );
            }

            if (!empty($basID)) {
                $conditions[] = $this->queryBuilder->expr()->orX(
                    $this->queryBuilder->expr()->in('species.id', $basID),
                    $this->queryBuilder->expr()->in('species.basID', $basID),
                    $this->queryBuilder->expr()->in('species.synID', $basID)
                );
            }

            if (!empty($synID)) {
                $conditions[] = $this->queryBuilder->expr()->orX(
                    $this->queryBuilder->expr()->in('species.id', $synID),
                    $this->queryBuilder->expr()->in('species.basID', $synID),
                    $this->queryBuilder->expr()->in('species.synID', $synID)
                );
            }
        } else {
            if (!empty($taxonId)) {
                $conditions[] = $this->queryBuilder->expr()->orX(
                    $this->queryBuilder->expr()->in('species.id', $taxonId)
                );
            }
        }

        //finally add to the builder
        $this->queryBuilder->andWhere(
            $this->queryBuilder->expr()->orX(...$conditions)
        );


    }

    protected function getTaxonIds(string $name)
    {
        $pieces = explode(" ", trim($name));
        $part1 = array_shift($pieces);
        $part2 = array_shift($pieces);
        if (empty($part2)) {
            $sql = "SELECT ts.taxonID, ts.basID, ts.synID
                    FROM tbl_tax_genera tg,  tbl_tax_species ts
                     LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                     LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                     LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                     LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                     LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                     LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                    WHERE tg.genID = ts.genID AND tg.genus LIKE :part1 ";
        } else {
            $sql = "SELECT ts.taxonID, ts.basID, ts.synID
                    FROM tbl_tax_genera tg,  tbl_tax_species ts
                     LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                     LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                     LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                     LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                     LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                     LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                    WHERE tg.genID = ts.genID AND tg.genus LIKE :part1  AND (     te.epithet LIKE :part2
                                                        OR te1.epithet LIKE :part2
                                                        OR te2.epithet LIKE :part2
                                                        OR te3.epithet LIKE :part2
                                                        OR te4.epithet LIKE :part2
                                                        OR te5.epithet LIKE :part2)";
        }

        return $this->entityManager->getConnection()->executeQuery($sql, ['part1' => $part1 . '%', 'part2' => $part2 . '%'])->fetchAllAssociative();
    }

    public function providePaginationInfo(): array
    {
        $totalRecordCount = $this->countResults();
        $recordsPerPage = $this->searchFormSessionService->getSetting('recordsPerPage', SearchFormController::RECORDS_PER_PAGE[0]);
        $currentPage = $this->searchFormSessionService->getSetting('page', 1);

        $totalPages = ceil($totalRecordCount / $recordsPerPage);

        $pages[] = 1;
        if ($currentPage > 1) {

            if ($currentPage > self::PAGINATION_RANGE + 2) {
                $pages[] = '...';
            }
        }

        $start = max(2, $currentPage - self::PAGINATION_RANGE);
        $end = min($totalPages - 1, $currentPage + self::PAGINATION_RANGE);

        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        if ($currentPage < $totalPages) {
            if ($currentPage < $totalPages - self::PAGINATION_RANGE - 1) {
                $pages[] = '...';
            }
        }
        if ($totalPages > 1) {
            $pages[] = $totalPages;
        }
        return ["totalRecords" => $totalRecordCount, "totalPages" => $totalPages, "pages" => $pages];
    }

    public function countResults(): int
    {
        $this->buildQuery();
        return $this->queryBuilder->select('count(DISTINCT s.id)')->getQuery()->getSingleScalarResult();
    }

    public function searchOnlyIds(?int $limit = null): array
    {
        $this->buildQuery();
        if($limit){
            $this->queryBuilder->setMaxResults($limit);
        }
        return $this->queryBuilder
            ->select('DISTINCT s.id')
            ->resetDQLPart('orderBy') //TODO maybe better keep sort as in UI? - sort is not fully resolved in the code
            ->orderBy('genus.name', Order::Ascending->value)
            ->addOrderBy('epithet.name', Order::Ascending->value)
            ->addOrderBy('author.name', Order::Ascending->value)
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function getSpecimenDataforExport(): array
    {
        $rows = [];
        $specimenIds = $this->searchOnlyIds(ExcelService::EXPORT_LIMIT);
        $sqlSpecimen = "SELECT s.specimen_ID, s.series_number, s.Nummer, s.alt_number, s.Datum, s.Datum2, s.Fundort, s.det, s.taxon_alt, s.Bemerkungen,
                 s.CollNummer, s.altitude_min, s.altitude_max, s.Bezirk, s.Fundort, s.typified,
                 s.digital_image, s.digital_image_obs, s.HerbNummer, s.ncbi_accession, s.observation, s.habitat, s.habitus, s.garten,
                 s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                 s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
                 s.quadrant, s.quadrant_sub, s.exactness,
                 ss.series,
                 si.identification_status,
                 sv.voucher,
                 mc.collection, mc.collectionID, mc.coll_short, m.SourceInstitutionID as source_code,
                 n.nation_engl, p.provinz,
                 c.Sammler, c2.Sammler_2,
                 tr.rank_abbr,
                 tg.genus, tf.family,
                 ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
                 `herbar_view`.GetScientificName(ts.taxonID, 0) AS `scientificName`
                FROM tbl_specimens s
                 LEFT JOIN tbl_specimens_series          ss  ON ss.seriesID = s.seriesID
                 LEFT JOIN tbl_specimens_identstatus     si  ON si.identstatusID = s.identstatusID
                 LEFT JOIN tbl_specimens_voucher         sv  ON sv.voucherID = s.voucherID
                 LEFT JOIN tbl_management_collections    mc  ON mc.collectionID = s.collectionID
                 LEFT JOIN metadata                      m   ON m.MetadataID = mc.source_id
                 LEFT JOIN tbl_geo_nation                n   ON n.NationID = s.NationID
                 LEFT JOIN tbl_geo_province              p   ON p.provinceID = s.provinceID
                 LEFT JOIN tbl_collector                 c   ON c.SammlerID = s.SammlerID
                 LEFT JOIN tbl_collector_2               c2  ON c2.Sammler_2ID = s.Sammler_2ID
                 LEFT JOIN tbl_tax_species               ts  ON ts.taxonID = s.taxonID
                 LEFT JOIN tbl_tax_authors               ta  ON ta.authorID = ts.authorID
                 LEFT JOIN tbl_tax_authors               ta1 ON ta1.authorID = ts.subspecies_authorID
                 LEFT JOIN tbl_tax_authors               ta2 ON ta2.authorID = ts.variety_authorID
                 LEFT JOIN tbl_tax_authors               ta3 ON ta3.authorID = ts.subvariety_authorID
                 LEFT JOIN tbl_tax_authors               ta4 ON ta4.authorID = ts.forma_authorID
                 LEFT JOIN tbl_tax_authors               ta5 ON ta5.authorID = ts.subforma_authorID
                 LEFT JOIN tbl_tax_epithets              te  ON te.epithetID = ts.speciesID
                 LEFT JOIN tbl_tax_epithets              te1 ON te1.epithetID = ts.subspeciesID
                 LEFT JOIN tbl_tax_epithets              te2 ON te2.epithetID = ts.varietyID
                 LEFT JOIN tbl_tax_epithets              te3 ON te3.epithetID = ts.subvarietyID
                 LEFT JOIN tbl_tax_epithets              te4 ON te4.epithetID = ts.formaID
                 LEFT JOIN tbl_tax_epithets              te5 ON te5.epithetID = ts.subformaID
                 LEFT JOIN tbl_tax_rank                  tr  ON tr.tax_rankID = ts.tax_rankID
                 LEFT JOIN tbl_tax_genera                tg  ON tg.genID = ts.genID
                 LEFT JOIN tbl_tax_families              tf  ON tf.familyID = tg.familyID
                WHERE specimen_ID IN (:specimenIDs)";
        $parameterTypes = [
            "specimenIDs" => ArrayParameterType::INTEGER
        ];
        $result = $this->entityManager->getConnection()->executeQuery($sqlSpecimen, ['specimenIDs' => $specimenIds], $parameterTypes);
        while ($rowSpecimen = $result->fetchAssociative()) {
            $rows[] = $this->prepareRowForExport($rowSpecimen);
        }
        return $rows;
    }

    protected function prepareRowForExport(array $rowSpecimen): array
    {//TODO kept as is
        if ($rowSpecimen['epithet5']) {
            $infra_spec = $rowSpecimen['epithet5'];
            $infra_author = $rowSpecimen['author5'];
        } elseif ($rowSpecimen['epithet4']) {
            $infra_spec = $rowSpecimen['epithet4'];
            $infra_author = $rowSpecimen['author4'];
        } elseif ($rowSpecimen['epithet3']) {
            $infra_spec = $rowSpecimen['epithet3'];
            $infra_author = $rowSpecimen['author3'];
        } elseif ($rowSpecimen['epithet2']) {
            $infra_spec = $rowSpecimen['epithet2'];
            $infra_author = $rowSpecimen['author2'];
        } elseif ($rowSpecimen['epithet1']) {
            $infra_spec = $rowSpecimen['epithet1'];
            $infra_author = $rowSpecimen['author1'];
        } else {
            $infra_spec = '';
            $infra_author = '';
        }

        if ($rowSpecimen['Coord_S'] > 0 || $rowSpecimen['S_Min'] > 0 || $rowSpecimen['S_Sec'] > 0) {
            $lat = -($rowSpecimen['Coord_S'] + $rowSpecimen['S_Min'] / 60 + $rowSpecimen['S_Sec'] / 3600);
            $latDMS = $rowSpecimen['Coord_S'] . "°";
            if (!empty($rowSpecimen['S_Min'])) {
                $latDMS .= ' ' . $rowSpecimen['S_Min'] . "'";
            }
            if (!empty($rowSpecimen['S_Sec'])) {
                $latDMS .= ' ' . $rowSpecimen['S_Sec'] . '"';
            }
            $latDMS .= ' S';
            $latHemisphere = 'S';
        } else if ($rowSpecimen['Coord_N'] > 0 || $rowSpecimen['N_Min'] > 0 || $rowSpecimen['N_Sec'] > 0) {
            $lat = $rowSpecimen['Coord_N'] + $rowSpecimen['N_Min'] / 60 + $rowSpecimen['N_Sec'] / 3600;
            $latDMS = $rowSpecimen['Coord_N'] . "°";
            if (!empty($rowSpecimen['N_Min'])) {
                $latDMS .= ' ' . $rowSpecimen['N_Min'] . "'";
            }
            if (!empty($rowSpecimen['N_Sec'])) {
                $latDMS .= ' ' . $rowSpecimen['N_Sec'] . '"';
            }
            $latDMS .= ' N';
            $latHemisphere = 'N';
        } else {
            $lat = $latDMS = $latHemisphere = '';
        }
        if (strlen((string)$lat) > 0) {
            $lat = "" . number_format(round($lat, 9), 9) . "° ";
        }

        if ($rowSpecimen['Coord_W'] > 0 || $rowSpecimen['W_Min'] > 0 || $rowSpecimen['W_Sec'] > 0) {
            $lon = -($rowSpecimen['Coord_W'] + $rowSpecimen['W_Min'] / 60 + $rowSpecimen['W_Sec'] / 3600);
            $lonDMS = $rowSpecimen['Coord_W'] . "°";
            if (!empty($rowSpecimen['W_Min'])) {
                $lonDMS .= ' ' . $rowSpecimen['W_Min'] . "'";
            }
            if (!empty($rowSpecimen['W_Sec'])) {
                $lonDMS .= ' ' . $rowSpecimen['W_Sec'] . '"';
            }
            $lonDMS .= ' W';
            $lonHemisphere = 'W';
        } else if ($rowSpecimen['Coord_E'] > 0 || $rowSpecimen['E_Min'] > 0 || $rowSpecimen['E_Sec'] > 0) {
            $lon = $rowSpecimen['Coord_E'] + $rowSpecimen['E_Min'] / 60 + $rowSpecimen['E_Sec'] / 3600;
            $lonDMS = $rowSpecimen['Coord_E'] . "°";
            if (!empty($rowSpecimen['E_Min'])) {
                $lonDMS .= ' ' . $rowSpecimen['E_Min'] . "'";
            }
            if (!empty($rowSpecimen['E_Sec'])) {
                $lonDMS .= ' ' . $rowSpecimen['E_Sec'] . '"';
            }
            $lonDMS .= ' E';
            $lonHemisphere = 'E';
        } else {
            $lon = $lonDMS = $lonHemisphere = '';
        }

        if (strlen((string)$lon) > 0) {
            $lon = "" . number_format(round($lon, 9), 9) . "° ";
        }

        return [
            $rowSpecimen['specimen_ID'],
            $rowSpecimen['observation'],
            ($rowSpecimen['digital_image']) ? '1' : '',
            ($rowSpecimen['digital_image_obs']) ? '1' : '',
            $rowSpecimen['source_code'],
            $rowSpecimen['HerbNummer'],
            $rowSpecimen['coll_short'],
            $rowSpecimen['CollNummer'],
            $this->makeTypus(intval($rowSpecimen['specimen_ID'])),
            $rowSpecimen['typified'],
            $rowSpecimen['scientificName'],
            $rowSpecimen['identification_status'],
            $rowSpecimen['genus'],
            $rowSpecimen['epithet'],
            $rowSpecimen['author'],
            $rowSpecimen['rank_abbr'],
            $infra_spec,
            $infra_author,
            $rowSpecimen['family'],
            $rowSpecimen['garten'],
            $rowSpecimen['voucher'],
            $this->specimenService->collection($rowSpecimen),
            $rowSpecimen['Sammler'],
            $rowSpecimen['Nummer'],
            $rowSpecimen['Sammler_2'],
            $rowSpecimen['alt_number'],
            $rowSpecimen['series'],
            $rowSpecimen['series_number'],
            $rowSpecimen['Datum'],
            $rowSpecimen['Datum2'],
            $rowSpecimen['nation_engl'],
            $rowSpecimen['provinz'],
            $rowSpecimen['Bezirk'],
            $lat,
            $latDMS,
            $latHemisphere,
            ($latHemisphere == 'N') ? $rowSpecimen['Coord_N'] : $rowSpecimen['Coord_S'],
            ($latHemisphere == 'N') ? $rowSpecimen['N_Min'] : $rowSpecimen['S_Min'],
            ($latHemisphere == 'N') ? $rowSpecimen['N_Sec'] : $rowSpecimen['S_Sec'],
            $lon,
            $lonDMS,
            $lonHemisphere,
            ($lonHemisphere == 'E') ? $rowSpecimen['Coord_E'] : $rowSpecimen['Coord_W'],
            ($lonHemisphere == 'E') ? $rowSpecimen['E_Min'] : $rowSpecimen['W_Min'],
            ($lonHemisphere == 'E') ? $rowSpecimen['E_Sec'] : $rowSpecimen['W_Sec'],
            $rowSpecimen['exactness'],
            $rowSpecimen['altitude_min'],
            $rowSpecimen['altitude_max'],
            $rowSpecimen['quadrant'],
            $rowSpecimen['quadrant_sub'],
            $rowSpecimen['Fundort'],
            $rowSpecimen['det'],
            $rowSpecimen['taxon_alt'],
            ((substr((string)$rowSpecimen['Bemerkungen'], 0, 1) == '=') ? " " : "") . $rowSpecimen['Bemerkungen'],  // to prevent a starting "=" (would be interpreted as a formula)
            ((substr((string)$rowSpecimen['habitat'], 0, 1) == '=') ? " " : "") . $rowSpecimen['habitat'],          // to prevent a starting "=" (would be interpreted as a formula)
            ((substr((string)$rowSpecimen['habitus'], 0, 1) == '=') ? " " : "") . $rowSpecimen['habitus'],          // to prevent a starting "=" (would be interpreted as a formula)
            $this->getStableIdentifier($rowSpecimen['specimen_ID'])
        ];

    }

    //TODO this function is doing the same as TypusService->makeTypus, just without the HTML formating (!). Left for fast-forward but refactoring necessary!!
    protected function makeTypus(int $id): string
    {
        $sql = "SELECT typus_lat, tg.genus,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
             ts.synID, ts.taxonID, ts.statusID
            FROM (tbl_specimens_types tst, tbl_typi tt, tbl_tax_species ts)
             LEFT JOIN tbl_tax_authors  ta  ON ta.authorID   = ts.authorID
             LEFT JOIN tbl_tax_authors  ta1 ON ta1.authorID  = ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors  ta2 ON ta2.authorID  = ts.variety_authorID
             LEFT JOIN tbl_tax_authors  ta3 ON ta3.authorID  = ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors  ta4 ON ta4.authorID  = ts.forma_authorID
             LEFT JOIN tbl_tax_authors  ta5 ON ta5.authorID  = ts.subforma_authorID
             LEFT JOIN tbl_tax_epithets te  ON te.epithetID  = ts.speciesID
             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
             LEFT JOIN tbl_tax_genera   tg  ON tg.genID      = ts.genID
            WHERE tst.typusID = tt.typusID
             AND tst.taxonID = ts.taxonID
             AND specimenID = :id";
        $result = $this->entityManager->getConnection()->executeQuery($sql, ['id' => $id]);

        $text = "";
        while ($row = $result->fetchAssociative()) {
            if ($row['synID']) {
                $sql3 = "SELECT ts.statusID, tg.genus, ts.taxonID,
                      ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
                      te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5
                     FROM tbl_tax_species ts
                      LEFT JOIN tbl_tax_authors  ta  ON ta.authorID   = ts.authorID
                      LEFT JOIN tbl_tax_authors  ta1 ON ta1.authorID  = ts.subspecies_authorID
                      LEFT JOIN tbl_tax_authors  ta2 ON ta2.authorID  = ts.variety_authorID
                      LEFT JOIN tbl_tax_authors  ta3 ON ta3.authorID  = ts.subvariety_authorID
                      LEFT JOIN tbl_tax_authors  ta4 ON ta4.authorID  = ts.forma_authorID
                      LEFT JOIN tbl_tax_authors  ta5 ON ta5.authorID  = ts.subforma_authorID
                      LEFT JOIN tbl_tax_epithets te  ON te.epithetID  = ts.speciesID
                      LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                      LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                      LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                      LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                      LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                      LEFT JOIN tbl_tax_genera   tg  ON tg.genID      = ts.genID
                     WHERE taxonID =:synId";
                $result3 = $this->entityManager->getConnection()->executeQuery($sql3, ['synId' => $row['synID']]);
                $row3 = $result3->fetchAssociative();
                $accName = $this->typusService->taxonWithHybrids($row3);
            } else {
                $accName = "";
            }

            $sql2 = "SELECT l.suptitel, la.autor, l.periodicalID, lp.periodical, l.vol, l.part, ti.paginae, ti.figures, l.jahr
                 FROM tbl_tax_index ti
                  INNER JOIN tbl_lit            l  ON l.citationID    = ti.citationID
                  LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
                  LEFT JOIN tbl_lit_authors     la ON la.autorID      = l.editorsID
                 WHERE ti.taxonID = :taxonID";
            $result2 = $this->entityManager->getConnection()->executeQuery($sql2, ['taxonID' => $row['taxonID']]);

            $text .= $row['typus_lat'] . " for " . $this->typusService->taxonWithHybrids($row) . " ";
            while ($row2 = $result2->fetchAssociative()) {
                $text .= $this->typusService->protolog($row2) . " ";
            }
            if (strlen($accName) > 0) {
                $text .= "Current Name: $accName ";
            }
        }

        return $text;
    }



    protected function getStableIdentifier(int $specimenID): string
    {
        $specimen = $this->specimenService->findAccessibleForPublic($specimenID);
        if (!empty($specimen->getMainStableIdentifier()->getIdentifier())) {
            return $specimen->getMainStableIdentifier()->getIdentifier();
        } else {
            return $this->specimenService->constructStableIdentifier($specimen);
        }

    }

    public function getKmlExport():string
    {
        $text = '';
        $specimenIds = $this->searchOnlyIds(KmlService::EXPORT_LIMIT);
        $sqlSpecimen = "SELECT s.specimen_ID, tg.genus, c.Sammler, c2.Sammler_2, ss.series, s.series_number,
             s.Nummer, s.alt_number, s.Datum, s.Fundort, s.det, s.taxon_alt, s.Bemerkungen,
             n.nation_engl, p.provinz, s.Fundort, tf.family, tsc.cat_description,
             mc.collection, mc.collectionID, mc.coll_short, s.typified,
             s.digital_image, s.digital_image_obs, s.HerbNummer, s.ncbi_accession,
             s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
             s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5,
             ts.synID, ts.taxonID, ts.statusID
            FROM tbl_specimens s
             LEFT JOIN tbl_specimens_series ss ON ss.seriesID=s.seriesID
             LEFT JOIN tbl_management_collections mc ON mc.collectionID=s.collectionID
             LEFT JOIN tbl_geo_nation n ON n.NationID=s.NationID
             LEFT JOIN tbl_geo_province p ON p.provinceID=s.provinceID
             LEFT JOIN tbl_collector c ON c.SammlerID=s.SammlerID
             LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID=s.Sammler_2ID
             LEFT JOIN tbl_tax_species ts ON ts.taxonID=s.taxonID
             LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
             LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
             LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
             LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID
             LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID=tsc.categoryID
            WHERE specimen_ID IN (:specimenIDs)";
        $parameterTypes = [
            "specimenIDs" => ArrayParameterType::INTEGER
        ];
        $result = $this->entityManager->getConnection()->executeQuery($sqlSpecimen, ['specimenIDs' => $specimenIds], $parameterTypes);
        while ($rowSpecimen = $result->fetchAssociative()) {
            $text .= $this->kmlService->prepareRow($rowSpecimen);
        }
        return $this->kmlService->export($text);
    }
}
