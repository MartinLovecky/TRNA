<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\{PluginController, VoteController};
use Yuha\Trna\Core\Enums\{ActionResult, Panel};
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Core\Window\WindowRegistry;
use Yuha\Trna\Infrastructure\Gbx\Client;

class ManiaLinks implements DependentPlugin
{
    use LoggerAware;
    private const int OUT = 0;
    private PluginController $pluginController;

    public function __construct(
        private Client $client,
        private VoteController $voteController
    ) {
        $this->initLog('Plugin-ManiaLinks');
    }

    public function setRegistry(PluginController $pluginController): void
    {
        $this->pluginController = $pluginController;
    }

    public function onAnswer(TmContainer $player)
    {
        try {
            [$panel, $currentAction] = WindowRegistry::decode($player->get('encodedAction'));
            $player->set("{$panel->name}.currentPage", $currentAction);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }

        $res = $this->handleAction($player, $panel);
    }

    public function displayToAll(string $winName, array $context, bool $hide = false): void
    {
        $this->client->sendRenderToAll($winName, $context, self::OUT, $hide);
    }

    public function displayToLogin(string $winName, string $login, array $context, bool $hide = false): void
    {
        $this->client->sendRenderToLogin($login, $winName, $context, self::OUT, $hide);
    }

    public function closeDisplayToAll(int $id = 1): void
    {
        $this->client->sendXmlToAll("<manialink id='{$id}'></manialink>");
    }

    public function closeDisplayToLogin(string $login, int $id = 1): void
    {
        $this->client->sendXmlToLogin($login, "<manialink id='{$id}'></manialink>");
    }

    private function handleAction(TmContainer $player, Panel $panel): void
    {
        $choice = $panel->choiceName($player->get("{$panel->name}.currentPage"));
        $result = match ($panel) {
            Panel::Skip, Panel::Replay => $this->action($player, $choice),
            default => ActionResult::NoAction,
        };

        match ($result) {
            ActionResult::Closed     => $this->closeDisplayToLogin($player->get('Login'), $panel->value),
            ActionResult::NotHandled => $this->logWarning("Manialink {$panel->name} action: {$choice} not handled: private function action(TmContainer \$player, string \$choice = 'none')"),
            default => null,
        };
    }

    private function action(TmContainer $player, string $choice = 'none'): ActionResult
    {
        if ($choice === 'yes' || $choice === 'no') {
            $this->voteController->update($player, $choice);
            return ActionResult::Handled;
        } elseif (($choice === 'cancel' || $choice === 'pass') && $player->get('isAdmin')) {
            return ActionResult::NotHandled; //TODO
        } elseif ($choice === 'close') {
            $this->voteController->update($player, $choice);
            return ActionResult::Closed;
        }

        return ActionResult::NoAction;
    }
}
