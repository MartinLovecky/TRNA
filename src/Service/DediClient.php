<?php

declare(strict_types=1);

namespace Yuha\Trna\Service;

use Yuha\Trna\Infrastructure\Xml\Request;
use Yuha\Trna\Infrastructure\Xml\Response;

class DediClient
{
    private const string ENDPOINT = 'http://dedimania.net:8002/Dedimania';

    public function __construct(
        private HttpClient $httpClient,
        private Request $request,
        private Response $response
    ) {
    }

    public function request(string $type, array $params)
    {
        $xml = $this->request->createMultiCallRequest($params);
        $res = $this->httpClient->postXml(self::ENDPOINT, $xml);

        if (!$res) {
            return ['ok' => false, 'reason' => 'response_failed'];
        }

        $parsed = $this->response->processResponse($type, $res, true);

        dd($parsed);
    }
}
