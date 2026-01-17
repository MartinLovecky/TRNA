<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Window;

use Yuha\Trna\Core\Enums\{ActionKind, Panel};

final class ActionCodec
{
    private const PANEL_SHIFT = 10000;
    private const KIND_SHIFT  = 100;
    private const CLOSE       = 0;

    public static function encodePage(Panel $panel, int $page): int
    {
        return
            ($panel->value * self::PANEL_SHIFT) +
            (ActionKind::Page->value * self::KIND_SHIFT) +
            $page;
    }

    public static function encodeChoice(Panel $panel, int $choice): int
    {
        return
            ($panel->value * self::PANEL_SHIFT) +
            (ActionKind::Choice->value * self::KIND_SHIFT) +
            $choice;
    }

    public static function encodeClose(Panel $panel): int
    {
        return
            ($panel->value * self::PANEL_SHIFT) +
            (ActionKind::Close->value * self::KIND_SHIFT) +
            self::CLOSE;
    }

    public static function decode(int $actionId): ActionContext
    {
        $panelValue = intdiv($actionId, self::PANEL_SHIFT);
        $kindValue  = intdiv($actionId % self::PANEL_SHIFT, self::KIND_SHIFT);
        $value      = $actionId % self::KIND_SHIFT;

        $panel = Panel::tryFrom($panelValue)
            ?? throw new \InvalidArgumentException("Unknown panel {$panelValue}");

        $kind = ActionKind::tryFrom($kindValue)
            ?? throw new \InvalidArgumentException("Unknown action kind {$kindValue}");

        return new ActionContext($panel, $kind, $value);
    }
}
