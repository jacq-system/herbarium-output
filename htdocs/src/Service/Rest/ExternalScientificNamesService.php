<?php declare(strict_types=1);

namespace App\Service\Rest;


use App\Entity\Jacq\Herbarinput\ExternalServices;
use App\Repository\Herbarinput\ExternalServicesRepository;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExternalScientificNamesService
{

    protected array $result = [];
    protected array $apiResponses = [];
    protected array $errors = [];
    protected string $searchString = '';
    const int ID_GBIF = 51;
    const int ID_WFO = 57;
    const int ID_WORMS = 58;

    public function __construct(protected readonly ExternalServicesRepository $servicesRepository,  protected HttpClientInterface $httpClient)
    {
    }

    public function searchAll(string $term): array
    {
        $this->searchString = $term;

        foreach ($this->servicesRepository->getCallableServices() as $externalService) {
            try {
                $this->apiResponses[$externalService->getId()] = $this->httpClient->request('GET', $externalService->getApiUrl() . urlencode($term), [
                    'timeout' => 8,
                ]);
            } catch (TransportExceptionInterface $e) {
                $this->errors[$externalService->getId()] = $e->getMessage();
            } finally {
                $this->result[$externalService->getApiCode()] = [
                    'match'      => [],
                    'candidates' => [],
                    'serviceID'  => $externalService->getId(),
                    'name'       => $externalService->getName(),
                    'error'      => $this->errors[$externalService->getId()] ?? null
                ];
            }

            $this->proceedResponse($externalService);
        }
        return [
            "searchString" => $this->searchString,
            "results"      => $this->result
        ];
    }

    protected function proceedResponse(ExternalServices $service): void
    {
        try {
            $response = $this->apiResponses[$service->getId()];
            $result = json_decode($response->getContent(), true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

            switch ($service->getId()) {
                case self::ID_GBIF:
                    $this->gbif_read($service, $result);
                    break;
                case self::ID_WFO:
                    $this->wfo_read($service, $result);
                    break;
                case self::ID_WORMS:
                    if ($response->getStatusCode() === 200) {
                        $this->worms_read($service, $result);
                    }
                    break;
            }
        } catch (\Throwable $e) {
            $this->result[$service->getName()]['error'] = $e->getMessage();
        }
    }

    /**
     * read GBIF and store data into internal array, needs just the result of the service
     *
     * @param array $result given result of the service, json decoded
     * @return void
     */
    private function gbif_read(ExternalServices $service, array $result): void
    {
        if (isset($result['count']) && $result['count'] > 0) {
            if ($result['count'] === 1) {
                $this->result[$service->getApiCode()]['match'] = array('id'    => $result['results'][0]['key'],
                    'name'  => $result['results'][0]['scientificName']);
            } else {
                foreach ($result['results'] as $candidate) {
                    $this->result[$service->getApiCode()]['candidates'][] = array('id'   => $candidate['key'],
                        'name' => $candidate['scientificName']);
                }
            }
        }
    }

    /**
     * read World Flora Online and store data into internal array, needs just the result of the service
     *
     * @param array $result given result of the service, json decoded
     * @return void
     */
    private function wfo_read(ExternalServices $service, $result): void
    {
        if (!empty($result['match'])) {
            $this->result[$service->getApiCode()]['match'] = array('id'    => $result['match']['wfo_id'],
                'name'  => $result['match']['full_name_plain']);
        } elseif (!empty($result['candidates'])) {
            foreach ($result['candidates'] as $candidate) {
                $this->result[$service->getApiCode()]['candidates'][] = array('id'   => $candidate['wfo_id'],
                    'name' => $candidate['full_name_plain']);
            }
        }
    }

    /**
     *  read World Register of Marine Species (VLIZ) and store data into internal array, needs just the result of the service
     *
     * @param array $result given result of the service, json decoded
     * @return void
     */
    private function worms_read(ExternalServices $service, array $result): void
    {
        if (count($result) > 1) {
            foreach ($result as $candidate) {
                $this->result[$service->getApiCode()]['candidates'][] = array('id'   => $candidate['AphiaID'],
                    'name' => $candidate['scientificname']);
            }
        } else {
            $this->result[$service->getApiCode()]['match'] = array('id'   => $result[0]['AphiaID'],
                'name' => $result[0]['scientificname']);
        }
    }
}
