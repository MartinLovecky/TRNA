<?php

declare(strict_types=1);

namespace Yuha\Trna\Infrastructure\Gbx;

use Yuha\Trna\Core\TmContainer;

final class RemoteClient
{
    private static TmContainer $admin;
    private static GbxRpcClient $client;

    public static function init(GbxRpcClient $ac, string $login): void
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

    public static function execute(
        string $method,
        array $params = [],
        bool $multi = false
    ): TmContainer {
        if ($multi) {
            $x = self::$client->multicall($params);
            return TmContainer::fromArray($x);
        }

        return self::$client->query($method, $params);
    }

    public static function getAdmin(): TmContainer
    {
        return self::$admin;
    }
}
