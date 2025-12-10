<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Controllers;

use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Plugins\ManiaLinks;
use Yuha\Trna\Repository\Challange;
use Yuha\Trna\Repository\Players;

class AppController
{
    public function __construct(
        private Challange $challange,
        private Players $players,
        private PluginController $pluginController,
    ) {
    }

    public function run()
    {
        dd($this->challange);
    }

    private function onAnswer(TmContainer $cb): void
    {
        $player = $this->players->getByLogin($cb->get('Login'));

        if (!$player instanceof TmContainer) {
            return;
        }
        $player->set('encodedAction', $cb->get('maniaLinkId'));
        $this->pluginController->invokeMethod(ManiaLinks::class, 'onAnswer', $player);
    }
}
