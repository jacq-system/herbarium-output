<?php declare(strict_types=1);

namespace App\Controller;

use App\Service\Rest\DevelopersService;
use App\Service\Tools\DjatokaService;
use App\Service\Tools\StatisticsService;
use JACQ\Enum\CoreObjectsEnum;
use JACQ\Enum\TimeIntervalEnum;
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
        return $this->render('output/tools/statistics.html.twig');
    }

    #[Route('/tools/jacqStatisticsResults', name: 'output_jacqStatistics_results')]
    public function jacqStatisticsResults(#[MapQueryParameter] string $periodStart, #[MapQueryParameter] string $periodEnd, #[MapQueryParameter] int $updated, #[MapQueryParameter] CoreObjectsEnum $type, #[MapQueryParameter] TimeIntervalEnum $interval): Response
    {
        $data = $this->statisticsService->getResults($periodStart, $periodEnd, $updated, $type, $interval);
        $periodMin = $data['periodMin'];
        $periodMax = $data['periodMax'];

        $periodSum = [];
        foreach ($data['results'] as $institution) {
            for ($i = $periodMin; $i <= $periodMax; $i++) {
                if (!isset($periodSum[$i])) {
                    $periodSum[$i] = 0;
                }
                $periodSum[$i] += $institution['stat'][$i];
            }
        }
        return $this->render('output/tools/statistics_results.html.twig', ["results" => $data['results'], "periodMin" => $periodMin, "periodMax" => $periodMax, 'suma' => $periodSum]);
    }

    #[Route('/tools', name: 'tools_overview')]
    public function indexTools(): Response
    {
        return $this->render('output/tools/default.html.twig');
    }

}
