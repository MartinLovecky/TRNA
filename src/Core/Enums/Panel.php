<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Enums;

enum Panel: int
{
    case Help   = 50;
    case Track  = 11;
    case Skip   = 12;
    case Replay = 17;
    case Kick   = 18;
    case Admin  = 20;
    case Tmxv   = 30;
    case Cpll   = 40;

    public function choices(): array
    {
        return match ($this) {
            default => [
                'yes' => 1,
                'no' => 2,
                'cancel' => 3,
                'pass'  => 4,
            ]
        };
    }

    public function choiceName(int $value): string
    {
        return array_search($value, $this->choices(), true) ?: 'close';
    }
}
