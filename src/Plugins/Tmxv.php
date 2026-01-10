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
    private const DEFAULT_DATE = '1970-01-01';
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
        if ($player->get('cmd.action') !== 'tmxv') {
            return;
        }

        match ($player->get('cmd.mod')) {
            'videos'        => $this->showVideos($player),
            'video', 'gps'  => $this->handleVideoArgs($player),
            default         => $this->help($player)
        };
    }

    public function getVideo(): array
    {
        return $this->hasVideos() ? $this->videos : [];
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

    private function handleVideoArgs(TmContainer $player): void
    {
        match ($player->get('cmd.param')) {
            'list'   => $this->showVideos($player),
            'latest' => $this->sortAndShowVideos($player, 'latest'),
            'oldest' => $this->sortAndShowVideos($player, 'oldest'),
            default  => $this->help($player)
        };
    }

    private function sortAndShowVideos(TmContainer $player, string $order): void
    {
        if (!$this->hasVideos()) {
            $this->noVideos($player->get('Login'));
            return;
        }

        $this->sortVideo($order);
        $this->showVideos($player);
    }

    private function sortVideo(string $order): void
    {
        usort($this->videos, static function ($a, $b) use ($order) {
            $dateA = $a['PublishedAt'] ?? null;
            $dateB = $b['PublishedAt'] ?? null;

            $timestampA = $dateA instanceof \DateTimeInterface
                ? $dateA->getTimestamp()
                : strtotime($dateA ?? self::DEFAULT_DATE);

            $timestampB = $dateB instanceof \DateTimeInterface
                ? $dateB->getTimestamp()
                : strtotime($dateB ?? self::DEFAULT_DATE);

            return $order === 'latest'
                ? $timestampB <=> $timestampA
                : $timestampA <=> $timestampB;
        });
    }

    private function help(TmContainer $player): void
    {
        $maniaLinks = $this->pluginController->getPlugin(ManiaLinks::class);

        $maniaLinks->displayToLogin(
            Panel::Help->template(),
            $player->get('Login'),
            $this->windowBuilder->data(Panel::Help, $player),
        );
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
