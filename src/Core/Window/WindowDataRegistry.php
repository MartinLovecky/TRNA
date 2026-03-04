<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Window;

use Yuha\Trna\Core\Enums\Window;

class WindowDataRegistry
{
    /** @var array<int, WindowDataProvider> */
    private array $providers = [];

    public function register(Window $window, string $provider): void
    {
        $x = new $provider();
        $this->providers[$window->value] = $x;
    }

    public function get(Window $window): WindowDataProvider
    {
        return $this->providers[$window->value]
            ?? throw new \RuntimeException("No provider for {$window->name}");
    }
}
