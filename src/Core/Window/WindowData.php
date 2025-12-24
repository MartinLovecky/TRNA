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
            ['command' => '/track',   'description' => 'Track commands must start with /track'],
            ['command' => 'playtime', 'description' => 'Check your playtime statistics'],
            ['command' => 'time',     'description' => 'Check current server time'],
            ['command' => 'info',     'description' => 'Get information about the current map'],
            ['command' => 'help',     'description' => 'Show this help menu'],
        ];
    }

    private function tmxvHelp(): array
    {
        return [
            ['command' => '/tmxv',  'description' => 'Tmxv commands must start with /tmxv'],
            ['command' => 'help',   'description' => 'Show this help menu'],
            ['command' => 'video',  'description' => 'Same as gps'],
            ['command' => 'videos', 'description' => 'Same as gps list'],
            ['command' => 'gps',    'description' => 'Gives the latest video in chat'],
            ['command' => '-||- list',   'description' => 'Gives all videos in a window'],
            ['command' => '-||- latest', 'description' => 'Gives the latest video in chat'],
            ['command' => '-||- oldest', 'description' => 'Gives the oldest video in chat'],
        ];
    }
}
