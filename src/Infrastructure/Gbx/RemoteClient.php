<?php

declare(strict_types=1);

namespace Yuha\Trna\Infrastructure\Gbx;

use Yuha\Trna\Core\TmContainer;

final class RemoteClient
{
    private static TmContainer $admin;
    private static Client $client;

    public static function init(Client $ac, string $login): void
    {
        self::$client = $ac;
        self::$admin = TmContainer::fromArray([
            'Login' => $login,
            'NickName' => $_ENV['server_name'],
            'PlayerId' => 0,
            'TeamId' => -1,
            'OnlineRights' => 3,
            'SpectatorStatus' => 2550101,
            'LadderRanking' => 0,
            'Flags' => 1100000,
            'isMasterAdmin' => true,
            'created' => time(),
        ]);
    }

    public function execute(
        string $method,
        array $params = [],
        bool $multi = false
    ): Deferred {
        if ($multi) {
            return self::$client->multicall($params);
        }

        return self::$client->query($method, $params);
    }

    public function getAdmin(): TmContainer
    {
        return self::$admin;
    }
}
