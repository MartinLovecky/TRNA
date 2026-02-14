<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\PluginController;
use Yuha\Trna\Core\Enums\{Action, Jukebox, Window};
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Core\Window\{Builder, Codec};
use Yuha\Trna\Infrastructure\Gbx\Client;
use Yuha\Trna\Repository\Challenge;

class RaspJukebox implements DependentPlugin
{
    use LoggerAware;
    private PluginController $pluginController;
    public int $jukeListID;

    public function __construct(
        private readonly Builder $builder,
        private readonly Codec $codec,
        private readonly Client $client,
        private readonly Challenge $challenge
    ) {
        $this->initLog('Plugin-Jukebox');
        $this->jukeListID = $this->codec->encode(Window::JUKE_LIST, Action::Open);
    }

    public function setRegistry(PluginController $pluginController): void
    {
        $this->pluginController = $pluginController;
    }

    // ---------- Event Handlers  ----------

    public function onPlayerConnect(TmContainer $player): void
    {
        $this->builder->display(
            window: Window::JUKE_BOX,
            login: $player->get('Login'),
            data: ['list_id' => $this->jukeListID],
        );
    }

    public function onNewChallenge(): void
    {
        // TODO : DISPLAY BOX WITH CNT OF all maps and display window on click
        //$this->displayTest();
    }

    public function onChatCommand(TmContainer $player): void
    {
        if (!Jukebox::action($player->get('cmd.action'))) {
            return; // not jukebox action
        }

        $mod = $player->get('cmd.mod');   // jb, best, worst, nofin, p [param]
        $arg   = $player->get('cmd.param');
        $this->logDebug('cmd', [$mod, $arg, $player->get('Login')]);
        $this->displayTest($player->get('Login'));
        return;
        if (!isset($mod)) {
            $this->displayTest();
            return;
        }
        $tmx = $this->challenge->getTmx();
        $posibleResults = [$tmx->id, $tmx->author, $tmx->uid];

        if (!isset($arg)) {
            //TODO display list of all maps

            return;
        }
        // TODO: this should only happen on /list $p int|string
    }

    public function displayTest(string $login): void
    {
        //$this->builder->display();
        //TODO (yuha) design Jukebox window
        $win = 'hud/small';
        // $maniaLinks->displayToLogin(
        //     $win,
        //     $login,
        //     ['box_id' => 4444, 'list_id' => 4445]
        // );
    }
}
