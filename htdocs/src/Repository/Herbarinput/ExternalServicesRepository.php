<?php declare(strict_types=1);

namespace App\Repository\Herbarinput;

use App\Entity\Jacq\Herbarinput\ExternalServices;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class ExternalServicesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalServices::class);
    }

    /**
     * @return ExternalServices[]
     */
    public function getCallableServices(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.apiUrl IS NOT NULL')
            ->andWhere('l.apiCode IS NOT NULL')
            ->getQuery()->getResult();
    }


}
