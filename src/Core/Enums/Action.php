<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Enums;

enum Action: int
{
    case Close  = 0;  // ← Close action
    case Page   = 1;  // ← direct page jump
    case Chat   = 2;  // ← (not implemented)

    case Next   = 3;  // ← navigation
    case Prev   = 4;  // ← navigation
    case First  = 5;  // ← navigation
    case Last   = 6;  // ← navigation

    case Yes    = 7;  // ← choice
    case No     = 8;  // ← choice
    case Cancel = 9;  // ← choice
    case Pass   = 10; // ← choice

    case Open = 11;   // ← Open diffrent Window

    public static function choiceCases(): array
    {
        return [
            self::Yes,
            self::No,
            self::Cancel,
            self::Pass,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Yes => 'yes',
            self::No => 'no',
            self::Cancel => 'cancel',
            self::Pass => 'pass',
            default => throw new \LogicException("Not a choice action: {$this->name}"),
        };
    }
}
