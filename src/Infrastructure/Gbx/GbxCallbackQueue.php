<?php

declare(strict_types=1);

namespace Yuha\Trna\Infrastructure\Gbx;

use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Infrastructure\Xml\Response;

final class GbxCallbackQueue
{
    private array $queue = [];

    public function __construct(
        private GbxConnection $connection,
        private Response $response
    ) {
    }

    public function poll(): void
    {
        $packet = $this->connection->readCallbackPacket();
        if (!$packet) {
            return;
        }

        $this->queue[] = $this->response->processCallback($packet);
    }

    public function pop(): ?TmContainer
    {
        return array_shift($this->queue) ?: null;
    }
}
