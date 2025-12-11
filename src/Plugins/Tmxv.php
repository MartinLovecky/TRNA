<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Service\YoutubeClient;
use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\PluginController;
use Yuha\Trna\Repository\Challange;

class Tmxv implements DependentPlugin
{
    private PluginController $pluginController;
    private array $videos = [];

    public function __construct(
        private Challange $challange,
        private YoutubeClient $youtubeClient
    ) {}

    public function setRegistry(PluginController $pluginController): void
    {
        $this->pluginController = $pluginController;
    }

    public function onNewChallenge(): void
    {
        $tmx = $this->challange->getTmx();

        if (!isset($tmx->id)) {
            return;
        }

        dd($tmx->replayurl);
    }

    public function onChatCommand(TmContainer $player): void
    {
        //NOTE: we could create Enum similar as RaspVotes
        if ($player->get('command.name') !== 'tmxv') {
            return;
        }

        match ($player->get('command.param')) {
            'videos'        => $this->showVideos($player),
            'video', 'gps'  => $this->handleVideoArgs($player),
            default         => $this->help($player)
        };
    }

    private function showVideos(TmContainer $player): void
    {
        $this->hasVideos()
            ? $this->showWindow($player)
            : $this->noVideos($player->get('Login'));
    }

    private function hasVideos(): bool
    {
        return !empty($this->videos);
    }

    private function showWindow() {}
    private function noVideos(string $login) {}
    private function handleVideoArgs() {}
    private function help(TmContainer $player) {}
}
