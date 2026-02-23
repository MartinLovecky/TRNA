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
    private Restart $restarting = Restart::NO;
    private Status $currStatus = Status::NONE;
    private Status $prevStatus = Status::NONE;
    private int $uptime = 0;
    private bool $warmUp = false;

    public function __construct(
        private Color $c,
        private Client $client,
        private Challenge $challenge,
        private Players $players,
        private PluginController $pluginController,
    ) {
        $this->initLog('AppController');
        $this->uptime = time();
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

        $msg = <<<MSG
            {$this->c->white}*** {$this->c->green}TRNA {$version} running on {$ip}:{$port}
        MSG;
        $this->client->sendChatMessageToAll($msg);
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
            'TrackMania.PlayerConnect'     => $this->onPlayerConnect($cb),
            'TrackMania.PlayerDisconnect'  => $this->pluginController->invokeAllMethods('onPlayerDisconnect', $cb->get('Login')),
            'TrackMania.PlayerChat'        => $this->onChat($cb),
            'TrackMania.PlayerCheckpoint'  => $this->pluginController->invokeAllMethods('onCheckpoint', $cb),
            'TrackMania.PlayerInfoChanged' => $this->playerInfoChanged($cb),
            'TrackMania.PlayerFinish'      => $this->onFinish($cb),
            'TrackMania.BeginRound'        => null, // do nothing
            'TrackMania.EndRound'          => null, // do nothing
            'TrackMania.StatusChanged'     => $this->gameStatusChanged($cb),
            'TrackMania.BeginChallenge'    => $this->newChallenge($cb),
            'TrackMania.EndChallenge'      => $this->endChallenge($cb),
            'TrackMania.PlayerManialinkPageAnswer' => $this->onAnswer($cb),
            'TrackMania.ChallengeListModified'     => $this->pluginController->invokeAllMethods('onListModified', $cb),
            'TrackMania.VoteUpdated'       => $this->pluginController->invokeAllMethods('onVoteUpdated', $cb),
            default                        => $this->logDebug("Unhadled cb {$cb->get('methodName')}", $cb->toArray()),
        };
    }

    private function onPlayerConnect(TmContainer $cb): void
    {
        if ($cb->get('Login') === '') {
            return;
        }

        if (TmContainer::fromJsonFile('Banned')->has($cb->get('Login'))) {
            $msg = <<<MSG
                {$this->c->green}Could not connect: \n
                Your IP was banned from this server!
            MSG;
            $this->client->sendChatMessageToLogin($msg, $cb->get('Login'));
            return;
        }

        $this->players->add($cb->get('Login'));
        $player = $this->players->getByLogin($cb->get('Login'));
        $msg = <<<MSG
            {$this->c->green}Welcome {$player->get('NickName')} {$this->c->z->green}to {$this->c->white}
        MSG . Server::$name;

        $this->client->sendChatMessageToLogin($msg, $player->get('Login'));
        $this->pluginController->invokeAllMethods('onPlayerConnect', $player);
    }

    private function onChat(TmContainer $cb): void
    {
        $player = $this->players->getByLogin($cb->get('Login'));

        if ($player) {
            $this->handleChatMessage($player, $cb->get('text'));
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

    private function playerInfoChanged(TmContainer $cb): void
    {
        $info = $cb->get('playerInfo');
        $player = $this->players->getByLogin($info->get('Login'));

        if (!$player instanceof TmContainer) {
            return;
        }

        if ($info->get('LadderRanking') > 0) {
            $player->setMultiple([
                'LadderRanking' => $info->get('LadderRanking'),
                'IsOfficial'    => true,
            ]);
        }

        $player->setMultiple([
            'IsOfficial'  => false,
            'IsSpectator' => false,
            'PrevStatus'  => $player->get('IsSpectator'),
            'SpectatorStatus' => $info->get('SpectatorStatus'),
        ]);
        // check spectator status (ignoring temporary changes)
        if ($info->get('SpectatorStatus') % 10 !== 0) {
            $player->set('IsSpectator', true);
        }

        $this->pluginController->invokeAllMethods('onPlayerInfoChanged', $player);
    }

    private function onFinish(TmContainer $cb): void
    {
        if ($this->currStatus !== Status::RUNNING_PLAY) {
            return;
        }

        $player = $this->players->getByLogin($cb->get('Login'));

        if (!$player instanceof TmContainer) {
            return;
        }

        $player->setMultiple([
            'record.time' => $cb->get('time'),
            'record.new'  => false,
            'record.date' => date("Y-m-d H:i:s"),
        ]);

        $this->pluginController->invokeAllMethods('onPlayerFinish', $player);
    }

    private function gameStatusChanged(TmContainer $cb): void
    {
        $cbStatus = Status::tryFrom($cb->get('statusCode'));
        $this->prevStatus = $this->currStatus;
        $this->currStatus = $cbStatus;

        if (
            $this->currStatus === Status::RUNNING_SYNC ||
            $this->currStatus === Status::FINISH
        ) {
            $this->warmUp = $this->client->query('GetWarmUp')->get('result');
        }
        $this->warmUp = false;
        // WHEN FINISH
        //TODO: Refresh Scoretable lists,
    }

    private function newChallenge(TmContainer $cb): void
    {
        $this->logDebug('newChallenge ' . date("Y-m-d H:i:s"));
        //
        if ($this->restarting !== Restart::NO) {
            if ($this->restarting === Restart::CHATTIME) {
                $this->restarting = Restart::NO;
            } else {
                $this->restarting = Restart::NO;
                //onRestartChallenge2
            }
        }
        $this->pluginController->invokeAllMethods('onNewChallenge', $cb->toArray());
        // onNewChallenge2 merged with onNewChallenge
        // show_trackrecs maybe
    }

    private function endChallenge(TmContainer $cb): void
    {
        $this->logDebug('endChallenge ' . date("Y-m-d H:i:s"), $cb->toArray());
    }

    private function onAnswer(TmContainer $cb): void
    {
        $player = $this->players->getByLogin($cb->get('Login'));

        if (!$player instanceof TmContainer) {
            return;
        }

        $player->set('encodedAction', $cb->get('answer'));
        $this->pluginController->invokeMethod(ManiaLinks::class, 'onAnswer', $player);
    }
}
