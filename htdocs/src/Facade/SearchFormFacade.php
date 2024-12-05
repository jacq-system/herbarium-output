<?php declare(strict_types=1);

namespace App\Facade;

use App\Controller\HomeController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class SearchFormFacade
{
    public function __construct(protected  EntityManagerInterface $entityManager, protected RequestStack $requestStack)
    {
    }

    public function search(): array
    {
        $result = [];
        $session = $this->requestStack->getSession()->get(HomeController::SESSION_NAMESPACE);
        return $result;
    }



}
