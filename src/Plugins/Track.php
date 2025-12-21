<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\Color;
use Yuha\Trna\Core\Enums\GameMode;
use Yuha\Trna\Core\Server;
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Infrastructure\Gbx\Client;
use Yuha\Trna\Repository\Challange;
use Yuha\Trna\Service\Aseco;

class Track
{
    private int $startTime = 0;
    private int $totalReplays = 0;

    public function __construct(
        private Color $color,
        private Client $client,
        private Challange $challange
    ) {
    }

    public function onChatCommand(TmContainer $player): void
    {
        if ($player->get('command.name') !== 'track') {
            return;
        }

        match($player->get('command.param')) {
            'playtime' => null,
            'time'     => null,
            'track'    => null,
            default    => null
        };
    }

    public function onNewChallenge(): void
    {
        $c = $this->color;
        $info = $this->challange->getCurrentChallengeInfo();
        $mapName = $info->get('Name');
        $author = $info->get('Author');
        $this->startTime = time();

        $msg = <<<MSG
            {$c->white}** {$c->green}Current track {$mapName}{$c->z->green} by Author: {$author}
        MSG;

        $this->client->sendChatMessageToAll($msg);
    }

    public function onEndRace(): void
    {
        $gameMode = $this->challange->gameMode();

        if ($gameMode === GameMode::Race || $gameMode === GameMode::Stunts) {
            return;
        }

        $c = $this->color;
        $name = $this->formatName();
        $playTime = Aseco::getFormattedTime($this->timePlaying());
        $totalTime = Aseco::getFormattedTime(time() - Server::$startTime);

        if ($this->totalReplays === 0) {
            $msg = <<<MSG
                {$c->white}**{$c->green} Track {$name}{$c->z->green} finished after: {$playTime}
            MSG;

            Aseco::console($msg);
            $this->client->sendChatMessageToAll($msg);
            return;
        }

        $msg = <<<MSG
            {$c->white}** Track {$name} finished after $playTime ({$this->totalReplays} replays total: {$totalTime})
        MSG;

        Aseco::console($msg);
        $this->client->sendChatMessageToAll($msg);
    }

    private function formatName(): string
    {
        $tmx = $this->challange->getTmx();

        if (isset($tmx->name)) {
            return "\$l[http://tmnforever.tm-exchange.com/main.aspx?"
                . "action=trackshow&id=$tmx->id]$tmx->name";
        }

        return $this->challange->getGbx()->name;
    }

    private function timePlaying(): int
    {
        return time() - $this->startTime;
    }

    private function help()
    {

    }
}
