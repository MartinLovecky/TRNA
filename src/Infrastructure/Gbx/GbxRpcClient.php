<?php

declare(strict_types=1);

namespace Yuha\Trna\Infrastructure\Gbx;

use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Infrastructure\Xml\{Request, Response};

final class GbxRpcClient
{
    public function __construct(
        private GbxConnection $connection,
        private Request $request,
        private Response $response
    ) {
    }

    public function query(string $method, array $params = []): TmContainer
    {
        $xml = $this->request->createRpcRequest($method, $params);
        $handle = $this->connection->send($xml);
        $raw = $this->connection->waitFor($handle);

        $result = $this->response->processResponse($method, $raw);

        if ($result->has('result.faultString')) {
            throw new \Exception(
                "{$method} error {$result->get('faultString')}",
            );
        }

        return $result;
    }

    public function multicall(array $calls): array
    {
        $xml = $this->request->createMultiCallRequest($calls);
        $handle = $this->connection->send($xml);
        $raw = $this->connection->waitFor($handle);

        $response = $this->response
            ->processResponse('system.multicall', $raw, true, $calls);

        $results = $response->get('result', []);
        $mapped = [];

        foreach ($results as $i => $res) {
            $mapped[$calls[$i]['methodName']] = $res;
        }

        return $mapped;
    }
}
