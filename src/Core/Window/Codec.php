<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Window;

use Yuha\Trna\Core\DTO\WindowContext;
use Yuha\Trna\Core\Enums\{Action, Window};

final class Codec
{
    private const WINDOW_SHIFT  = 10000;
    private const ACTION_SHIFT  = 100;

    public function encode(Window $window, Action $action, int $value = 0): int
    {
        return ($window->value * self::WINDOW_SHIFT) +
            ($action->value * self::ACTION_SHIFT) +
            $value;
    }

    public function decode(int $id): WindowContext
    {
        $windowValue = intdiv($id, self::WINDOW_SHIFT);
        $actionValue = intdiv($id % self::WINDOW_SHIFT, self::ACTION_SHIFT);
        $value = $id % self::ACTION_SHIFT;
        $window = Window::tryFrom($windowValue)
            ?? throw new \InvalidArgumentException("Unknown window for value {$windowValue}");
        $action = Action::tryFrom($actionValue)
            ?? throw new \InvalidArgumentException("Unknown action for value {$actionValue}");
        return new WindowContext($window, $action, $value);
    }
}
