<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Controllers;

use Yuha\Trna\Core\Enums\Panel;
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Core\Window\WindowRegistry;
use Yuha\Trna\Repository\Players;

class VoteController
{
    use LoggerAware;
    private const int DURATION = 35;
    private const int MIN_PLAYERS = 2;
    private const bool ALLOW_CANCEL = true;

    private ?array $vote = null;

    public function __construct(private Players $players)
    {
        $this->initLog('VoteController');
    }

    public function startVote(
        TmContainer $player,
        Panel $panel,
        bool $display = true
    ): array {
        if (isset($this->vote)) {
            return ['reason' => 'vote_in_progress'];
        }

        if (!$display) {
            return ['reason' => 'admin_skip'];
        }

        // if ($this->players->numPlayers < self::MIN_PLAYERS) {
        //     return ['reason' => 'CFV'];
        // }

        $actions = [];
        foreach ($panel->choices() as $key => $value) {
            $actions[$key] = WindowRegistry::encode($panel, $value);
        }

        $player->set("{$panel->name}.vote", 'none');
        $startedAt = microtime(true);

        $this->vote = [
            'type'            => $panel->name,
            'templateActions' => $actions,
            'initiator'       => $player->get('Login'),
            'startedAt'       => $startedAt,
            'requiredP'       => 0.6,
            'minPlayers'      => self::MIN_PLAYERS,
            'playerCnt'       => $this->players->numPlayers,
            'votes'           => [],
            'choice'          => 'none',
            'header'          => "{$panel->name} vote",
            'param'           => $player->get('command.param'),
            'arg'             => $player->get('command.arg'),
        ];

        $rem = (int) ceil(max(0, self::DURATION - (microtime(true) - $this->vote['startedAt'])));
        $this->vote['remaining'] = $rem;

        return ['actions' => $actions, 'remaining' => $rem];
    }

    public function update(TmContainer $player, string $choice): array
    {
        if (!isset($this->vote)) {
            return ['reason' => 'no_active_vote'];
        }

        $this->vote['choice'] = $choice;
        $this->vote['votes'][$player->get('Login')] = $choice;

        $status = $this->status();

        if ($status['total'] === 0) {
            return ['reason' => 'no_active_vote'];
        }

        $this->logDebug("status", $this->vote);

        $player->set("{$status['type']}.vote", $choice);

        return ['ok' => true];
    }

    public function resolveVote(): void
    {
        $this->logDebug("status", $this->status());
    }

    public function status(): array
    {
        if (!isset($this->vote)) {
            return ['reason' => 'no_active_vote'];
        }

        $cnt = $this->countVotes();

        return [
            'type'      => $this->vote['type'],
            'actions'   => $this->vote['templateActions'],
            'panel'     => $this->vote['type'],
            'initiator' => $this->vote['initiator'],
            'header'    => $this->vote['header'],
            'choice'    => $this->vote['choice'],
            'yes'       => $cnt['yes'],
            'no'        => $cnt['no'],
            'total'     => $cnt['total'],
            'votes'     => array_keys($this->vote['votes']),
            'playerCnt' => $this->players->numPlayers,
            'remaining' => $this->vote['remaining'],
            'param'     => $this->vote['param'],
            'arg'       => $this->vote['arg'],
        ];
    }

    private function countVotes(): array
    {
        $yes = $no = 0;

        if (!isset($this->vote)) {
            return ['yes' => $yes, 'no' => $no, 'total' => 0, 'playerCnt' => $this->players->numPlayers];
        }

        foreach ($this->vote['votes'] as $_ => $choice) {
            switch ($choice) {
                case 'yes':
                    $yes++;
                    break;
                case 'no':
                    $no++;
                    break;
            }
        }

        return [
            'yes' => $yes,
            'no' => $no,
            'total' => $yes + $no,
            'playerCnt' => $this->vote['playerCnt'],
        ];
    }
}
