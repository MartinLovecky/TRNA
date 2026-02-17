<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\{Color, TmContainer};
use Yuha\Trna\Core\Controllers\RepoController;
use Yuha\Trna\Core\DTO\PlayerCheckpoint;
use Yuha\Trna\Core\Enums\{GameMode, Table, Window};
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Core\Window\Builder;
use Yuha\Trna\Infrastructure\Gbx\Client;
use Yuha\Trna\Repository\Challenge;
use Yuha\Trna\Service\Aseco;

class Checkpoints
{
    use LoggerAware;
    /** @var array<string, PlayerCheckpoint> */
    private array $cps = [];
    private bool $test = false;

    public function __construct(
        private readonly Builder $builder,
        private readonly Client $client,
        private readonly Color $c,
        private readonly Challenge $challenge,
        private readonly RepoController $repo,
    ) {
        $this->initLog('Plugin-Checkpoints');
    }

    // ---------- Event Handlers  ----------
    public function onPlayerConnect(TmContainer $player): void
    {
        $login = $player->get('Login');
        $gameMode = $this->challenge->gameMode();

        $this->cps[$login] = new PlayerCheckpoint(
            bestFin: $gameMode === GameMode::Laps ? 0 : PHP_INT_MAX,
            currFin: $gameMode === GameMode::Laps ? 0 : PHP_INT_MAX,
            bestCps: [$player->get('extra.cps')],
            currCps: [],
            dedirec: $player->get('extra.dedicps', 0),
        );
    }

    public function onPlayerDisconnect(string $login): void
    {
        $data = [
            'cps' => $this->cps[$login]->bestFin,
            'dedicps' => $this->cps[$login]->dedirec,
        ];
        $this->repo->update(Table::PLAYERS_EXTRA, $data, $login);
        unset($this->cps[$login]);
    }

    public function onNewChallenge(): void
    {
        //$uid = $this->challenge->getCurrentChallengeInfo('UId');
        $gameMode = $this->challenge->gameMode();
        // clear all checkpoints
        foreach ($this->cps as $login => $_) {
            $this->cps[$login]->bestFin = PHP_INT_MAX;
            $this->cps[$login]->currFin = PHP_INT_MAX;
            if ($gameMode === GameMode::Laps) {
                $this->cps[$login]->currFin = 0;
            }
            $this->cps[$login]->bestCps = [];
            $this->cps[$login]->currCps = [];

            $lrec = $this->cps[$login]->loclrec - 1;

            if ($lrec + 1 > 0) {
                //
            } elseif ($lrec + 1 === 0) {
            }
        }
    }

    public function onCheckpoint(TmContainer $cb): void
    {
        $gameMode = $this->challenge->gameMode();

        if ($gameMode === GameMode::Stunts) {
            return;
        }

        $login = $cb->get('Login');

        if (!isset($this->cps[$login])) {
            return;
        }

        $time  = $cb->get('time');
        $cpIndex = $cb->get('checkpointIndex');

        // NOTE: LAPS NOT SUPPORTED

        if ($gameMode === GameMode::Race && $cpIndex === 0) {
            $this->cps[$login]->currCps = [];
        }
        // check cheater
        if (
            $time <= 0 ||
            $cpIndex !== \count($this->cps[$login]->currCps) ||
            $cpIndex > 0 &&
            $time < end($this->cps[$login]->currCps) &&
            $this->test
        ) {
            $this->processCheater($login);
        }
        // store current checkpoint
        $this->cps[$login]->currCps[$cpIndex] = $time;
        // check if displaying for this player, and for best checkpoints
        if (
            $this->cps[$login]->loclrec !== -1 &&
            isset($checkpoints[$login]->best_cps[$cpIndex])
        ) {
            $check = $cpIndex + 1;
            $dif   = $this->cps[$login]->currCps[$cpIndex] - $this->cps[$login]->bestCps[$cpIndex];

            if ($dif < 0) {
                $dif  = abs($dif);
                $sign = "{$this->c->blue}-";
            } elseif ($dif === 0) {
                $sign = "{$this->c->blue}";
            } else {
                $sign = "{$this->c->red}+";
            }

            $sec = floor($dif / 1000);
            $hun = ($dif - ($sec * 1000)) / 10;

            if ($check === \count($this->cps[$login]->bestCps)) {
                $check = 'F';
            }

            // display panel
            $this->builder->display(
                Window::Checkpoints,
                $login,
                [
                    'id'   => Window::Checkpoints->value,
                    'cp'   => $check,
                    'diff' => $sign . \sprintf('%d.%02d', $sec, $hun),
                ],
            );
        }
    }

    public function onPlayerFinish(TmContainer $player)
    {
        $gameMode = $this->challenge->gameMode();

        if ($gameMode !== GameMode::Race) {
            return;
        }
    }

    public function onPlayerInfoChanged(TmContainer $player): void
    {
        if (
            $this->challenge->gameMode() === GameMode::Stunts
            || $player->get('prevstatus') === $player->get('IsSpectator')
        ) {
            return;
        }

        //display_cpspanel($login, 0, '$00f -.--');
    }

    private function processCheater(string $login): void
    {
        TmContainer::updateJsonFile('Banned', "Logins.{$login}", true);
        Aseco::console("Cheater {$login} banned");
        $msg = <<<MSG
            {$this->c->white}*** {$this->c->green}Cheater {$login}{$this->c->z->green} banned!
        MSG;
        $this->client->sendChatMessageToAll($msg);
        $this->client->query('Ban', [$login]);
    }
}
