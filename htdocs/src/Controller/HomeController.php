<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\CoreObjectsEnum;
use App\Enum\TimeIntervalEnum;
use App\Facade\Rest\IiifFacade;
use App\Facade\SearchFormFacade;
use App\Service\CollectionService;
use App\Service\DjatokaService;
use App\Service\InstitutionService;
use App\Service\Rest\DevelopersService;
use App\Service\Rest\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public const string SESSION_FILTERS = 'searchForm';
    public const string SESSION_SETTINGS = 'searchFormSettings';
    public const array RECORDS_PER_PAGE = array(10, 30, 50, 100);

    //TODO the name of taxon is not part of the query now, hard to sort
    public const array SORT = ["taxon"=> '', 'collector'=>'s.collector'];

    public function __construct(protected DevelopersService $developersService, protected readonly DjatokaService $djatokaService, protected readonly StatisticsService $statisticsService, protected readonly CollectionService $collectionService, protected readonly InstitutionService $herbariumService, protected readonly SearchFormFacade $searchFormFacade)
    {
    }

    #[Route('/', name: 'app_front_index')]
    public function index(): Response
    {
        return $this->render('front/home/index.html.twig');
    }

    #[Route('/database', name: 'app_front_database')]
    public function database(Request $request, SessionInterface $session, #[MapQueryParameter] bool $reset = false): Response
    {
        if ($reset) {
            $session->remove(self::SESSION_FILTERS);
            return $this->redirectToRoute($request->get('_route'));
        }
        $getData = $request->query->all();
        if (!empty($getData)) {
            $session->set(self::SESSION_FILTERS, $getData);
        }

        $institutions = $this->herbariumService->getAllAsPairs();
        $collections = $this->collectionService->getAllAsPairs();
        return $this->render('front/home/database.html.twig', ["institutions" => $institutions, 'collections' => $collections, "values" => $session->get(self::SESSION_FILTERS)]);
    }

    #[Route('/databaseSearch', name: 'app_front_databaseSearch', methods: ['POST'])]
    public function databaseSearch(Request $request, SessionInterface $session): Response
    {
        $postData = $request->request->all();
        $session->set(self::SESSION_FILTERS, $postData);

        $filters = $session->get(self::SESSION_FILTERS);
        $settings = $session->get(self::SESSION_SETTINGS);
        $pagination = $this->searchFormFacade->providePaginationInfo($filters, $settings);

        return $this->render('front/home/databaseSearch.html.twig', [
            "data" => $session->get(self::SESSION_FILTERS),
            'records' => $this->searchFormFacade->search($filters, $settings),
            'recordsCount' => $pagination["totalRecords"],
            'totalPages' => $pagination['totalPages'],
            'pages' => $pagination['pages'],
            'currentPage' => $pagination['currentPage'],
            'recordsPerPage' => self::RECORDS_PER_PAGE,
            "settings" => $settings]);
    }


    #[Route('/databaseSearchSettings', name: 'app_front_databaseSearchSettings', methods: ['GET'])]
    public function databaseSearchSettings(SessionInterface $session, #[MapQueryParameter] string $feature, #[MapQueryParameter] string $value): Response
    {
        $currentSettings = $session->get(self::SESSION_SETTINGS);
        switch ($feature) {
            case "page":
                $currentSettings["page"] = $value;
                $session->set(self::SESSION_SETTINGS, $currentSettings);
                break;
            case "recordsPerPage":
                $currentSettings["recordsPerPage"] = $value;
                $session->set(self::SESSION_SETTINGS, $currentSettings);
                break;
            case "sort":
                if (isset(self::SORT[$value])) {
                    $currentSettings["sort"] = self::SORT[$value];
                    $session->set(self::SESSION_SETTINGS, $currentSettings);
                }
                break;
            default:
                break;
        }
        return new JsonResponse($session->get(self::SESSION_SETTINGS));
    }

    #[Route('/collectionsSelectOptions', name: 'app_front_collectionsSelectOptions', methods: ['GET'])]
    public function collectionsSelectOptions(#[MapQueryParameter] int $herbariumID): Response
    {
        $result = $this->collectionService->getAllFromHerbariumAsPairs($herbariumID);

        return new JsonResponse($result);
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

    #[Route('/jacqStatisticsResults', name: 'app_front_jacqStatistics_results')]
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
        return $this->render('front/home/statistics_results.html.twig', ["results" => $data['results'], "periodMin" => $periodMin, "periodMax" => $periodMax, 'suma' => $periodSum]);
    }

    #[Route('/checkDjatokaServers', name: 'app_front_checkDjatokaServers', defaults: ['source' => null])]
    public function checkDjatokaServers(?string $source): Response
    {
        $data = $this->djatokaService->getData($source);
        $warn = $data['warn'] ?? null;
        $ok = $data['ok'] ?? null;
        $fail = $data['fail'] ?? null;
        $noPicture = $data['noPicture'] ?? null;

        return $this->render('front/home/djatokaCheck.html.twig', ["warn" => $warn, "ok" => $ok, "fail" => $fail, "noPicture" => $noPicture]);
    }

    #[Route('/develop/rest', name: 'deve_rest')]
    public function indexDevelopRest(): Response
    {
        $data = $this->developersService->testApiWithExamples();
        return $this->render('front/develop/rest.html.twig', ["results" => $data]);
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
