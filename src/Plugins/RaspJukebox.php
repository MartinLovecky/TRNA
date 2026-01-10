<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\PluginController;
use Yuha\Trna\Core\Enums\Jukebox;
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Infrastructure\Gbx\Client;
use Yuha\Trna\Repository\Challenge;

class RaspJukebox implements DependentPlugin
{
    use LoggerAware;
    private PluginController $pluginController;
    private const string WIN = 'hud' . \DIRECTORY_SEPARATOR;

    public function __construct(
        private Client $client,
        private Challenge $challenge
    ) {
        $this->initLog('Plugin-Jukebox');
    }

    public function setRegistry(PluginController $pluginController): void
    {
        $this->pluginController = $pluginController;
    }

    // ---------- Event Handlers  ----------

    public function onSync(): void
    {
    }

    public function onNewChallenge(): void
    {
        $this->displayTest();
    }

    public function onChatCommand(TmContainer $player): void
    {
        if (!Jukebox::action($player->get('cmd.action'))) {
            return; // not jukebox action
        }

        $param = $player->get('cmd.mod');   // jb, best, worst, nofin, p [param]
        $arg   = $player->get('cmd.param');

        $tmx = $this->challenge->getTmx();
        $posibleResults = [$tmx->id, $tmx->author, $tmx->uid];

        if (!isset($arg)) {
            //TODO display list of all maps

            return;
        }
        // TODO: this should only happen on p

        //map doesnt exist on tmx or invalid input inside $arg
        if (!\in_array($arg, $posibleResults, true)) {
            $this->client->sendChatMessageToLogin("Invalid input {$arg} or not on tmx", $player->get('Login'));
        }
    }

    public function displayTest(): void
    {
        $maniaLinks = $this->pluginController->getPlugin(ManiaLinks::class);

        //TODO (yuha) design Jukebox window
        $maniaLinks->displayToAll(self::WIN . 'testBox', []);
    }
}
