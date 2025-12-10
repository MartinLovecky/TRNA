<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Revolt\EventLoop;
use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\PluginController;
use Yuha\Trna\Core\Controllers\VoteController;
use Yuha\Trna\Core\Enums\Panel;
use Yuha\Trna\Core\Enums\Votes;
use Yuha\Trna\Core\TmContainer;

class RaspVotes implements DependentPlugin
{
    private PluginController $pluginController;

    public function __construct(private VoteController $voteController)
    {
    }

    /**
     * Avoiding circular depedency
     */
    public function setRegistry(PluginController $pluginController): void
    {
        $this->pluginController = $pluginController;
    }

    public function onChatCommand(TmContainer $player): void
    {
        $votes = Votes::tryFrom($player->get('command.name'));

        if (!$votes) {
            return; // irelevant chat command for this class
        }

        $res = match ($votes) {
            Votes::SKIP => $this->voteController->startVote($player, Panel::Skip),
            Votes::OP_SKIP => $this->voteController->startVote($player, Panel::Skip, false),
            default => null,
        };

        if (isset($res['reason'])) {
            $this->handleReason($player, $res['reason']);
        }

        $this->startVoteCountdown($player, $votes->panel());
    }

    private function handleReason(TmContainer $player, string $reason): void
    {
        $choice = $this->voteController->status()['choice'] ?? 'none';

        match ($reason) {
            'vote_in_progress'    => $this->voteController->update($player, $choice),
            'admin_skip'          => null, //skip with no window
            'not_enough_players'  => null, //skip with no window
            default               => null
        };
    }

    private function startVoteCountdown(TmContainer $player, Panel $panel): void
    {
        $voteController = $this->voteController;
        $maniaLinks = $this->pluginController->getPlugin(ManiaLinks::class);

        EventLoop::repeat(1.0, static function (string $id) use ($player, $panel, $voteController, $maniaLinks) {
            $status = $voteController->status();
            $remaining = $voteController->tick();

            //REVIEW: ADD HERE TOTAL VOTES = PLAYER COUNT
            if ($remaining <= 0) {
                EventLoop::cancel($id);
                $voteController->resolveVote();
                $maniaLinks->closeDisplayToAll($panel->value);
                return;
            }

            $maniaLinks->displayToAll($panel->template(), [
                'actions'   => $status['actions'],
                'isAdmin'   => $player->get('isAdmin'),
                'remaining' => $remaining,
                'yes'       => $status['yes'],
                'no'        => $status['no'],
            ]);

            $remaining--;
        });
    }
}
