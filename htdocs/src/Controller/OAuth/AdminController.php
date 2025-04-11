<?php declare(strict_types = 1);

namespace App\Controller\OAuth;

use App\Service\Rest\DevelopersService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    public function __construct(protected DevelopersService $developersService)
    {
    }

    #[Route('/admin', name: 'app_admin_index')]
    public function index(): Response
    {
        // the template path is the relative file path from `templates/`
        return $this->render('admin/home/default.html.twig', [
            // this array defines the variables passed to the template,
            // where the key is the variable name and the value is the variable value
            // (Twig recommends using snake_case variable names: 'foo_bar' instead of 'fooBar')

        ]);
    }


}
