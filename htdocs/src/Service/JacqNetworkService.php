<?php declare(strict_types=1);

namespace App\Service;

readonly class JacqNetworkService
{

    public function translateSymfonyToRealServicePath(string $generatedServiceAbsUrl): string
    {
        $parts = parse_url($generatedServiceAbsUrl);
        $parts['host'] = 'services.jacq.org';
        $segments = array_values(array_filter(explode('/', $parts['path'])));
        if (isset($segments[0])) {
            $segments[0] = 'jacq-services';
        }
        $parts['path'] = '/' . implode('/', $segments);
        return 'https://' . $parts['host'] . $parts['path'];

    }

}
