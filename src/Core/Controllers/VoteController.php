<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Controllers;

use Yuha\Trna\Core\{Color, TmContainer};
use Yuha\Trna\Core\Enums\{GameMode, Panel};
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Core\Window\WindowRegistry;
use Yuha\Trna\Infrastructure\Gbx\Client;
use Yuha\Trna\Repository\{Challange, Players};

class VoteController
{
    use LoggerAware;
    private const int DURATION = 35;
    private const int MIN_PLAYERS = 2;
    private const float REQUIRED_PERCENT = 0.6;
    private const bool ALLOW_CANCEL = true;

    private ?array $vote = null;

    public function __construct(
        private Color $color,
        private Client $client,
        private Challange $challange,
        private Players $players
    ) {
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

        if ($this->players->numPlayers < self::MIN_PLAYERS) {
            return ['reason' => 'not_enought_players'];
        }

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
            'total'           => 0,
            'votes'           => [],
            'choice'          => 'none',
            'header'          => "{$panel->name} vote",
            'param'           => $player->get('command.param'),
            'arg'             => $player->get('command.arg'),
        ];

        $rem = (int) ceil(max(0, self::DURATION - (microtime(true) - $this->vote['startedAt'])));
        $this->vote['remaining'] = $rem;

        return ['active' => true];
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

        $player->set("{$status['type']}.vote", $choice);

        return ['ok' => true];
    }

    public function resolveVote(): void
    {
        $status = $this->status();
        $playerCount = $this->players->playerCount();

        // REVIEW for one player hnadled on startvote so this should not happen
        // but if player disconects I am not sure
        if ($playerCount < self::MIN_PLAYERS) {
            //FAILED no enough players cancel vote send message
            $this->vote = null;
            return;
        }

        $yes = $status['yes'];
        $yesPercent = $playerCount > 0 ? ($yes / $playerCount) : 0;

        if ($yesPercent <= self::REQUIRED_PERCENT) {
            //FAILED not enough yes votes cancel vote send message
            $this->vote = null;
            return;
        }

        match ($status['type']) {
            'Skip'   => $this->skip($status['initiator']),
            'Replay' => null,
            'Kick'   => null,
            default  => null,
        };

        $this->vote = null;
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
            'remaining' => $this->vote['remaining'],
            'param'     => $this->vote['param'],
            'arg'       => $this->vote['arg'],
            'playerCnt' => $this->players->playerCount(),
        ];
    }

    public function tick(): int
    {
        if (!isset($this->vote)) {
            return 0;
        }

        $this->vote['remaining']--;

        return $this->vote['remaining'];
    }

    private function countVotes(): array
    {
        $yes = $no = 0;

        if (!isset($this->vote)) {
            return ['yes' => $yes, 'no' => $no, 'total' => 0];
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
        ];
    }

    public function skip(string $initiator): void
    {
        $gameMode = $this->challange->gameMode();
        $msg = <<<MSG
            {$this->color->green}Player {$initiator}{$this->color->z->green} skips challenge!
        MSG;
        if ($gameMode === GameMode::Cup) {
            $this->client->query('NextChallenge', [true]);
            $this->client->sendChatMessageToAll('');
        }
        $this->client->query('NextChallenge');
        $this->client->sendChatMessageToAll($msg);
    }
}
