<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Window;

use Yuha\Trna\Core\Enums\Window;

interface WindowDataProvider
{
    public function getData(?string $login = null, ?Window $window = null, ?array $context = null): array;
}
