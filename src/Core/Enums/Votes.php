<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Enums;

enum Votes: string
{
    case SKIP      = 'skip';
    case OP_SKIP   = '/skip';
    case REPLAY    = 'replay';
    case OP_REPLAY = '/replay';
    case KICK      = 'kick';
    case OP_KICK   = '/kick';

    public function panel(): Window
    {
        return match ($this) {
            self::SKIP, self::OP_SKIP     => Window::Skip,
            self::REPLAY, self::OP_REPLAY => Window::Replay,
            self::KICK, self::OP_KICK     => Window::Kick,
        };
    }
}
