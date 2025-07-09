<?php declare(strict_types=1);

namespace App\Facade;

use App\Controller\SearchFormController;
use App\Service\ExcelService;
use App\Service\KmlService;
use App\Service\SearchFormSessionService;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use JACQ\Entity\Jacq\Herbarinput\Institution;
use JACQ\Entity\Jacq\Herbarinput\Specimens;
use JACQ\Service\GeoService;
use JACQ\Service\SpecimenService;
use JACQ\Service\TypusService;

class SearchFormFacade
{
    public const int PAGINATION_RANGE = 3;
    protected QueryBuilder $queryBuilder;

    public function __construct(protected readonly EntityManagerInterface $entityManager, protected SearchFormSessionService $searchFormSessionService, protected readonly SpecimenService $specimenService, protected readonly TypusService $typusService, protected readonly KmlService $kmlService, protected readonly GeoService $geoService)
    {
    }

    public function search(): array
    {
        $this->buildQuery();

        $recordsPerPage = (int)$this->searchFormSessionService->getSetting('recordsPerPage', SearchFormController::RECORDS_PER_PAGE[0]);
        $page = (int)$this->searchFormSessionService->getSetting('page', 3);
        $offset = ($page - 1) * $recordsPerPage;

        $sort = $this->searchFormSessionService->getSort();
        if ($sort === null) {
            $this->defaultSort();
        } else {
            $this->sort($sort);
        }

        $this->queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($recordsPerPage);
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
            ->andWhere('s.accessibleForPublic = 1');

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

        if (!empty($this->searchFormSessionService->getFilter('onlyCoords'))) {
            $this->queryCoords();
        }

        if (!empty($this->searchFormSessionService->getFilter('taxon'))) {
            $this->queryTaxon($this->searchFormSessionService->getFilter('taxon'));
        }

    }

    protected function queryInstitution(int $code): void
    {
        $this->queryBuilder
            ->join('c.institution', 'i')
            ->andWhere('i.id = :institution')
            ->setParameter('institution', $code);
    }

    /**
     * complex (see https://github.com/jacq-system/herbarium-output/issues/36)
     */
    protected function queryHerbNr(string $value): void
    {
        $pattern = '/^(?<code>[a-zA-Z]+)\s*(?<rest>.*)$/';
        if (!preg_match($pattern, $value, $matches)) {
            //only number
            $this->queryBuilder->andWhere('s.herbNumber LIKE :herbNr');
            $this->queryBuilder->setParameter('herbNr', '%' . $value . '%');
        } else {
            if (empty($this->searchFormSessionService->getFilter('institution'))) {
                $institution = $this->entityManager->getRepository(Institution::class)->findOneBy(['code' => $matches['code']]);
                $this->queryInstitution($institution->getId());
            }
            $rest = trim($matches['rest']);
            $trailing = '';
            if (ctype_alpha(substr($rest, -1))) {
                for ($i = strlen($rest) - 2; $i >= 0; $i--) {
                    $checkChar = $rest[$i];
                    if (!ctype_alpha($checkChar) && $checkChar !== '-') {
                        break;
                    }
                }
                $trailing = substr($rest, $i + 1);
                $rest = substr($rest, 0, $i + 1);
            }

            $prefix = '';
            if (strpos($rest, '-') === 4) {// contents of search is ####-#... so, look also inside "CollNummer" (relevant for source-ID 6 = W)
                $prefix = substr($rest, 0, 5); // 1234-
                $rest = substr($rest, 5);
            }

            $number = (strlen($rest) >= 6) ? $rest : sprintf('%06d', (int)$rest);

            $like = $prefix . '%' . $number . $trailing;

            $this->queryBuilder->setParameter('herbNr', $like);

            if (!empty($prefix)) {
                $this->queryBuilder->andWhere(
                    $this->queryBuilder->expr()->orX(
                        $this->queryBuilder->expr()->like('s.herbNumber', ':herbNr'),
                        $this->queryBuilder->expr()->like('s.collectionNumber', ':herbNr')
                    ));
            } else {
                $this->queryBuilder->andWhere('s.herbNumber LIKE :herbNr');
            }
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
        /**
         * @note https://github.com/jacq-system/jacq-legacy/issues/4
         */
        $this->queryBuilder
            ->andWhere(
                's.isTypus IS NOT NULL'
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

    protected function queryCoords(): void
    {
        $this->queryBuilder
            ->andWhere($this->queryBuilder->expr()->orX(
                's.degreeS IS NOT NULL',
                's.degreeN IS NOT NULL'
            )
            );

    }

    protected function queryTaxon(string $id): void
    {
        $taxaIds = $this->getTaxonIds($id);
        $conditions = [];
        if (empty($taxaIds)) {
            $this->queryBuilder->andWhere('1 = 0');
        }

        //result includes NULL rows that need to be excluded
        $taxonId = array_filter(array_column($taxaIds, 'taxonID'), fn($value) => $value !== null);
        $basID = array_filter(array_column($taxaIds, 'basID'), fn($value) => $value !== null);
        $synID = array_filter(array_column($taxaIds, 'synID'), fn($value) => $value !== null);
        if (!empty($this->searchFormSessionService->getFilter('includeSynonym'))) {
            if (!empty($taxonId)) {
                $conditions[] = $this->queryBuilder->expr()->orX(
                    $this->queryBuilder->expr()->in('species.id', $taxonId),
                    $this->queryBuilder->expr()->in('species.basionym', $taxonId),
                    $this->queryBuilder->expr()->in('species.validName', $taxonId)
                );
            }

            if (!empty($basID)) {
                $conditions[] = $this->queryBuilder->expr()->orX(
                    $this->queryBuilder->expr()->in('species.id', $basID),
                    $this->queryBuilder->expr()->in('species.basionym', $basID),
                    $this->queryBuilder->expr()->in('species.validName', $basID)
                );
            }

            if (!empty($synID)) {
                $conditions[] = $this->queryBuilder->expr()->orX(
                    $this->queryBuilder->expr()->in('species.id', $synID),
                    $this->queryBuilder->expr()->in('species.basionym', $synID),
                    $this->queryBuilder->expr()->in('species.validName', $synID)
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

    protected function getTaxonIds(string $name): array
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

    protected function defaultSort(string $direction = Order::Ascending->value): void
    {
        $this->queryBuilder->orderBy('genus.name', $direction)
            ->addOrderBy('epithet.name', $direction)
            ->addOrderBy('author.name', $direction);
    }

    protected function sort(array $sort): void
    {
        $column = key($sort);
        $direction = $sort[$column];
        switch ($column) {
            case 'collector':
                $this->queryBuilder
                    ->join('s.collector', 'collector')
                    ->orderBy('collector.name', $direction);
                break;
            case 'date':
                $this->queryBuilder->orderBy('s.date', $direction);
                break;
            case 'location':
                $this->queryBuilder
                    ->join('s.country', 'country')
                    ->join('s.province', 'province')
                    ->orderBy('country.nameEng', $direction)
                    ->addOrderBy('province.name', $direction)
                    ->addOrderBy('s.locality', $direction);
                break;
            case 'typus':
                $this->queryBuilder
                    ->join('s.typus', 'typus')
                    ->join('typus.rank', 'rank')
                    ->orderBy('rank.latinName', $direction);
                break;
            case 'collection':
                $this->queryBuilder->orderBy('s.herbNumber', $direction);
                break;
            case 'coords':
                $this->queryBuilder
                    ->orderBy('s.degreeS', $direction)
                    ->addOrderBy('s.degreeN', $direction);
                break;
            case 'taxon':
                $this->defaultSort($direction);
        }

    }

    public function providePaginationInfo(): array
    {
        $totalRecordCount = $this->countResults();
        $recordsPerPage = (int)$this->searchFormSessionService->getSetting('recordsPerPage', SearchFormController::RECORDS_PER_PAGE[0]);
        $currentPage = (int)$this->searchFormSessionService->getSetting('page', 1);

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
        return $this->queryBuilder->resetDQLPart('orderBy')->select('count(DISTINCT s.id)')->getQuery()->getSingleScalarResult();
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

    public function searchForExport(?int $limit = null, bool $onlyWithCoords = false): array
    {
        $this->buildQuery();
        if ($limit) {
            $this->queryBuilder->setMaxResults($limit);
        }
        if ($onlyWithCoords) {
            $this->queryCoords();
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
            $specimen->getSpecies()->getAuthorSpecies()?->getName(),
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
            $specimen->getCountry()?->getNameEng(),
            $specimen->getProvince()?->getName(),
            $specimen->getRegion(),
            $specimen->getLatitude() ? number_format(round($specimen->getLatitude(), 9), 9) . '°' : '',
            $latDMS,
            $specimen->getHemisphereLatitude(),
            ($specimen->getHemisphereLatitude() === 'N') ? $specimen->getDegreeN() : $specimen->getDegreeS(),
            ($specimen->getHemisphereLatitude() === 'N') ? $specimen->getMinuteN() : $specimen->getMinuteS(),
            ($specimen->getHemisphereLatitude() === 'N') ? $specimen->getSecondN() : $specimen->getSecondS(),
            $specimen->getLongitude() ? number_format(round($specimen->getLongitude(), 9), 9) . '°' : '',
            $lonDMS,
            $specimen->getHemisphereLongitude(),
            ($specimen->getHemisphereLongitude() === 'E') ? $specimen->getDegreeE() : $specimen->getDegreeW(),
            ($specimen->getHemisphereLongitude() === 'E') ? $specimen->getMinuteE() : $specimen->getMinuteW(),
            ($specimen->getHemisphereLongitude() === 'E') ? $specimen->getSecondE() : $specimen->getSecondW(),
            $specimen->getExactness(),
            $specimen->getAltitudeMin(),
            $specimen->getAltitudeMax(),
            $specimen->getQuadrant(),
            $specimen->getQuadrantSub(),
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

    public function searchForKmlExport(bool $reduced = false): \Generator
    {
        $this->buildQuery();
        $this->queryCoords();
        if (!$reduced) {
            $this->queryBuilder->setMaxResults(KmlService::EXPORT_LIMIT);
        }
        $iterableResult = $this->queryBuilder->getQuery()->toIterable();
        $i = 0;
        foreach ($iterableResult as $row) {
            if ($reduced) {
                yield $this->kmlService->prepareRowReduced($row);
            } else {
                yield $this->kmlService->prepareRow($row);
            }
            if (++$i % 300 === 0) {
                $this->entityManager->clear();
            }
        }
        $this->entityManager->clear();
    }

}
