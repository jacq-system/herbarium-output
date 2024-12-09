<?php declare(strict_types=1);

namespace App\Facade;

use App\Entity\Jacq\Herbarinput\Specimens;
use App\Entity\Jacq\Herbarinput\Typus;
use App\Service\InstitutionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Exception;

class SearchFormFacade
{
    protected array $sessionData;

    protected QueryBuilder $queryBuilder;

    public function __construct(protected readonly EntityManagerInterface $entityManager, protected readonly InstitutionService $institutionService)
    {
    }

    public function search($sessionData): array
    {
        $this->sessionData = $sessionData;
        $this->buildQuery();
        return $this->queryBuilder->getQuery()->getResult();
    }

    protected function buildQuery(): void
    {
        $this->queryBuilder = $this->entityManager->getRepository(Specimens::class)->createQueryBuilder('s')->setMaxResults(300000);

        if (!empty($this->sessionData['institution'])) {
            $this->queryInstitution((int)$this->sessionData['institution']);
        }

        if (!empty($this->sessionData['herbNr'])) {
            $this->queryHerbNr($this->sessionData['herbNr']);
        }

        if (!empty($this->sessionData['collection'])) {
            $this->queryBuilder->andWhere('s.collection = :collection')
                ->setParameter('collection', $this->sessionData['collection']);
        }

        if (!empty($this->sessionData['collectorNr'])) {
            $this->queryCollectorNr($this->sessionData['collectorNr']);
        }

        if (!empty($this->sessionData['collector'])) {
            $this->queryCollector($this->sessionData['collector']);
        }

        if (!empty($this->sessionData['collectionDate'])) {
            $this->queryBuilder->andWhere('s.date LIKE :collectionDate')
                ->setParameter('collectionDate', '%' . $this->sessionData['collectionDate'] . "%");
        }

        if (!empty($this->sessionData['collectionNr'])) {
            $this->queryBuilder->andWhere('s.collectionNumber LIKE :collectionNr')
                ->setParameter('collectionNr', '%' . $this->sessionData['collectionNr'] . "%");
        }

        if (!empty($this->sessionData['series'])) {
            $this->querySeries($this->sessionData['series']);
        }

        if (!empty($this->sessionData['locality'])) {
            $this->queryLocality($this->sessionData['locality']);
        }

        if (!empty($this->sessionData['habitus'])) {
            $this->queryBuilder->andWhere('s.collection LIKE :habitus')
                ->setParameter('habitus', '%' . $this->sessionData['habitus'] . '%');
        }

        if (!empty($this->sessionData['habitat'])) {
            $this->queryBuilder->andWhere('s.collection LIKE :habitat')
                ->setParameter('habitat', '%' . $this->sessionData['habitat'] . '%');
        }

        if (!empty($this->sessionData['annotation'])) {
            $this->queryBuilder->andWhere('s.collection LIKE :annotation')
                ->setParameter('annotation', '%' . $this->sessionData['annotation'] . '%');
        }

        if (!empty($this->sessionData['country'])) {
            $this->queryCountry($this->sessionData['country']);
        }

        if (!empty($this->sessionData['province'])) {
            $this->queryProvince($this->sessionData['province']);
        }

        if (!empty($this->sessionData['onlyType'])) {
            $this->queryType();
        }

        if (!empty($this->sessionData['onlyImages'])) {
            $this->queryImages();
        }
    }

    protected function queryImages(): void
    {
        $this->queryBuilder
            ->andWhere($this->queryBuilder->expr()->orX(
                $this->queryBuilder->expr()->eq('s.image', 1),
                $this->queryBuilder->expr()->eq('s.imageObservation', 1)
            ));
    }

    protected function queryInstitution(int $id): void
    {
        $this->queryBuilder
            ->join('s.collection', 'c')
            ->join('c.institution', 'i')
            ->andWhere('i.id = :institution')
            ->setParameter('institution', $id);
    }

    /**
     * simplified. Original code searched also in collectorNr for example. Let's use just trim the institution code and do simple fulltext
     */
    protected function queryHerbNr(string $value): void
    {
        $pattern = '/^(?<code>[a-zA-Z]+)\s+(?<rest>.*)$/';
        $this->queryBuilder->andWhere('s.herbNumber LIKE :herbNr');
        if (preg_match($pattern, $value, $matches) && empty($this->sessionData['institution'])) {
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

    public function countResults($sessionData): int
    {
        $this->sessionData = $sessionData;
        $this->buildQuery();
        return $this->queryBuilder->select('count(DISTINCT s.id)')->getQuery()->getSingleScalarResult();
    }


}
