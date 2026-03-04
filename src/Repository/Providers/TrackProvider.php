<?php

declare(strict_types=1);

use Yuha\Trna\Core\Enums\Window;
use Yuha\Trna\Core\Window\WindowDataProvider;

final class TrackProvider implements WindowDataProvider
{
    public function getData(
        ?string $login = null,
        ?Window $window = null,
        ?array $context = null
    ): array {
        return [
            ['cmd' => '/track',   'des' => 'Track commands must start with /track'],
            ['cmd' => 'playtime', 'des' => 'Check your playtime statistics'],
            ['cmd' => 'time',     'des' => 'Check current server time'],
            ['cmd' => 'info',     'des' => 'Get information about the current map'],
            ['cmd' => 'help',     'des' => 'Show this help menu'],
        ];
    }
}
