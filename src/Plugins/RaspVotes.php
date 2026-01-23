<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Revolt\EventLoop;
use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\{PluginController, VoteController};
use Yuha\Trna\Core\Enums\{Panel, Votes};
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Core\Window\Window;

class RaspVotes implements DependentPlugin
{
    use LoggerAware;
    private PluginController $pluginController;

    public function __construct(
        private VoteController $voteController,
        private Window $window
    ) {
        $this->initLog('RaspVotes');
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
        $votes = Votes::tryFrom($player->get('cmd.action'));

        if (!$votes) {
            return; // irelevant chat command for this class
        }

        $res = match ($votes) {
            Votes::SKIP    => $this->voteController->startVote($player, Panel::Skip),
            Votes::OP_SKIP => $this->voteController->startVote($player, Panel::Skip, false),
            Votes::KICK    => $this->voteController->startVote($player, Panel::Kick),
            Votes::OP_KICK => $this->voteController->startVote($player, Panel::Kick, false),
            default => null,
        };

        if (isset($res['reason'])) {
            $this->handleReason($player, $res['reason']);
            return;
        }

        $this->startVoteCountdown($player, $votes->panel());
    }

    private function handleReason(TmContainer $player, string $reason): void
    {
        $choice = $this->voteController->status()['choice'] ?? 'none';

        match ($reason) {
            'vote_in_progress' => $this->voteController->update($player, $choice),
            'admin_skip', 'not_enough_players' => $this->voteController->skip($player->get('Login')),
            default => null
        };
    }

    private function startVoteCountdown(TmContainer $player, Panel $panel): void
    {
        $maniaLinks = $this->pluginController->getPlugin(ManiaLinks::class);

        EventLoop::repeat(1.0, function (string $id) use ($player, $panel, $maniaLinks) {
            $status = $this->voteController->status();
            $remaining = $this->voteController->tick();

            if ($remaining <= 0 || $status['total'] === $status['playerCnt']) {
                EventLoop::cancel($id);
                $this->voteController->resolveVote(); //TODO
                $maniaLinks->closeDisplayToAll($panel->value);
                return;
            }
            // when closed do not display again
            if ($status['choice'] !== 'close') {
                $maniaLinks->displayToAll(
                    $panel->template(),
                    $this->window->build($panel, $player),
                );
            }

            $remaining--;
        });
    }
}
