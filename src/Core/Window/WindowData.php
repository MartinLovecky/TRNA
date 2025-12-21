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
            Panel::Skip => $this->skipData($player),
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
}
