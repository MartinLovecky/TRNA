<?php

declare(strict_types=1);

namespace Yuha\Trna\Repository;

use Yuha\Trna\Core\Controllers\RepoController;
use Yuha\Trna\Core\Enums\Table;
use Yuha\Trna\Core\{Server, TmContainer};
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Infrastructure\Gbx\Client;
use Yuha\Trna\Service\Aseco;

class Players
{
    use LoggerAware;
    public int $numSpecs = 0;
    public int $numPlayers = 0;

    public function __construct(
        private Client $client,
        private RepoController $repoController,
        private TmContainer $tmContainer
    ) {
        $this->initLog('Players');
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
            "{$login}.IPAddress" => preg_replace('/:\d+/', '', $p->get('IPAddress', '')),
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
        $admins = TmContainer::fromJsonFile(Server::$jsonDir . 'Admins')->get('Admins')->getIterator();
        foreach ($admins as $_ => $value) {
            return $login === $value;
        }
        return false;
    }

    private function createPlayerInD(TmContainer $player): void
    {
        # Player
        $data = [
            'Login'      => $player->get('Login'),
            'Game'       => 'TMF',
            'NickName'   => $player->get('NickName'),
            'playerID'   => $player->get('Login'),
            'Nation'     => $player->get('Nation'),
            'Wins'       => $player->get('LadderStats.NbrMatchWins'),
            'TimePlayed' => time() - $player->get('created'),
            'TeamName'   => $player->get('LadderStats.TeamName'),
            'LastSeen'   => date('Y-m-d H:i:s'),
        ];

        $result = $this->repoController->insert(Table::PLAYERS, $data, $player->get('Login'));

        if (!$result['ok']) {
            match ($result['reason']) {
                // already_exist is not critical
                'query_failed'   => $this->logWarning("Couldn't create Player: {$player->get('Login')} with data:", $data),
                'execute_failed' => $this->logError("Insert execution failed with message: {$result['message']}"),
                default => null
            };
        }
        # Player extra
        $data = [
            'cps'       => -1,
            'dedicps'   => -1,
            'donations' => 0,
            'style'     => $_ENV['window_style'],
            'panels'    => json_encode([
                'admin'   => $_ENV['admin_panel'],
                'donate'  => $_ENV['donate_panel'],
                'records' => $_ENV['records_panel'],
                'vote'    => $_ENV['vote_panel'],
            ]),
            'playerID'  => $player->get('Login'),
        ];

        $result = $this->repoController->insert(Table::PLAYERS_EXTRA, $data, $player->get('Login'));

        if (!$result['ok']) {
            match ($result['reason']) {
                // already_exist is not critical
                'query_failed'   => $this->logWarning("Couldn't create ExtraData for {$player->get('Login')} with data:", $data),
                'execute_failed' => $this->logError("Insert execution failed with message: {$result['message']}"),
                default => null
            };
        }
    }
}
