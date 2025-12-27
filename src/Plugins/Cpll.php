<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\{Color, TmContainer};
use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\PluginController;
use Yuha\Trna\Infrastructure\Gbx\Client;
use Yuha\Trna\Repository\Challenge;

class Cpll implements DependentPlugin
{
    private PluginController $pluginController;
    private int $nbCheckpoints = 0;
    private array $cpll = [];

    public function __construct(
        private Color $c,
        private Client $client,
        private Challenge $challenge,
    ) {
    }

    public function setRegistry(PluginController $pluginController): void
    {
        $this->pluginController = $pluginController;
    }

    // ---------- Event Handlers  ----------
    public function onPlayerConnect(TmContainer $player): void
    {
        $msg = <<<MSG
            {$this->c->white}** {$this->c->z->green} This server is running CPLL, use /cp and /mycp to view current standings
        MSG;
        $this->client->sendChatMessageToLogin($msg, $player->get('Login'));
        $this->cpll[$player->get('Login')] = [
            'time' => 0,
            'cp'   => 0,
        ];
    }

    public function onPlayerDisconnect(string $login): void
    {
        if (isset($this->cpll[$login])) {
            unset($this->cpll[$login]);
        }
    }

    public function onPlayerFinish(TmContainer $player): void
    {
    }

    public function onNewChallenge(): void
    {
        $chall = $this->challenge->getCurrentChallengeInfo();
        $this->nbCheckpoints = $chall->get('NbCheckpoints');
        $this->cpll = [];
    }

    public function onRestartChallenge(): void
    {
        $this->cpll = [];
    }

    public function onCheckpoint(TmContainer $cb): void
    {
        if (isset($this->cpll[$cb->get('Login')])) {
            $this->cpll[$cb->get('Login')] = [
                'time' => $cb->get('time'),
                'cp'   => $cb->get('checkpointIndex'),
            ];
        }
    }

    public function onChatCommand(TmContainer $player): void
    {
    }

    // ---------- Chat functions  ----------

}
