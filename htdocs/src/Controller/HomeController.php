<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\User;
use App\Service\Rest\DevelopersService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(protected DevelopersService $developersService)
    {
    }

    #[Route('/', name: 'app_front_index')]
    public function index(): Response
    {
        // the template path is the relative file path from `templates/`
        return $this->render('front/home/index.html.twig', [
            // this array defines the variables passed to the template,
            // where the key is the variable name and the value is the variable value
            // (Twig recommends using snake_case variable names: 'foo_bar' instead of 'fooBar')

        ]);
    }

    #[Route('/database', name: 'app_front_database')]
    public function database(): Response
    {
        // the template path is the relative file path from `templates/`
        return $this->render('front/home/database.html.twig', [
            // this array defines the variables passed to the template,
            // where the key is the variable name and the value is the variable value
            // (Twig recommends using snake_case variable names: 'foo_bar' instead of 'fooBar')

        ]);
    }

    #[Route('/collections', name: 'app_front_collections')]
    public function collections(): Response
    {
        // the template path is the relative file path from `templates/`
        return $this->render('front/home/collections.html.twig', [
            // this array defines the variables passed to the template,
            // where the key is the variable name and the value is the variable value
            // (Twig recommends using snake_case variable names: 'foo_bar' instead of 'fooBar')

        ]);
    }
    #[Route('/systems', name: 'app_front_systems')]
    public function systems(): Response
    {
        // the template path is the relative file path from `templates/`
        return $this->render('front/home/systems.html.twig', [
            // this array defines the variables passed to the template,
            // where the key is the variable name and the value is the variable value
            // (Twig recommends using snake_case variable names: 'foo_bar' instead of 'fooBar')

        ]);
    }

    #[Route('/imprint', name: 'app_front_imprint')]
    public function imprint(): Response
    {
        // the template path is the relative file path from `templates/`
        return $this->render('front/home/imprint.html.twig', [
            // this array defines the variables passed to the template,
            // where the key is the variable name and the value is the variable value
            // (Twig recommends using snake_case variable names: 'foo_bar' instead of 'fooBar')

        ]);
    }

    #[Route('/develop', name: 'deve_rest')]
    public function indexDevelop(): Response
    {
        $data = $this->developersService->testApiWithExamples();
        return $this->render('front/home/develop.html.twig', ["results"=>$data]);
    }

    #[Route('/api/test', name: 'app_api_test')]
    public function apiTest(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        return $this->json([
            'message' => 'You successfully authenticated!',
            'email' => $user->getEmail(),
        ]);
    }

    #[Route('.well-known/jwks.json', name: 'app_jwks', methods: ['GET'])]
    public function jwks(): Response
    {
        // Load the public key from the filesystem and use OpenSSL to parse it.
        $kernelDirectory = $this->getParameter('kernel.project_dir');
        $publicKey = openssl_pkey_get_public(file_get_contents($kernelDirectory . '/config/jwt/public.pem'));
        $details = openssl_pkey_get_details($publicKey);
        $jwks = [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'use' => 'sig',
                    'kid' => '1',
                    'n' => strtr(rtrim(base64_encode($details['rsa']['n']), '='), '+/', '-_'),
                    'e' => strtr(rtrim(base64_encode($details['rsa']['e']), '='), '+/', '-_'),
                ],
            ],
        ];
        return $this->json($jwks);
    }
}
