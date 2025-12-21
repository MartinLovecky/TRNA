<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Controllers;

use Yuha\Trna\Core\Contracts\{DependentPlugin, PluginInterface};
use Yuha\Trna\Plugins\Cpll;
use Yuha\Trna\Plugins\Dedimania;
use Yuha\Trna\Plugins\Karma;
use Yuha\Trna\Plugins\ManiaLinks;
use Yuha\Trna\Plugins\RaspVotes;
use Yuha\Trna\Plugins\Tmxv;

class PluginController
{
    /** @var array<class-string, PluginInterface> */
    private array $plugins = [];

    public function __construct(
        private Cpll $cpll,
        private Dedimania $dedimania,
        private Karma $karma,
        private ManiaLinks $maniaLinks,
        private RaspVotes $raspVotes,
        private Tmxv $tmxv
    ) {
        $this->plugins = [
            Dedimania::class  => $this->dedimania,
            ManiaLinks::class => $this->maniaLinks,
            RaspVotes::class  => $this->raspVotes,
            Tmxv::class       => $this->tmxv,
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
