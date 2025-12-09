<?php

declare(strict_types=1);

namespace Yuha\Trna\Repository;

use Yuha\Trna\Core\{Server, TmContainer};
use Yuha\Trna\Infrastructure\Gbx\{Client, GbxFetcher};
use Yuha\Trna\Infrastructure\Tmx\TmxFetcher;
use Yuha\Trna\Service\Aseco;

class Challange
{
    public function __construct(
        private Client $client,
        private GbxFetcher $gbxFetcher,
        private TmxFetcher $tmxFetcher
    ) {
        $this->gbxFetcher->setXml(true);
        $file = $this->getCurrentChallengeInfo()->get('FileName');
        $this->gbxFetcher->processFile(Server::$trackDir . $file);
        $this->tmxFetcher->initTmx($this->gbxFetcher->UId);
    }

    public function getCurrentChallengeInfo(): TmContainer
    {
        $c = $this->client->query('GetCurrentChallengeInfo')->get('result');

        $this->formatChallenge($c);

        return $c;
    }

    private function formatChallenge(TmContainer $c): void
    {
        $c->setMultiple([
            'FileName'   => str_replace('\\', \DIRECTORY_SEPARATOR, $c->get('FileName', '')),
            'AuthorTime' => Aseco::getFormattedTime($c->get('AuthorTime', 0)),
            'GoldTime'   => Aseco::getFormattedTime($c->get('GoldTime', 0)),
            'SilverTime' => Aseco::getFormattedTime($c->get('SilverTime', 0)),
            'BronzeTime' => Aseco::getFormattedTime($c->get('BronzeTime', 0)),
        ]);
    }
}
