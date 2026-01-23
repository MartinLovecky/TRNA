<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\Enums\GameMode;
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Repository\Challenge;

class Checkpoints
{
    public function __construct(private Challenge $challenge)
    {
    }

    public function onPlayerConnect(TmContainer $player)
    {
        $cps = TmContainer::fromArray([

        ]);
    }

    public function onPlayerInfoChanged(TmContainer $player)
    {
        if (
            $this->challenge->gameMode() === GameMode::Stunts
            || $player->get('prevstatus') === $player->get('IsSpectator')
        ) {
            return;
        }

        //display_cpspanel($login, 0, '$00f -.--');
    }
}
