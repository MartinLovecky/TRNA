<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\{Color, TmContainer};
use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\PluginController;
use Yuha\Trna\Core\Enums\Panel;
use Yuha\Trna\Core\Window\WindowBuilder;
use Yuha\Trna\Infrastructure\Gbx\Client;
use Yuha\Trna\Repository\Challenge;

class Cpll implements DependentPlugin
{
    private PluginController $pluginController;
    private int $nbCheckpoints = 0;
    private bool $enabled = true;
    private bool $filter = true;
    private array $cpll = [];

    public function __construct(
        private Color $c,
        private Client $client,
        private Challenge $challenge,
        private WindowBuilder $windowBuilder,
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

    public function onPlayerFinish(string $login): void
    {
        $this->resetCP($login);
    }

    public function onNewChallenge(): void
    {
        $this->nbCheckpoints = $this->challenge->getCurrentChallengeInfo('NbCheckpoints');
        $this->resetCP();
    }

    public function onRestartChallenge(): void
    {
        $this->resetCP();
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
        if ($player->get('cmd.action') !== 'cpll') {
            return;
        }

        match ($player->get('cmd.mod')) {
            'on', 'off', 'filter' => $this->adminCMD($player),
            'mycp'                => $this->listCP($player, true),
            'cp'                  => $this->listCP($player, false),
            default               => $this->help($player)
        };
    }

    // ---------- Chat functions  ----------

    private function adminCMD(TmContainer $player): void
    {
        if (
            $player->get('isMasterAdmin') === true
            || $player->get('isAdmin') === true
        ) {
            $this->enabled = $player->get('cmd.mod') === 'on' ? true : false;
            $this->filter = $player->get('cmd.param') === 'on' ? true : false;
            $msg = <<<MSG
            {$this->c->green}CPLL set to {$player->get('cmd.mod')} {$player->get('cmd.param')}
            MSG;
            $this->client->sendChatMessageToLogin($msg, $player->get('Login'));
        } else {
            $msg = <<<MSG
                {$this->c->green}You don't have permission to do this action {$player->get('cmd.mod')}
            MSG;
            $this->client->sendChatMessageToLogin($msg, $player->get('Login'));
        }
    }

    private function listCP(TmContainer $player, bool $isMyCp): void
    {
        if (!$this->enabled) {
            $msg = <<<MSG
                {$this->c->green}CPLiveList is currently disabled!
            MSG;
            $this->client->sendChatMessageToLogin($msg, $player->get('Login'));
            return;
        }

        if ($this->filter) {
            foreach ($this->cpll as $key => $_) {
                if ($player->get('IsSpectator') === true) {
                    unset($this->cpll[$key]);
                }
            }
        }

        if ($isMyCp && !isset($this->cpll[$player->get('Login')])) {
            $msg = <<<MSG
                {$this->c->green}You did not reach a checkpoint yet!
            MSG;
            $this->client->sendChatMessageToLogin($msg, $player->get('Login'));
            return;
        }

        uasort($this->cpll, static fn ($a, $b) => $a['cp'] <=> $b['cp']);
        //TODO: check what this even does in original

    }

    private function help(TmContainer $player): void
    {
        $maniaLinks = $this->pluginController->getPlugin(ManiaLinks::class);
        $maniaLinks->displayToLogin(
            'tmxv/help',
            $player->get('Login'),
            $this->windowBuilder->data(Panel::Cpll, $player),
        );
    }

    // ---------- helper functions  ----------

    private function resetCP(?string $login = null): void
    {
        if (isset($login)) {
            unset($this->cpll[$login]);
        }
        $this->cpll = [];
    }
}
