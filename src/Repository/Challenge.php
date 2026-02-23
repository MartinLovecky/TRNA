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

class Challenge
{
    use LoggerAware;

    public function __construct(
        private Client $client,
        private GbxFetcher $gbxFetcher,
        private LocalRecord $localRecord,
        private RepoController $repoController,
        private TmxFetcher $tmxFetcher
    ) {
        $this->initLog('Challenge');
        $this->gbxFetcher->setXml(true);
        $file = $this->getCurrentChallengeInfo('FileName');
        $this->gbxFetcher->processFile(Server::$trackDir . $file);
        $this->tmxFetcher->initTmx($this->gbxFetcher->UId);
        $this->createChallengeInDb();
        $this->localRecord->createDBRecord($this->gbxFetcher->UId);
    }

    public function getTmx(): TmxFetcher
    {
        return $this->tmxFetcher;
    }

    public function getGbx(): GbxFetcher
    {
        return $this->gbxFetcher;
    }

    /**
     * Returns ChallengeInfo as TmContainer or specific value if it exist
     *
     * @return mixed returns TmContainer when $path is not set
     */
    public function getCurrentChallengeInfo(?string $path = null): mixed
    {
        $c = $this->client->query('GetCurrentChallengeInfo')->get('result');

        $this->formatChallenge($c);

        $c->set('Options', $this->gameInfo());

        return $path ? $c->get($path) : $c;
    }

    public function getTotalMaps(): int
    {
        return $this->client
            ->query('GetChallengeList', [100000, 0])
            ->get('result')
            ->count();
    }

    public function gameMode(): GameMode
    {
        $value = $this->getCurrentChallengeInfo('Options.GameMode');

        return GameMode::tryFrom($value);
    }

    /**
     * Get uids from nextChallengeIndex
     *
     * @param  integer      $amount how many to get
     * @return string|array string when amount is 1
     */
    public function getNextUids(int $amount = 1): string|array
    {
        $index = $this->client->query('GetNextChallengeIndex')->get('result');
        $uids = $this->listMaps($amount, $index)->map(static fn (TmContainer $c) => $c->get('UId'));

        return $amount === 1 ? $uids[0] : $uids;
    }

    public function getChallengeFromDB(?string $uid = null): array
    {
        $Uid = $uid ?? $this->getCurrentChallengeInfo('UId');
        $result = $this->repoController->fetch(Table::CHALLENGES, 'Uid', $Uid);

        if (isset($result['reason'])) {
            $this->logWarning("Challange: {$Uid} doesn't exist in database please create it first");
            return [];
        }

        return $result;
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
            'Name'        => Aseco::stripColors($this->gbxFetcher->name),
            'Author'      => Aseco::stripColors($this->gbxFetcher->author),
            'Environment' => $this->gbxFetcher->envir,
        ];

        $result = $this->repoController->insert(Table::CHALLENGES, $data, $this->gbxFetcher->UId);

        if (!$result['ok']) {
            match ($result['reason']) {
                // already_exist is not critical information we could $this->logInfo("Challenge data for map: {$this->gbx->UId} already exist")
                'query_failed'   => $this->logWarning("Couldn't create data for {$this->gbxFetcher->UId} data:", $data),
                'execute_failed' => $this->logError("Insert execution failed with message: {$result['message']}"),
                default => null
            };
        }
    }
}
