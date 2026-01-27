<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\PluginController;
use Yuha\Trna\Core\Controllers\VoteController;
use Yuha\Trna\Core\Enums\Action;
use Yuha\Trna\Core\Enums\Window;
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Core\Window\Builder;
use Yuha\Trna\Core\Window\Codec;
use Yuha\Trna\Core\Window\Context;
use Yuha\Trna\Core\Window\Data;
use Yuha\Trna\Core\Window\Registry;
use Yuha\Trna\Infrastructure\Gbx\Client;

class ManiaLinks implements DependentPlugin
{
    use LoggerAware;
    private const int OUT = 0;
    private PluginController $pluginController;

    public function __construct(
        private Builder $builder,
        private Client $client,
        private Codec $codec,
        private Data $data,
        private Registry $registry,
        private VoteController $voteController,
    ) {
        $this->initLog('Plugin-ManiaLinks');
    }

    public function setRegistry(PluginController $pluginController): void
    {
        $this->pluginController = $pluginController;
    }

    public function onAnswer(TmContainer $player): void
    {
        $context = $this->codec->decode($player->get('encodedAction'));
        $this->handleAction($player, $context);
    }

    private function handleAction(TmContainer $player, Context $ctx): void
    {
        $playerId = $player->get('Login');
        $window = $ctx->window;
        match ($ctx->action) {
            Action::Next  => $this->registry->next($playerId, $window),
            Action::Prev  => $this->registry->prev($playerId, $window),
            Action::First => $this->registry->first($playerId, $window),
            Action::Last  => $this->registry->last($playerId, $window),
            Action::Page  => $this->registry->setPage($playerId, $window, $window->value),
            Action::Open  => $this->builder->display(Window::from($ctx->value), $playerId, $this->data->getData($window)),
            Action::Close => $this->client->sendXmlToAll("<manialink id='{$window->value}'></manialink>"),
            Action::Yes, Action::No, Action::Cancel, Action::Pass => $this->handleChoice($player, $ctx),
            default => null
        };

        if (\in_array($ctx->action, [Action::Next, Action::Prev, Action::First, Action::Last], true)) {
            $this->builder->display($window, $playerId, $this->data->getData($window));
        }
    }

    private function handleChoice(TmContainer $player, Context $ctx)
    {
    }
}
