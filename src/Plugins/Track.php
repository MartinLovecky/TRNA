<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\{Color, Server, TmContainer};
use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\PluginController;
use Yuha\Trna\Core\Enums\{GameMode, Panel};
use Yuha\Trna\Core\Window\WindowBuilder;
use Yuha\Trna\Infrastructure\Gbx\Client;
use Yuha\Trna\Repository\Challenge;
use Yuha\Trna\Service\Aseco;

class Track implements DependentPlugin
{
    private int $startTime = 0;
    private int $totalReplays = 0;
    private PluginController $pluginController;

    public function __construct(
        private Color $c,
        private Client $client,
        private Challenge $challenge,
        private WindowBuilder $windowBuilder
    ) {
    }

    public function setRegistry(PluginController $pluginController): void
    {
        $this->pluginController = $pluginController;
    }

    // ---------- Event Handlers  ----------

    public function onChatCommand(TmContainer $player): void
    {
        if ($player->get('cmd.action') !== 'track') {
            return;
        }

        match ($player->get('cmd.mod')) {
            'playtime' => $this->playtime($player->get('Login')),
            'time'     => $this->showtime($player->get('Login')),
            'track'    => $this->trackinfo($player->get('Login')),
            default    => $this->help($player)
        };
    }

    public function onNewChallenge(): void
    {
        $info = $this->challenge->getCurrentChallengeInfo();
        $mapName = $info->get('Name');
        $author = $info->get('Author');
        $this->startTime = time();

        $msg = <<<MSG
            {$this->c->white}** {$this->c->green}Current track {$mapName}{$this->c->z->green} by Author: {$author}
        MSG;

        $this->client->sendChatMessageToAll($msg);
    }

    public function onEndRace(): void
    {
        $gameMode = $this->challenge->gameMode();

        if ($gameMode === GameMode::Race || $gameMode === GameMode::Stunts) {
            return;
        }

        $name = $this->formatName();
        $playTime = Aseco::getFormattedTime($this->timePlaying());
        $totalTime = Aseco::getFormattedTime(time() - Server::$startTime);

        if ($this->totalReplays === 0) {
            $msg = <<<MSG
                {$this->c->white}**{$this->c->green} Track {$name}{$this->c->z->green} finished after: {$playTime}
            MSG;

            Aseco::console($msg);
            $this->client->sendChatMessageToAll($msg);
            return;
        }

        $msg = <<<MSG
            {$this->c->white}** Track {$name} finished after $playTime ({$this->totalReplays} replays total: {$totalTime})
        MSG;

        Aseco::console($msg);
        $this->client->sendChatMessageToAll($msg);
    }

    // ---------- Chat functions  ----------

    private function playtime(string $login): void
    {
        $info = $this->challenge->getCurrentChallengeInfo();
        $mapName = $info->get('Name');

        $msg = <<<MSG
            {$this->c->white}** {$mapName}{$this->c->z->green} has been played for {$this->c->white}{$this->timePlaying()}
        MSG;

        $this->client->sendChatMessageToLogin($msg, $login);
    }

    private function showtime(string $login): void
    {
        $time = date('l, M d Y \a\t H:i:s T');
        $msg = <<<MSG
            {$this->c->white}** {$this->c->green}Server Time: {$this->c->white}{$time}
        MSG;

        $this->client->sendChatMessageToLogin($msg, $login);
    }

    private function trackinfo(string $login): void
    {
        $challenge = $this->challenge->getCurrentChallengeInfo();

        $msg = <<<MSG
            {$challenge->get('Name')}{$this->c->green} Author: {$challenge->get('Author')}{$this->c->gz}
            {$this->c->white}Author Time : {$this->c->green}{$challenge->get('AuthorTime')}
            {$this->c->white}Gold   Time : {$this->c->gold}{$challenge->get('GoldTime')}
            {$this->c->white}Silver Time : {$this->c->silver}{$challenge->get('SilverTime')}
            {$this->c->white}Bronze Time : {$this->c->bronze}{$challenge->get('BronzeTime')}
        MSG;

        $this->client->sendChatMessageToLogin($msg, $login);
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

    private function formatName(): string
    {
        $tmx = $this->challenge->getTmx();

        if (isset($tmx->name)) {
            return "\$l[http://tmnforever.tm-exchange.com/main.aspx?"
                . "action=trackshow&id=$tmx->id]$tmx->name";
        }

        return $this->challenge->getGbx()->name;
    }

    private function timePlaying(): int
    {
        return time() - $this->startTime;
    }
}
