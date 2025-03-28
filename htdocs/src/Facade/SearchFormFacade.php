<?php declare(strict_types=1);

namespace App\Facade;

use App\Controller\Front\SearchFormController;
use App\Entity\Jacq\Herbarinput\Specimens;
use App\Entity\Jacq\Herbarinput\Typus;
use App\Service\ExcelService;
use App\Service\GeoService;
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

    public function __construct(protected readonly EntityManagerInterface $entityManager, protected readonly InstitutionService $institutionService, protected readonly SearchFormSessionService $searchFormSessionService, protected readonly SpecimenService $specimenService, protected readonly TypusService $typusService, protected readonly KmlService $kmlService, protected readonly GeoService $geoService)
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
            ->leftJoin('species.authorSpecies', 'author')
            ->leftJoin('species.epithetSpecies', 'epithet')
            ->join('s.herbCollection', 'c')
            ->orderBy('genus.name', Order::Ascending->value)
            ->andWhere('s.accessibleForPublic = 1')
            ->addOrderBy('epithet.name', Order::Ascending->value)
            ->addOrderBy('author.name', Order::Ascending->value);

        if (!empty($this->searchFormSessionService->getFilter('institution'))) {
            $this->queryInstitution($this->searchFormSessionService->getFilter('institution'));
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

    protected function queryInstitution(string $code): void
    {
        $this->queryBuilder
            ->join('c.institution', 'i')
            ->andWhere('i.code = :institution OR i.name = :institution')
            ->setParameter('institution', $code);
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
                $this->queryInstitution($matches['code']);
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

    public function getSpecimenDataforExport(): array
    {
        $rows = [];
        $specimens = $this->searchForExport(ExcelService::EXPORT_LIMIT);
        foreach ($specimens as $specimen) {
            $rows[] = $this->prepareRowForExport($specimen);
        }
        return $rows;
    }

    public function searchOnlyIds(?int $limit = null): array
    {
        $this->buildQuery();
        if ($limit) {
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

    public function searchForExport(?int $limit = null): array
    {
        $this->buildQuery();
        if ($limit) {
            $this->queryBuilder->setMaxResults($limit);
        }
        return $this->queryBuilder->getQuery()->getResult();
    }

    protected function prepareRowForExport(Specimens $specimen): array
    {
        $infraInfo = $specimen->getSpecies()->getInfraEpithet();

        $specimen->getLatitude() ? $latDMS = $this->geoService->decimalToDMS($specimen->getLatitude()) . ' ' . $specimen->getHemisphereLatitude() : $latDMS = null;
        $specimen->getLongitude() ? $lonDMS = $this->geoService->decimalToDMS($specimen->getLongitude()) . ' ' . $specimen->getHemisphereLongitude() : $lonDMS = null;

        return [
            $specimen->getId(),
            $specimen->isObservation() ? 1 : '',
            $specimen->hasImage() ? 1 : '',
            $specimen->hasImageObservation() ? 1 : '',
            $specimen->getHerbCollection()->getInstitution()->getCode(),
            $specimen->getHerbNumber(),
            $specimen->getHerbCollection()->getCollShort(),
            $specimen->getCollectionNumber(),
            $this->typusService->getTypusText($specimen),
            $specimen->getTypified(),
            $this->specimenService->getScientificName($specimen),
            $specimen->getIdentificationStatus()?->getName(),
            $specimen->getSpecies()->getGenus()->getName(),
            $specimen->getSpecies()->getEpithetSpecies()?->getName(),
            $specimen->getSpecies()->getAuthorSpecies()->getName(),
            $specimen->getSpecies()->getRank()->getAbbreviation(),
            $infraInfo['epithet'],
            $infraInfo['author'],
            $specimen->getSpecies()->getGenus()->getFamily()->getName(),
            $specimen->getGarden(),
            $specimen->getVoucher()?->getName(),
            $this->specimenService->getCollectionText($specimen),
            $specimen->getCollector()?->getName(),
            $specimen->getNumber(),
            $specimen->getCollector2()?->getName(),
            $specimen->getAltNumber(),
            $specimen->getSeries()?->getName(),
            $specimen->getSeriesNumber(),
            $specimen->getDate(),
            $specimen->getDate2(),
            $specimen->getCountry()->getNameEng(),
            $specimen->getProvince()?->getName(),
            $specimen->getRegion(),
            $specimen->getLatitude() ? number_format(round($specimen->getLatitude(), 9), 9) . '°' : '',
            $latDMS,
            $specimen->getHemisphereLatitude(),
            ($specimen->getHemisphereLatitude() === 'N') ? $specimen->degreeN : $specimen->degreeS,
            ($specimen->getHemisphereLatitude() === 'N') ? $specimen->minuteN : $specimen->minuteS,
            ($specimen->getHemisphereLatitude() === 'N') ? $specimen->secondN : $specimen->secondS,
            $specimen->getLongitude() ? number_format(round($specimen->getLongitude(), 9), 9) . '°' : '',
            $lonDMS,
            $specimen->getHemisphereLongitude(),
            ($specimen->getHemisphereLongitude() === 'E') ? $specimen->degreeE : $specimen->degreeW,
            ($specimen->getHemisphereLongitude() === 'E') ? $specimen->degreeE : $specimen->degreeW,
            ($specimen->getHemisphereLongitude() === 'E') ? $specimen->degreeE : $specimen->degreeW,
            $specimen->exactness,
            $specimen->getAltitudeMin(),
            $specimen->getAltitudeMax(),
            $specimen->quadrant,
            $specimen->quadrantSub,
            $specimen->getLocality(),
            $specimen->getDetermination(),
            $specimen->getTaxonAlternative(),
            /**
             * formerly was the "=" character removed, now only prepend apostrophe -> force cell as a string to prevent a starting "=" be interpreted as a formula
             */
            ((str_starts_with((string)$specimen->getAnnotation(), '=')) ? "'" : "") . $specimen->getAnnotation(),
            ((str_starts_with((string)$specimen->getHabitat(), '=')) ? "'" : "") . $specimen->getHabitat(),
            ((str_starts_with((string)$specimen->getHabitus(), '=')) ? "'" : "") . $specimen->getHabitus(),
            $this->specimenService->getStableIdentifier($specimen)
        ];

    }

    public function getKmlExport(): string
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
            WHERE specimen_ID IN (:specimenIDs) and s.accessible = 1";
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
