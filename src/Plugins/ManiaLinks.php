<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\{PluginController, VoteController};
use Yuha\Trna\Core\Enums\{Action};
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Core\Window\{Builder, Codec, Context, Data, Registry};
use Yuha\Trna\Infrastructure\Gbx\Client;

class ManiaLinks implements DependentPlugin
{
    use LoggerAware;
    private const int OUT = 0;
    private PluginController $pluginController;

    public function __construct(
        private readonly Builder $builder,
        private readonly Client $client,
        private readonly Codec $codec,
        private readonly Data $data,
        private readonly Registry $registry,
        private readonly VoteController $voteController,
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
            Action::Open  => $this->openWindow($playerId, $ctx),
            Action::Close => $this->closeWindow($window->value),
            Action::Yes, Action::No, Action::Cancel, Action::Pass => $this->handleChoice($playerId, $ctx),
            default => null
        };

        if (\in_array($ctx->action, [Action::Next, Action::Prev, Action::First, Action::Last], true)) {
            $this->builder->display($window, $playerId, $this->data->getData($window));
        }
    }

    public function closeWindow(int $id): void
    {
        $this->client->sendXmlToAll("<manialink id='{$id}'></manialink>");
    }

    public function openWindow(string $playerId, Context $ctx)
    {
        $this->builder->display(
            $ctx->window,
            $playerId,
            $this->data->getData($ctx->window),
        );
    }

    private function handleChoice(string $playerId, Context $ctx)
    {
    }
}
