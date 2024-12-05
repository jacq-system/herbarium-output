<?php declare(strict_types=1);

namespace App\Facade;

use App\Entity\Jacq\Herbarinput\Specimens;
use App\Service\InstitutionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class SearchFormFacade
{
    protected array $sessionData;

    protected QueryBuilder $queryBuilder;
//SELECT * FROM `tbl_specimens`WHERE specimen_ID = 433274;

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
            $this->queryInstitution($this->sessionData['institution']);

        }

        //keep herbNr bellow the institution - guessing institution should happen only when it is not set directly
        if (!empty($this->sessionData['herbNr'])) {
            $this->queryHerbNr($this->sessionData['herbNr']);
        }

        if (!empty($this->sessionData['collection'])) {
            $this->queryBuilder->andWhere('s.collection = :collection')
                ->setParameter('collection', $this->sessionData['collection']);
        }

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
            } catch (\Exception $exception) {
            }
            $this->queryBuilder->setParameter('herbNr', '%' . $matches['rest']);
        } else {
            $this->queryBuilder->setParameter('herbNr', '%' . $value);
        }

    }


    public function countResults($sessionData): int
    {
        $this->sessionData = $sessionData;
        $this->buildQuery();
        return $this->queryBuilder->select('count(DISTINCT s.id)')->getQuery()->getSingleScalarResult();
    }


}
