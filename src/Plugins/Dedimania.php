<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\PluginController;
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Service\DediClient;

class Dedimania implements DependentPlugin
{
    private PluginController $pluginController;

    public function __construct(private DediClient $dediClient)
    {
    }

    public function setRegistry(PluginController $pluginController): void
    {
        $this->pluginController = $pluginController;
    }

    //TODO: finish
    public function onPlayerConnect(TmContainer $player)
    {
        $params = [
            $this->dediClient->authenticate(),
            $this->dediClient->validateAccount(),
            $this->dediClient->playerArrive($player),
            $this->dediClient->warningsAndTTR(),
        ];

        $res = $this->dediClient->request('playerArrive', $params);

        if (!$res instanceof TmContainer) {
            // TODO: failed request debug http client in dediClient
            return;
        }

        // TODO: dig in old xaseco pain
    }

    public function onNewChallenge()
    {
        $res = $this->dediClient->request('currentChallenge', []);
    }
}
