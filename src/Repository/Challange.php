<?php

declare(strict_types=1);

namespace Yuha\Trna\Repository;

use Yuha\Trna\Core\Controllers\RepoController;
use Yuha\Trna\Core\Enums\{GameMode, Table};
use Yuha\Trna\Core\{Server, TmContainer};
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Infrastructure\Gbx\{Client, GbxFetcher};
use Yuha\Trna\Infrastructure\Tmx\TmxFetcher;
use Yuha\Trna\Service\Aseco;

class Challange
{
    use LoggerAware;

    public function __construct(
        private Client $client,
        private GbxFetcher $gbxFetcher,
        private RepoController $repoController,
        private TmxFetcher $tmxFetcher
    ) {
        $this->initLog('Challange');
        $this->gbxFetcher->setXml(true);
        $file = $this->getCurrentChallengeInfo()->get('FileName');
        $this->gbxFetcher->processFile(Server::$trackDir . $file);
        $this->tmxFetcher->initTmx($this->gbxFetcher->UId);
    }

    public function getTmx(): TmxFetcher
    {
        return $this->tmxFetcher;
    }

    public function getGbx(): GbxFetcher
    {
        return $this->gbxFetcher;
    }

    public function getCurrentChallengeInfo(): TmContainer
    {
        $c = $this->client->query('GetCurrentChallengeInfo')->get('result');

        $this->formatChallenge($c);

        $c->set('Options', $this->gameInfo());

        return $c;
    }

    public function gameMode(): GameMode
    {
        $value = $this->getCurrentChallengeInfo()->get('Options.GameMode');

        return GameMode::tryFrom($value);
    }

    public function listMaps(int $size = 1, int $index = 1): TmContainer
    {
        $list = $this->client->query('GetChallengeList', [$size, $index])->get('result');
        $list->each(static function (TmContainer $c) {
            if ($c->has('FileName') || $c->has('GoldTime')) {
                $c->setMultiple([
                    'FileName'   => str_replace('\\', \DIRECTORY_SEPARATOR, $c->get('FileName', '')),
                    'GoldTime'   => Aseco::getFormattedTime($c->get('GoldTime', 0)),
                ]);
            }
        });

        return $list;
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

    private function gameInfo(): TmContainer
    {
        return $this->client->query('GetCurrentGameInfo')->get('result');
    }

    private function createChallengeInDb(): void
    {
        $data = [
            'Uid'         => $this->gbxFetcher->UId,
            'Name'        => $this->gbxFetcher->name,
            'Author'      => $this->gbxFetcher->author,
            'Environment' => $this->gbxFetcher->envir,
        ];

        $result = $this->repoController->insert(Table::CHALLENGES, $data, $this->gbxFetcher->UId);

        if (!$result['ok']) {
            match($result['reason']) {
                // already_exist is not critical information we could $this->logInfo("Challenge data for map: {$this->gbx->UId} already exist")
                'query_failed'   => $this->logWarning("Couldn't create data for {$this->gbxFetcher->UId} data:", $data),
                'execute_failed' => $this->logError("Insert execution failed with message: {$result['message']}"),
                default => null
            };
        }
    }
}
