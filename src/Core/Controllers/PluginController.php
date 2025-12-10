<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Controllers;

use Yuha\Trna\Core\Contracts\{DependentPlugin, PluginInterface};
use Yuha\Trna\Plugins\ManiaLinks;
use Yuha\Trna\Plugins\RaspVotes;

class PluginController
{
    /** @var array<class-string, PluginInterface> */
    private array $plugins = [];

    public function __construct(
        private RaspVotes $raspVotes,
        private ManiaLinks $maniaLinks
    ) {
        $this->plugins = [
            RaspVotes::class => $this->raspVotes,
            ManiaLinks::class => $this->maniaLinks,
        ];

        foreach ($this->plugins as $plugin) {
            if ($plugin instanceof DependentPlugin) {
                $plugin->setRegistry($this);
            }
        }
    }

    /**
     * @template T of PluginInterface
     * @param  class-string<T> $pluginClass
     * @return T|null
     */
    public function getPlugin(string $pluginClass): ?PluginInterface
    {
        return $this->plugins[$pluginClass] ?? null;
    }

    /**
     * @template T of PluginInterface
     * @param class-string<T> $pluginClass
     */
    public function invokeMethod(string $pluginClass, string $methodName, ...$args): mixed
    {
        $plugin = $this->getPlugin($pluginClass);
        if ($plugin && method_exists($plugin, $methodName)) {
            return $plugin->$methodName(...$args);
        }
        return null;
    }

    public function invokeAllMethods(string $methodName, ...$args): void
    {
        foreach ($this->plugins as $plugin) {
            if (method_exists($plugin, $methodName)) {
                $plugin->$methodName(...$args);
            }
        }
    }
}
