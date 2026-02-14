<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Controllers;

use Yuha\Trna\Core\Contracts\{DependentPlugin, PluginInterface};
use Yuha\Trna\Plugins\{
    Checkpoints,
    Cpll,
    Dedimania,
    Karma,
    ManiaLinks,
    RaspJukebox,
    RaspVotes,
    Tmxv,
    Track
};

class PluginController
{
    /** @var array<class-string, PluginInterface> */
    private array $plugins = [];

    public function __construct(
        private Checkpoints $checkpoints,
        private Cpll $cpll,
        private Dedimania $dedimania,
        private Karma $karma,
        private ManiaLinks $maniaLinks,
        private RaspJukebox $raspJukebox,
        private RaspVotes $raspVotes,
        private Tmxv $tmxv,
        private Track $track,
    ) {
        // Plugins avaible when class implements DependentPlugin
        $this->plugins = [
            Checkpoints::class => $this->checkpoints,
            Cpll::class        => $this->cpll,
            Dedimania::class   => $this->dedimania,
            Karma::class       => $this->karma,
            ManiaLinks::class  => $this->maniaLinks,
            RaspJukebox::class => $this->raspJukebox,
            RaspVotes::class   => $this->raspVotes,
            Tmxv::class        => $this->tmxv,
            Track::class       => $this->track,
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

    /**
     *
     * @param [type] ...$args
     */
    public function invokeAllMethods(string $methodName, ...$args): void
    {
        foreach ($this->plugins as $plugin) {
            if (method_exists($plugin, $methodName)) {
                $plugin->$methodName(...$args);
            }
        }
    }
}
