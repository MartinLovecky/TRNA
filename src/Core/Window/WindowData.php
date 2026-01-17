<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Window;

use Yuha\Trna\Core\Controllers\VoteController;
use Yuha\Trna\Core\Enums\Panel;
use Yuha\Trna\Core\TmContainer;

class WindowData
{
    public function __construct(private VoteController $voteController)
    {
    }

    public function getData(Panel $panel, TmContainer $player): array
    {
        return match ($panel) {
            Panel::Skip  => $this->skipData($player),
            Panel::Track => $this->trackHelp(),
            Panel::Tmxv  => $this->tmxvHelp(),
            Panel::Cpll  => $this->cpllHelp(),
            default => []
        };
    }

    private function skipData(TmContainer $player): array
    {
        $status = $this->voteController->status();

        if (isset($status['reason'])) {
            return [];
        }

        return [
            'actions'   => $status['actions'],
            'isAdmin'   => $player->get('isAdmin'),
            'remaining' => $this->voteController->tick(),
            'yes'       => $status['yes'],
            'no'        => $status['no'],
        ];
    }

    private function trackHelp(): array
    {
        return [
            ['cmd' => '/track',   'des' => 'Track commands must start with /track'],
            ['cmd' => 'playtime', 'des' => 'Check your playtime statistics'],
            ['cmd' => 'time',     'des' => 'Check current server time'],
            ['cmd' => 'info',     'des' => 'Get information about the current map'],
            ['cmd' => 'help',     'des' => 'Show this help menu'],
            ///
            ['cmd' => '/tmxv',       'des' => 'Tmxv commands must start with /tmxv'],
            ['cmd' => 'help',        'des' => 'Show this help menu'],
            ['cmd' => 'video',       'des' => 'Same as gps'],
            ['cmd' => 'videos',      'des' => 'Same as gps list'],
            ['cmd' => 'gps',         'des' => 'Gives the latest video in chat'],
            ['cmd' => '-||- list',   'des' => 'Gives all videos in a window'],
            ['cmd' => '-||- latest', 'des' => 'Gives the latest video in chat'],
            ['cmd' => '-||- oldest', 'des' => 'Gives the oldest video in chat'],
            ['cmd' => '/tmxv',       'des' => 'Tmxv commands must start with /tmxv'],
            ['cmd' => 'help',        'des' => 'Show this help menu'],
            ['cmd' => 'video',       'des' => 'Same as gps'],
            ['cmd' => 'videos',      'des' => 'Same as gps list'],
            ['cmd' => 'gps',         'des' => 'Gives the latest video in chat'],
            ['cmd' => '-||- list',   'des' => 'Gives all videos in a window'],
            ['cmd' => '-||- latest', 'des' => 'Gives the latest video in chat'],
            ['cmd' => '-||- oldest', 'des' => 'Gives the oldest video in chat'],
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

    private function tmxvHelp(): array
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

    private function cpllHelp(): array
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
}
