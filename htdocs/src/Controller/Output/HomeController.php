<?php declare(strict_types=1);

namespace App\Controller\Output;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{


    #[Route('/', name: 'output_index')]
    public function index(): Response
    {
        return $this->render('output/home/index.html.twig');
    }

    #[Route('/collections', name: 'output_collections')]
    public function collections(): Response
    {
        return $this->render('output/home/collections.html.twig');
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

}
