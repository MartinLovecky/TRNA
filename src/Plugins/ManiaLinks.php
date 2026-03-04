<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\{PluginController, VoteController};
use Yuha\Trna\Core\DTO\WindowContext;
use Yuha\Trna\Core\Enums\{Action, Window};
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Core\Window\{Builder, Codec, Registry, WindowDataRegistry};
use Yuha\Trna\Infrastructure\Gbx\GameClient;

class ManiaLinks implements DependentPlugin
{
    use LoggerAware;
    private const int OUT = 0;
    private PluginController $pluginController;

    public function __construct(
        private readonly Builder $builder,
        private readonly GameClient $client,
        private readonly Codec $codec,
        private readonly Registry $registry,
        private readonly WindowDataRegistry $dataRegistry,
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

    private function handleAction(TmContainer $player, WindowContext $ctx): void
    {
        $login = $player->get('Login');
        $window = $ctx->window;

        match ($ctx->action) {
            Action::Next  => $this->registry->next($login, $window),
            Action::Prev  => $this->registry->prev($login, $window),
            Action::First => $this->registry->first($login, $window),
            Action::Last  => $this->registry->last($login, $window),
            //Action::Page  => $this->registry->setPage($login, $window, $window->value),
            Action::Open  => $this->openWindow($login, $ctx),
            Action::Close => $this->client->closeWindow($window->value),
            default => null
        };

        if (\in_array($ctx->action, [Action::Next, Action::Prev, Action::First, Action::Last], true)) {
            $this->renderWindow($login, $window);
        }
    }

    public function openWindow(string $login, WindowContext $ctx): void
    {
        // TODO SET context foreach 'window' if necessary dynamicly

        // $this->registry->setContext($login, $ctx->window, [
        //     'isMyCp' => $ctx->value === 1
        // ]);

        $this->registry->setPage($login, $ctx->window, 1);
        $this->renderWindow($login, $ctx->window);
    }

    private function renderWindow(
        string $login,
        Window $window,
        string $header = 'help'
    ): void {
        $context = $this->registry->getContext($login, $window);
        $provider = $this->dataRegistry->get($window);
        $rows = $provider->getData($login, $window, $context);

        $this->builder->display(
            $window,
            $login,
            $rows,
            $header,
        );
    }
}
