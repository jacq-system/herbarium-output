<?php declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiEndpointTest extends WebTestCase
{
    public function testApiWithExamples(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/services/doc.json');
        $this->assertResponseIsSuccessful();
        $apiDoc = json_decode($client->getResponse()->getContent(), true);
        foreach ($apiDoc['paths'] as $path => $methods) {
            foreach ($methods as $method => $details) { //TODO - only get e
                $data = $this->prepareRequest($path, $details);
                $client->request(strtoupper($method), $data["path"]);//, $data["parameters"]);
                $response = $client->getResponse();

//                $this->assertTrue(
//                    in_array($response->getStatusCode(), [200, 302]),
//                    sprintf("Route '%s' with method '%s' did not return expected status code.", $data["path"], $method)
//                );
                $expectedStatusCode =  200;
                $this->assertEquals(
                    $expectedStatusCode,
                    $response->getStatusCode(),
                    sprintf("Route '%s' with method '%s' did not return expected status code.", $data["path"], $method)
                );

            }
        }
    }

    protected function prepareRequest($path, $details)
    {
        $url = $path;
        $queryParams = [];
        $pathParams = [];

        foreach ($details['parameters'] as $parameter) {
            $paramName = $parameter['name'];
            $exampleValue = $parameter['example'] ?? '';

            if ($parameter['in'] === 'path') {
                $pathParams[$paramName] = $exampleValue;
            } elseif ($parameter['in'] === 'query') {
                $queryParams[$paramName] = $exampleValue;
            }
        }

        //  /users/{id} → /users/1)
        foreach ($pathParams as $name => $value) {
            $url = str_replace('{' . $name . '}', (string) $value, $url);
        }
        return ["path" => $url, "parameters" => $queryParams];
    }

}
