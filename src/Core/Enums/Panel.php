<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Enums;

enum Panel: int
{
    case Close  = 0;
    case Help   = 50;
    case Track  = 11;
    case Skip   = 12;
    case Pass   = 13;
    case Cancel = 14;
    case Yes    = 15;
    case No     = 16;
    case Replay = 17;
    case Kick   = 18;
    case Admin  = 20;
    case Tmxv   = 30;
    case Cpll   = 40;

    public function template(): string
    {
        return match ($this) {
            self::Track => 'track',
            self::Skip, self::Replay, self::Kick  => 'skip',
            self::Admin => 'admin',
            self::Tmxv  => 'videos',
            self::Help  => 'commands',
            self::Cpll  => 'idk',
            default     => throw new \RuntimeException("No template for panel {$this->name}")
        };
    }

    public function choices(): array
    {
        return match ($this) {
            self::Skip, self::Replay, self::Kick => [
                'yes' => 1,
                'no' => 2,
                'cancel' => 3,
                'close' => 0,
                'pass'  => 5,
                'none'  => 6, //wait
            ],
            default => ['close' => 0]
        };
    }

    public function choiceName(int $number): ?string
    {
        $choices = $this->choices();
        $name = array_search($number, $choices, true);
        return $name !== false ? $name : null;
    }
}
