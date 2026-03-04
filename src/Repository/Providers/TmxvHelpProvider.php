<?php

declare(strict_types=1);

use Yuha\Trna\Core\Enums\Window;
use Yuha\Trna\Core\Window\WindowDataProvider;

final class TmxvHelpProvider implements WindowDataProvider
{
    public function getData(
        ?string $login = null,
        ?Window $window = null,
        ?array $context = null
    ): array {
        return [
            ['cmd' => '/tmxv',       'des' => 'Tmxv commands must start with /tmxv'],
            ['cmd' => 'help',        'des' => 'Show this help menu'],
            ['cmd' => 'video',       'des' => 'Same as gps'],
            ['cmd' => 'videos',      'des' => 'Same as gps list'],
            ['cmd' => 'gps',         'des' => 'Gives the latest video in chat'],
            ['cmd' => '-||- list',   'des' => 'Gives all videos in a window'],
            ['cmd' => '-||- latest', 'des' => 'Gives the latest video in chat'],
            ['cmd' => '-||- oldest', 'des' => 'Gives the oldest video in chat'],
        ];
    }
}
