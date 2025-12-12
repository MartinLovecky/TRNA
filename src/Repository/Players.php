<?php

declare(strict_types=1);

namespace Yuha\Trna\Repository;

use Yuha\Trna\Core\Controllers\RepoController;
use Yuha\Trna\Core\{Server, TmContainer};
use Yuha\Trna\Infrastructure\Gbx\Client;
use Yuha\Trna\Service\Aseco;

class Players
{
    public int $numSpecs = 0;
    public int $numPlayers = 0;

    public function __construct(
        private Client $client,
        private RepoController $repoController,
        private TmContainer $tmContainer
    ) {
    }

    public function add(string $login, bool $isMasterAdmin = false): void
    {
        if ($this->hasPlayer($login)) {
            return;
        }

        $p = $this->getDetailedPlayerInfo($login);

        $this->tmContainer->set($login, $p);
        $this->tmContainer->setMultiple([
            "{$login}.created" => time(),
            "{$login}.isMasterAdmin" => $isMasterAdmin,
            "{$login}.isAdmin" => $this->isAdmin($login),
        ]);

        if ($p->get('IsSpectator')) {
            $this->numSpecs++;
        }

        $this->numPlayers = $this->tmContainer->count();
    }

    public function getByLogin(string $login): ?TmContainer
    {
        return $this->tmContainer->get($login);
    }

    public function playerCount(bool $includeSpectators = false): int
    {
        if ($includeSpectators) {
            return $this->numPlayers;
        }

        return $this->numPlayers - $this->numSpecs;
    }

    public function getDetailedPlayerInfo(string $login): TmContainer
    {
        $c = $this->client->query('GetDetailedPlayerInfo', [$login])->get('result');
        $c->set('Nation', Aseco::mapCountry($c->get('Path')));

        return $c;
    }

    public function getPlayerList(int $limit = 30, int $start = 0, int $type = 0): TmContainer
    {
        return $this->client->query('GetPlayerList', [$limit, $start, $type])->get('result');
    }

    private function hasPlayer(string $login): bool
    {
        return $this->tmContainer->has($login);
    }

    private function isAdmin(string $login): bool
    {
        $admins = TmContainer::fromJsonFile(Server::$jsonDir . 'Admins.json')->get('Admins')->getIterator();
        foreach ($admins as $_ => $value) {
            return $login === $value;
        }
        return false;
    }
}
