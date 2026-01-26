<?php declare(strict_types=1);

namespace App\Controller;

use JACQ\Repository\Herbarinput\CountryRepository;
use JACQ\Repository\Herbarinput\InstitutionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{

    public function __construct(protected readonly CountryRepository $countryRepository, protected readonly InstitutionRepository $institutionRepository)
    {
    }

    #[Route('/', name: 'output_index')]
    public function index(): Response
    {
        return $this->render('output/home/index.html.twig');
    }

    #[Route('/collections', name: 'output_collections')]
    public function collections(): Response
    {
        $countries = $this->countryRepository->findWithInstitutions();
        $institutions = $this->institutionRepository->getWithCoords();

        return $this->render('output/home/collections.html.twig', ["countries" => $countries, "institutions" => $institutions]);
    }

    #[Route('/systems', name: 'output_systems')]
    public function systems(): Response
    {
        return $this->render('output/home/systems.html.twig');
    }

    #[Route('/imprint', name: 'output_imprint')]
    public function imprint(): Response
    {
        return $this->render('output/home/imprint.html.twig');
    }

    #[Route('/version', name: 'version')]
    public function version(): Response
    {
        return $this->json($this->getParameter('app.version'));
    }

}
