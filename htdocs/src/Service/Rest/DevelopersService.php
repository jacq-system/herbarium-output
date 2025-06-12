<?php declare(strict_types=1);

namespace App\Service\Rest;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class DevelopersService
{
    protected const array Domains = ["https://jacq.dyn.cloud.e-infra.cz/", "https://services.jacq.org/jacq-"];

    public function __construct(protected readonly EntityManagerInterface $entityManager, protected HttpClientInterface $client, protected RouterInterface $router)
    {
    }

    public function testApiWithExamples(): array
    {
        $results = [];
//        $symfonySwaggerPath = $this->router->generate("app.swagger", [], UrlGeneratorInterface::ABSOLUTE_URL);
        $responseSwagger = $this->client->request('GET', 'https://jacqtest.dyn.cloud.e-infra.cz/services/doc.json');
        $apiDoc = json_decode($responseSwagger->getContent(), true);
        $i=0;
        foreach ($apiDoc['paths'] as $path => $methods) {
            $i++;
//            if($i>40){ continue;}
            foreach ($methods as $method => $details) {
                if ($method !== 'get') {
                    /** testing only GET to be easy */
                    continue;
                }
                foreach (self::Domains as $domain) {
                    $rawRequest = $this->prepareRequest($domain . ltrim($path, '/'), $details);
                    $individualResponse = $this->client->request(strtoupper($method), $rawRequest["path"], ["query" => $rawRequest["parameters"], 'headers' => [
                        'Accept' => 'application/json',
                    ]]);
                    if ($individualResponse->getStatusCode() == 200) {
                        $result = [
                            "code" => $individualResponse->getStatusCode(),
                            "content-type" => $individualResponse->getHeaders()['content-type'][0],
                            "content" => $individualResponse->getContent(),
                            "url" => $individualResponse->getInfo("url")
                        ];
                    } else {
                        $result = [
                            "code" => $individualResponse->getStatusCode(),
                            "content-type" => "",
                            "content" => "",
                            "url" => $individualResponse->getInfo("url")
                        ];
                    }

                    $results[$path][$domain] = $result;
                }

            }
        }

        return $results;
    }

    protected function prepareRequest($path, $details)
    {
        $url = $path;
        $queryParams = [];
        $pathParams = [];

        if (isset($details['parameters'])) {
            foreach ($details['parameters'] as $parameter) {
                $paramName = $parameter['name'];
                $exampleValue = $parameter['example'] ?? '';

                if ($parameter['in'] === 'path') {
                    $pathParams[$paramName] = $exampleValue;
                } elseif ($parameter['in'] === 'query') {
                    $queryParams[$paramName] = $exampleValue;
                }
            }
        }

        //  /users/{id} → /users/1
        foreach ($pathParams as $name => $value) {
            $url = str_replace('{' . $name . '}', (string)$value, $url);
        }
        return ["path" => $url, "parameters" => $queryParams];
    }


}
