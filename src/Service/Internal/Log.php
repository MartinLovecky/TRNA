<?php

declare(strict_types=1);

namespace Yuha\Trna\Service\Internal;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\{Level, Logger};
use Yuha\Trna\Core\Server;

/**
 * @internal This class is not meant to be used directly.
 *           Use the LoggerAware trait instead.
 */
final class Log
{
    /** @var array<string, Logger> */
    private static array $loggers = [];
    /** @internal */
    public static function getLogger(string $channel = 'trackmania'): Logger
    {
        if (!isset(self::$loggers[$channel])) {
            $logger = new Logger($channel);

            $handler = new StreamHandler(
                Server::$logsDir . "{$channel}.log",
                Level::Debug,
            );

            $formatter = new JsonFormatter();
            $formatter->includeStacktraces(true);
            $formatter->setJsonPrettyPrint(true);

            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);

            self::$loggers[$channel] = $logger;
        }

        return self::$loggers[$channel];
    }
}
