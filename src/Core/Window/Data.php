<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Window;

use Yuha\Trna\Core\Controllers\VoteController;
use Yuha\Trna\Core\Enums\Window;

class Data
{
    public function __construct(private VoteController $voteController)
    {
    }

    public function getData(Window $window): array
    {
        return match ($window) {
            Window::Skip  => $this->skipData(),
            Window::Track => $this->trackHelp(),
            Window::Tmxv  => $this->tmxvHelp(),
            Window::Cpll  => $this->cpllHelp(),
            Window::JUKE_LIST => $this->jukeList(),
            default => []
        };
    }

    public function skipData(): array
    {
        $status = $this->voteController->status();

        if (isset($status['reason'])) {
            return [];
        }

        return [
            'actions'   => $status['actions'],
            'isAdmin'   => $status['isAdmin'],
            'remaining' => $this->voteController->tick(),
            'yes'       => $status['yes'],
            'no'        => $status['no'],
        ];
    }

    public function trackHelp(): array
    {
        return [
            ['cmd' => '/track',   'des' => 'Track commands must start with /track'],
            ['cmd' => 'playtime', 'des' => 'Check your playtime statistics'],
            ['cmd' => 'time',     'des' => 'Check current server time'],
            ['cmd' => 'info',     'des' => 'Get information about the current map'],
            ['cmd' => 'help',     'des' => 'Show this help menu'],
        ];
    }

    public function tmxvHelp(): array
    {
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

    public function cpllHelp(): array
    {
        return [
            ['cmd' => '/cpll',        'des' => 'CPLL commands must start with /cpll'],
            ['cmd' => 'help',         'des' => 'Show this help menu'],
            ['cmd' => 'cp',           'des' => 'Show current checkpoint live list'],
            ['cmd' => 'mycp',         'des' => 'Show your current checkpoint status'],
            ['cmd' => 'on',           'des' => '[Admin] Enable CPLL'],
            ['cmd' => 'off',          'des' => '[Admin] Disable CPLL'],
            ['cmd' => 'filter on',    'des' => '[Admin] Hide spectators from the list'],
            ['cmd' => 'filter off',   'des' => '[Admin] Show spectators in the list'],
        ];
    }

    public function jukeList(): array
    {
        return [];
    }
}
