<?php declare(strict_types=1);

namespace App\Controller\Output;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{


    #[Route('/', name: 'app_front_index')]
    public function index(): Response
    {
        return $this->render('front/home/index.html.twig');
    }

    #[Route('/collections', name: 'app_front_collections')]
    public function collections(): Response
    {
        return $this->render('front/home/collections.html.twig');
    }

    #[Route('/systems', name: 'app_front_systems')]
    public function systems(): Response
    {
        return $this->render('front/home/systems.html.twig');
    }

    #[Route('/imprint', name: 'app_front_imprint')]
    public function imprint(): Response
    {
        return $this->render('front/home/imprint.html.twig');
    }

}
