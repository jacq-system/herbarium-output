<?php declare(strict_types=1);

namespace App\Service\Rest;


use App\Entity\Jacq\Herbarinput\ExternalServices;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExternalScinamesService
{



    public function __construct(protected readonly EntityManagerInterface $entityManager,  protected HttpClientInterface $httpClient)
    {
    }

    public function searchAll(string $term): array
    {
        $this->scinames['searchString'] = $term;
        $this->scinames['results'] = array();

        $responses = [];
        foreach ($this->externalServices as $key => $externalService) {
            try {
                $responses[$key] = $this->httpClient->request('GET', $externalService['url'] . urlencode($term), [
                    'timeout' => 8,
                ]);
            } catch (TransportExceptionInterface $e) {
                $this->scinames['results'][$this->externalServices[$key]['code']]['error'] = $e->getMessage();
            }
        }
        foreach ($responses as $key => $response) {
            $this->scinames['results'][$this->externalServices[$key]['code']] = [
                'match'      => [],
                'candidates' => [],
                'serviceID'  => $this->externalServices[$key]['serviceID'],
                'name'       => $this->entityManager
                    ->getRepository(ExternalServices::class)
                    ->find($this->externalServices[$key]['serviceID']),
                'error'      => null,
            ];

            try {
                $statusCode = $response->getStatusCode();
                $content = $response->getContent(); // exception if not 2xx
                $result = json_decode($content, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

                switch ($this->externalServices[$key]['code']) {
                    case 'gbif':
                        $this->gbif_read($result);
                        break;
                    case 'wfo':
                        $this->wfo_read($result);
                        break;
                    case 'worms':
                        if ($statusCode === 200) {
                            $this->worms_read($result);
                        }
                        break;
                }
            } catch (\Throwable $e) {
                $this->scinames['results'][$this->externalServices[$key]['code']]['error'] = $e->getMessage();
            }
        }
        return $this->scinames;
    }

    /**
     * read GBIF and store data into internal array, needs just the result of the service
     *
     * @param array $result given result of the service, json decoded
     * @return void
     */
    private function gbif_read(array $result): void
    {
        if (isset($result['count']) && $result['count'] > 0) {
            if ($result['count'] === 1) {
                $this->scinames['results']['gbif']['match'] = array('id'    => $result['results'][0]['key'],
                    'name'  => $result['results'][0]['scientificName']);
            } else {
                foreach ($result['results'] as $candidate) {
                    $this->scinames['results']['gbif']['candidates'][] = array('id'   => $candidate['key'],
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
    private function wfo_read($result): void
    {
        if (!empty($result['match'])) {
            $this->scinames['results']['wfo']['match'] = array('id'    => $result['match']['wfo_id'],
                'name'  => $result['match']['full_name_plain']);
        } elseif (!empty($result['candidates'])) {
            foreach ($result['candidates'] as $candidate) {
                $this->scinames['results']['wfo']['candidates'][] = array('id'   => $candidate['wfo_id'],
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
    private function worms_read(array $result): void
    {
        if (count($result) > 1) {
            foreach ($result as $candidate) {
                $this->scinames['results']['worms']['candidates'][] = array('id'   => $candidate['AphiaID'],
                    'name' => $candidate['scientificname']);
            }
        } else {
            $this->scinames['results']['worms']['match'] = array('id'   => $result[0]['AphiaID'],
                'name' => $result[0]['scientificname']);
        }
    }
}
