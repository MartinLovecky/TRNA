<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Controllers;

use Revolt\EventLoop;
use Yuha\Trna\Core\Color;
use Yuha\Trna\Core\Server;
use Yuha\Trna\Service\Aseco;
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Enums\Status;
use Yuha\Trna\Plugins\ManiaLinks;
use Yuha\Trna\Repository\Players;
use Yuha\Trna\Repository\Challange;
use Yuha\Trna\Infrastructure\Gbx\Client;
use Yuha\Trna\Infrastructure\Gbx\RemoteClient;

class AppController
{
    private Status $currStatus = Status::NONE;

    public function __construct(
        private Color $color,
        private Client $client,
        private Challange $challange,
        private Players $players,
        private PluginController $pluginController,
    ) {}

    public function run(): void
    {
        $this->startCallbackPump();
        $this->boot();
    }

    private function boot(): void
    {
        $this->client->query('EnableCallbacks', [true]);
        $this->waitForRunningPlay();
        RemoteClient::init($this->client, $_ENV['admin_login']);
    }

    private function waitForRunningPlay(): void
    {
        $attempts = 0;
        $maxAttempts = 30;
        $client = $this->client;

        EventLoop::repeat(1.0, function (string $id) use (&$attempts, $maxAttempts, $client) {
            $attempts++;

            if ($attempts > $maxAttempts) {
                EventLoop::cancel($id);
                throw new \RuntimeException("Server never reached RUNNING_PLAY");
            }

            $status = $client->query('GetStatus')->get('result');
            $this->currStatus = Status::tryFrom($status->get('Code')) ?? Status::NONE;

            if ($this->currStatus === Status::RUNNING_PLAY) {
                EventLoop::cancel($id);
                EventLoop::queue(function () {
                    $this->syncServer();
                    $this->sendHeader();
                });
                return;
            }
        });
    }

    private function syncServer(): void
    {
        $sysInfo = $this->client->query('GetSystemInfo')->get('result.ServerLogin');

        Server::setServerInfo(
            $this->client->query('GetDetailedPlayerInfo', [$sysInfo])->get('result'),
            $this->client->query('GetVersion')->get('result'),
            $this->client->query('GetLadderServerLimits')->get('result'),
            $this->client->query('GetServerPackMask')->get('result'),
            $this->client->query('GetServerOptions')->get('result')
        );
    }

    private function sendHeader(): void
    {
        $ip = Server::$ip;
        $port = Server::$port;
        $name = Server::$name;
        $serverLogin = Server::$login;
        $game = Server::$game;
        $packmask = Server::$packMask;
        $version = Server::$version;
        $build = Server::$build;
        $gameMode = $this->challange->gameMode();
        $c = $this->color;

        Aseco::consoleText('###############################################################################');
        Aseco::consoleText("  TRNA   : {$version} running on {$ip}:{$port}");
        Aseco::consoleText("  Name   : {$name} - {$serverLogin}");
        Aseco::consoleText("  Game   : {$game} - {$packmask} - {$gameMode->name}");
        Aseco::consoleText("  Version: {$version} / {$build}");
        Aseco::consoleText('  Authors: Florian Schnell & Assembler Maniac');
        Aseco::consoleText('  Re-Authored: Xymph');
        Aseco::consoleText('  Remake: Yuhzel');
        Aseco::consoleText('###############################################################################');

        $startup = "{$c->yellow}*** TmController running on {$c->white}{$ip}:{$port}{$c->yellow} ***";
        $this->client->sendChatMessageToAll($startup);
    }

    private function startCallbackPump(): void
    {
        EventLoop::repeat(0.05, function () {
            while ($cb = $this->client->popCBResponse()) {
                $this->dispatchCallback($cb);
            }
        });
    }

    private function dispatchCallback(TmContainer $cb): void
    {
        match ($cb->get('methodName')) {
            'TrackMania.PlayerConnect'    => $this->onPlayerConnect($cb),
            'TrackMania.PlayerDisconnect' => $this->onPlayerDisconnect($cb),
            'TrackMania.PlayerChat'       => $this->onChat($cb),
            'TrackMania.PlayerCheckpoint' => $this->onPlayerCp($cb),
            'TrackMania.PlayerFinish'     => $this->onFinish($cb),
            'TrackMania.BeginRound'       => $this->onBeginRound(),
            'TrackMania.EndRound'         => $this->onEndRound(),
            'TrackMania.StatusChanged'    => $this->gameStatusChanged($cb),
            'TrackMania.BeginChallenge'   => $this->newChallenge(),
            'TrackMania.EndChallenge'     => $this->endChallenge(),
            'TrackMania.PlayerManialinkPageAnswer' => $this->onAnswer($cb),
            default                       => null,
        };
    }

    private function onPlayerConnect(TmContainer $cb): void
    {
        $c = $this->color;
        $this->players->add($cb->get('Login'));
        $player = $this->players->getByLogin($cb->get('Login'));
        $msg = <<<MSG
            {$c->green}Welcome {$player->get('NickName')}{$c->green}to {$c->white}
        MSG . Server::$name;

        $this->client->sendChatMessageToLogin($msg, $player->get('Login'));
    }

    private function onPlayerDisconnect(TmContainer $cb) {}

    private function onChat(TmContainer $cb): void
    {
        $login = $cb->get('Login');
        $player = $this->players->getByLogin($login);

        if ($player) {
            $this->handleChatMessage($player, $cb->get('message'));
            return;
        }
    }

    private function handleChatMessage(TmContainer $player, string $message): void
    {
        if (str_starts_with($message, '/')) {
            $cmd = preg_split('/\s+/', substr($message, 1), 3);
            $cmdName = str_replace(['+', '-'], ['plus', 'minus'], $cmd[0]);
            $cmdParam = $cmd[1] ?? '';
            $cmdArg = $cmd[2] ?? '';
            $player->setMultiple([
                'command.name' => $cmdName,
                'command.param' => $cmdParam,
                'command.arg' => $cmdArg,
            ]);
            $this->pluginController->invokeAllMethods('onChatCommand', $player);
        }
    }

    private function onPlayerCp(TmContainer $cb) {}

    private function onFinish(TmContainer $cb) {}

    private function onBeginRound(): void
    {
        $this->pluginController->invokeAllMethods('onBeginRound');
    }

    private function onEndRound(): void
    {
        $this->pluginController->invokeAllMethods('onEndRound');
    }

    private function gameStatusChanged(TmContainer $cb){}
    private function newChallenge() {}
    private function endChallenge() {}

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
