<?php declare(strict_types=1);

namespace App\Controller\Output;

use App\Entity\User;
use App\Enum\CoreObjectsEnum;
use App\Enum\TimeIntervalEnum;
use App\Service\Rest\DevelopersService;
use App\Service\Tools\DjatokaService;
use App\Service\Tools\StatisticsService;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class ToolsController extends AbstractController
{

    public function __construct(protected DevelopersService $developersService, protected readonly DjatokaService $djatokaService, protected readonly StatisticsService $statisticsService)
    {
    }

    #[Route('/tools/statistics', name: 'app_tools_statistics')]
    public function jacqStatistics(): Response
    {
        return $this->render('front/tools/statistics.html.twig');
    }

    #[Route('/tools/jacqStatisticsResults', name: 'app_front_jacqStatistics_results')]
    public function jacqStatisticsResults(#[MapQueryParameter] string $periodStart, #[MapQueryParameter] string $periodEnd, #[MapQueryParameter] int $updated, #[MapQueryParameter] CoreObjectsEnum $type, #[MapQueryParameter] TimeIntervalEnum $interval): Response
    {
        $data = $this->statisticsService->getResults($periodStart, $periodEnd, $updated, $type, $interval);
        $periodMin = $data['periodMin'];
        $periodMax = $data['periodMax'];
        $periodSum = [];

        foreach ($data['results'] as $herbarium) {
            for ($i = $periodMin; $i <= $periodMax; $i++) {
                if (!isset($periodSum[$i])) {
                    $periodSum[$i] = 0;
                }
                $periodSum[$i] += $herbarium['stat'][$i];
            }
        }
        return $this->render('front/tools/statistics_results.html.twig', ["results" => $data['results'], "periodMin" => $periodMin, "periodMax" => $periodMax, 'suma' => $periodSum]);
    }

    #[Route('/tools/checkDjatokaServers', name: 'app_tools_checkDjatokaServers', defaults: ['source' => null])]
    public function checkDjatokaServers(#[MapQueryParameter] ?string $source): Response
    {
        try {
            $data = $this->djatokaService->getData($source);
        } catch (EntityNotFoundException $exception){
            $noRowError = $exception->getMessage();
            return $this->render('front/tools/djatokaCheck.html.twig', ["noRowError" => $noRowError]);
        }
        $warn = $data['warn'] ?? null;
        $ok = $data['ok'] ?? null;
        $fail = $data['fail'] ?? null;
        $noPicture = $data['noPicture'] ?? null;

        return $this->render('front/tools/djatokaCheck.html.twig', ["warn" => $warn, "ok" => $ok, "fail" => $fail, "noPicture" => $noPicture]);
    }

    #[Route('/tools/rest', name: 'tools_rest')]
    public function indexToolsRest(): Response
    {
        $data = $this->developersService->testApiWithExamples();
        return $this->render('front/tools/rest.html.twig', ["results" => $data]);
    }

    #[Route('/tools', name: 'tools_overview')]
    public function indexTools(): Response
    {
        return $this->render('front/tools/default.html.twig');
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
