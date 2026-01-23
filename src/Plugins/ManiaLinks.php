<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\{PluginController, VoteController};
use Yuha\Trna\Core\Enums\{ActionKind, Panel};
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Core\Window\{ActionCodec, ActionContext, Window};
use Yuha\Trna\Infrastructure\Gbx\Client;

class ManiaLinks implements DependentPlugin
{
    use LoggerAware;
    private const int OUT = 0;
    private PluginController $pluginController;

    public function __construct(
        private Client $client,
        private VoteController $voteController,
        private Window $window
    ) {
        $this->initLog('Plugin-ManiaLinks');
    }

    public function setRegistry(PluginController $pluginController): void
    {
        $this->pluginController = $pluginController;
    }

    public function onAnswer(TmContainer $player): void
    {
        $context = ActionCodec::decode($player->get('encodedAction'));

        match ($context->kind) {
            ActionKind::Page   => $this->handlePageAction($player, $context),
            ActionKind::Choice => $this->handleChoiceAction($player, $context),
            ActionKind::Close  => $this->handleCloseAction($player, $context),
            ActionKind::Chat   => $this->logDebug('Chat actions not supported', $context->toArray()),
            default => $this->logDebug("Unexpected context:", $context->toArray())
        };
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

    private function handlePageAction(TmContainer $player, ActionContext $context): void
    {
        $player->set("{$context->panel->name}.currentPage", $context->val);
        $winData = $this->window->build($context->panel, $player);
        $this->displayToLogin(Panel::Help->template(), $player->get('Login'), $winData);
    }

    private function handleChoiceAction(TmContainer $player, ActionContext $context): void
    {
        $choiceName = $context->panel->choiceName($context->val);
        $this->logDebug('choices', [$context->toArray(), $choiceName]);
        $this->handleAction($player, $context->panel, $choiceName);
    }

    private function handleAction(TmContainer $player, Panel $panel, string $choiceName): void
    {
        $this->logDebug("Action from panel: ", [
            'panel'  => $panel->name,
            'value'  => $panel->value,
            'choice' => $choiceName,
        ]);
    }

    private function handleCloseAction(TmContainer $player, ActionContext $context): void
    {
        $this->closeDisplayToLogin($player->get('Login'), $context->panel->value);
    }
}
