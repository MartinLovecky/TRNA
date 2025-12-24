<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\{Color, TmContainer};
use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\PluginController;
use Yuha\Trna\Core\Enums\Panel;
use Yuha\Trna\Core\Window\WindowBuilder;
use Yuha\Trna\Infrastructure\Gbx\Client;
use Yuha\Trna\Repository\Challenge;
use Yuha\Trna\Service\{Aseco, YoutubeClient};
use Yuha\Trna\Service\Internal\YoutubeSearchResults;

class Tmxv implements DependentPlugin
{
    private PluginController $pluginController;
    private ?array $videos = null;

    public function __construct(
        private Color $c,
        private Client $client,
        private Challenge $challenge,
        private WindowBuilder $windowBuilder,
        private YoutubeClient $youtubeClient
    ) {
    }

    public function setRegistry(PluginController $pluginController): void
    {
        $this->pluginController = $pluginController;
    }

    // ---------- Event Handlers  ----------

    public function onNewChallenge(): void
    {
        $tmx = $this->challenge->getTmx();

        if (isset($tmx->ytlink)) {
            $this->videos = [
                'title' => $tmx->ytTitle,
                'link'  => $tmx->ytlink,
                'publishedAt' => $tmx->publishedAt,
            ];
            return;
        }

        $gbx = $this->challenge->getGbx();
        $mapName = Aseco::stripColors($gbx->name);
        $search = $this->youtubeClient->search($mapName);

        if (!empty($search)) {
            $this->videos = $this->mapYoutubeResults($search);
        }
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

    // ---------- Chat functions  ----------

    private function showVideos(TmContainer $player): void
    {
        $this->hasVideos()
            ? $this->showWindow($player)
            : $this->noVideos($player->get('Login'));
    }

    private function hasVideos(): bool
    {
        return \is_array($this->videos);
    }

    private function showWindow(TmContainer $player): void
    {
        $maniaLinks = $this->pluginController->getPlugin(ManiaLinks::class);

        $maniaLinks->displayToLogin(
            Panel::Tmxv->template(),
            $player->get('Login'),
            $this->windowBuilder->data(Panel::Tmxv, $player),
        );
    }

    private function noVideos(string $login): void
    {
        $msg = <<<MSG
            {$this->c->white}** {$this->c->green}No GPS videos found for this track.
        MSG;
        $this->client->sendChatMessageToLogin($msg, $login);
    }

    private function handleVideoArgs()
    {
    }

    private function help(TmContainer $player)
    {
    }

    private function mapYoutubeResults(YoutubeSearchResults $res): array
    {
        return array_map(
            static fn (object $videos): array => [
                'title' => $videos->title,
                'link'  => $videos->videoLink,
                'publishedAt' => $videos->publishedAt,
            ],
            $res->videos,
        );
    }
}
