<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Traits;

use Monolog\Logger;
use Yuha\Trna\Service\Internal\Log;

trait LoggerAware
{
    private Logger $logger;

    protected function initLog(string $channel)
    {
        $this->logger = Log::getLogger($channel);
    }

    protected function logInfo(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    protected function logDebug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }
}
