<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Controllers;

use Revolt\EventLoop;
use Yuha\Trna\Core\{Color, Server, TmContainer};
use Yuha\Trna\Core\Enums\{Restart, Status};
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Infrastructure\Gbx\{Client, RemoteClient};
use Yuha\Trna\Plugins\ManiaLinks;
use Yuha\Trna\Repository\{Challenge, Players};
use Yuha\Trna\Service\Aseco;

class AppController
{
    use LoggerAware;
    private Restart $restarting = Restart::NONE;
    private Status $currStatus = Status::NONE;

    public function __construct(
        private Color $c,
        private Client $client,
        private Challenge $challenge,
        private Players $players,
        private PluginController $pluginController,
    ) {
        $this->initLog('AppController');
    }

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
            $this->client->query('GetServerOptions')->get('result'),
        );

        $this->pluginController->invokeAllMethods('onSync');
        $this->sendHeader();
        $this->newChallenge();
    }

    private function sendHeader(): void
    {
        $ip = Server::$ip;
        $port = Server::$port;
        $name = Server::$name;
        $game = Server::$game;
        $packmask = Server::$packMask;
        $version = Server::$version;
        $build = Server::$build;
        $gameMode = $this->challenge->gameMode();

        Aseco::consoleText('###############################################################################');
        Aseco::consoleText("  TRNA   : {$version} running on {$ip}:{$port}");
        Aseco::consoleText("  Name   : {$name}");
        Aseco::consoleText("  Game   : {$game} - {$packmask} - {$gameMode->name}");
        Aseco::consoleText("  Version: {$version} / {$build}");
        Aseco::consoleText('  Authors: Florian Schnell & Assembler Maniac');
        Aseco::consoleText('  Re-Authored: Xymph');
        Aseco::consoleText('  Remake: Yuhzel');
        Aseco::consoleText('###############################################################################');
    }

    private function startCallbackPump(): void
    {
        EventLoop::repeat(0.05, function () {
            $this->client->readCallBack();
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
        $this->players->add($cb->get('Login'));
        $player = $this->players->getByLogin($cb->get('Login'));
        $msg = <<<MSG
            {$this->c->green}Welcome {$player->get('NickName')} {$this->c->z->green}to {$this->c->white}
        MSG . Server::$name;

        $this->client->sendChatMessageToLogin($msg, $player->get('Login'));
        $this->pluginController->invokeAllMethods('onPlayerConnect', $player);
    }

    private function onPlayerDisconnect(TmContainer $cb)
    {
    }

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
        $message   = trim($message);
        $action    = '';
        $modifier  = '';
        $parameter = '';

        // +, ++, +++, -, --, ---
        if (preg_match('/^([+-]+)(?:\s+(.*))?$/', $message, $m)) {
            $signs = $m[1];
            $count = \strlen($signs);
            $type  = $signs[0] === '+' ? 'plus' : 'minus';
            // plus1, plus2 ...
            $action = $type . $count;
        } elseif (str_starts_with($message, '/')) {
            $parts = preg_split('/\s+/', substr($message, 1), 3);
            $action  = strtolower($parts[0] ?? '');
            $modifier = strtolower($parts[1] ?? '');
            $parameter   = strtolower($parts[2] ?? '');
        } else {
            return; // Not a command
        }

        $player->setMultiple([
            'cmd.action'  => $action,
            'cmd.mod'     => $modifier,
            'cmd.param'   => $parameter,
        ]);

        $this->pluginController->invokeAllMethods('onChatCommand', $player);
    }

    private function onPlayerCp(TmContainer $cb): void
    {
        $this->pluginController->invokeAllMethods('onCheckpoint', $cb);
    }

    private function onFinish(TmContainer $cb): void
    {
        $date = date('Y/m/d;H:i:s');
    }

    private function onBeginRound(): void
    {
        $this->logDebug('onBeginRound' . date("Y-m-d H:i:s"));
        $this->pluginController->invokeAllMethods('onBeginRound');
    }

    private function onEndRound(): void
    {
        $this->pluginController->invokeAllMethods('onEndRound');
    }

    private function gameStatusChanged(TmContainer $cb): void
    {
        $this->logDebug('gameStatusChanged' . date("Y-m-d H:i:s"), $cb->toArray());
    }

    private function newChallenge(): void
    {
        $this->logDebug('onNewChallenge' . date("Y-m-d H:i:s"));
        $this->pluginController->invokeAllMethods('onNewChallenge');
    }

    private function endChallenge()
    {
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
