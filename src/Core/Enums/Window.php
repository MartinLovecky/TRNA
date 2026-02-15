<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Enums;

enum Window: int
{
    case Admin  = 20;
    case Track  = 21;
    case Skip   = 22;
    case Replay = 23;
    case Kick   = 24;
    case Tmxv   = 25;
    case Cpll   = 26;
    case Help   = 27;
    case JUKE_BOX = 28;
    case JUKE_LIST = 29;
    case Checkpoints = 30;

    public function template(): string
    {
        return match ($this) {
            self::Help   => 'tmxv/help',
            self::Skip   => 'votes/skip',
            self::Kick   => 'votes/kick',
            self::Replay => 'votes/replay',
            self::Cpll   => 'cpll/cps',
            self::Tmxv   => 'tmxv/video',
            self::JUKE_BOX => 'hud/juke_box',
            self::JUKE_LIST => 'juke/list',
            self::Checkpoints => 'hud/cps_panel',
            default      => throw new \RuntimeException("No template for panel {$this->name}")
        };
    }
}
