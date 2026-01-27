<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Window;

use Yuha\Trna\Core\Enums\{Action, Window};

final class Context
{
    public function __construct(
        public readonly Window $window,
        public readonly Action $action,
        public readonly int $value = 0
    ) {
    }
}
