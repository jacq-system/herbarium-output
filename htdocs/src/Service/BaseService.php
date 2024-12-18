<?php declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;

readonly abstract class BaseService
{
    public function __construct(protected EntityManagerInterface $entityManager, protected RouterInterface $router)
    {
    }

    protected function query(string $sql, array $params = [], array $types = []): Result
    {
        return $this->entityManager->getConnection()->executeQuery($sql, $params, $types);
   }

}
