<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\User;
use App\Service\DjatokaService;
use App\Service\Rest\DevelopersService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(protected DevelopersService $developersService, protected readonly DjatokaService $djatokaService)
    {
    }

    #[Route('/', name: 'app_front_index')]
    public function index(): Response
    {
        return $this->render('front/home/index.html.twig');
    }

    #[Route('/database', name: 'app_front_database')]
    public function database(): Response
    {
        return $this->render('front/home/database.html.twig');
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

    #[Route('/jacqStatistics', name: 'app_front_jacqStatistics')]
    public function jacqStatistics(): Response
    {
        return $this->render('front/home/statistics.html.twig');
    }

    #[Route('/checkDjatokaServers', name: 'app_front_checkDjatokaServers', defaults: ['source' => null])]
    public function checkDjatokaServers(?string $source): Response
    {
        $data = $this->djatokaService->getData($source);
        $warn = $data['warn'] ?? null;
        $ok = $data['ok'] ?? null;
        $fail = $data['fail'] ?? null;
        $noPicture = $data['noPicture'] ?? null;

        return $this->render('front/home/djatokaCheck.html.twig', ["warn"=>$warn,"ok"=>$ok,"fail"=>$fail,"noPicture"=>$noPicture]);
    }

    #[Route('/develop/rest', name: 'deve_rest')]
    public function indexDevelopRest(): Response
    {
        $data = $this->developersService->testApiWithExamples();
        return $this->render('front/develop/rest.html.twig', ["results"=>$data]);
    }

    #[Route('/develop', name: 'deve_overview')]
    public function indexDevelop(): Response
    {
        return $this->render('front/develop/default.html.twig');
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
