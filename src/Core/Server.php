<?php

declare(strict_types=1);

namespace Yuha\Trna\Core;

final class Server
{
    public static string $game = 'TMF';
    public static string $ip = '127.0.0.1';
    public static string $version = '2.11.26';
    public static string $rootDir = '';
    public static string $gamePath = '';
    public static string $publicDir = '';
    public static string $gameDir = '';
    public static string $trackDir = '';
    public static string $jsonDir = '';
    public static string $twigDir = '';
    public static string $sqlDir = '';
    public static string $logsDir = '';
    public static string $login = '';
    public static string $pass = '';
    public static string $nickName = '';
    public static string $zone = '';
    public static string $build = '';
    public static string $packMask = '';
    public static string $name = '';
    public static string $comment = '';
    public static int $port = 5009;
    public static int $startTime = 0;
    public static int $id = 0;
    public static int $rights = 0;
    public static int $maxPlayers = 0;
    public static int $maxSpectators = 0;
    public static int $ladderMode = 0;
    public static int $vehicleNetQuality = 0;
    public static int $callVoteTimeout = 0;
    public static float $timeout = 10.0;
    public static float $ladderMax = 0.0;
    public static float $ladderMin = 0.0;
    public static float $callVoteRatio = 0.0;
    public static bool $private = false;
    private const int TM_ROOT = 2;
    private const int GAME_ROOT = 3;
    private const int ZONE_OFFSET = 6;

    public static function setPaths(): void
    {
        self::$rootDir   = \dirname(__DIR__, self::TM_ROOT) . \DIRECTORY_SEPARATOR;
        self::$gamePath  = \dirname(__DIR__, self::GAME_ROOT) . \DIRECTORY_SEPARATOR;
        self::$publicDir = self::$rootDir   . 'public'    . \DIRECTORY_SEPARATOR;
        self::$gameDir   = self::$gamePath  . 'GameData'  . \DIRECTORY_SEPARATOR;
        self::$trackDir  = self::$gameDir   . 'Tracks'    . \DIRECTORY_SEPARATOR;
        self::$jsonDir   = self::$publicDir . 'json'      . \DIRECTORY_SEPARATOR;
        self::$twigDir   = self::$publicDir . 'templates' . \DIRECTORY_SEPARATOR;
        self::$sqlDir    = self::$publicDir . 'sql'       . \DIRECTORY_SEPARATOR;
        self::$logsDir   = self::$rootDir   . 'logs'      . \DIRECTORY_SEPARATOR;
        self::$login     = $_ENV['admin_login'] ?? 'none';
        self::$pass      = $_ENV['admin_password'] ?? 'none';
        self::$startTime = time();
    }

    public static function setServerInfo(
        TmContainer $info,
        TmContainer $version,
        TmContainer $ladder,
        string $mask,
        TmContainer $options,
    ): void {
        self::$id = $info->get('PlayerId');
        self::$nickName = $info->get('NickName');
        self::$zone = substr($info->get('Path'), self::ZONE_OFFSET);
        self::$rights   = $info->get('OnlineRights');
        // build
        self::$build   = $version->get('Build');
        //ladder
        self::$ladderMin = $ladder->get('LadderServerLimitMin');
        self::$ladderMax = $ladder->get('LadderServerLimitMax');
        // packmask
        self::$packMask = $mask;
        // options
        self::$name              = ucfirst($options->get('Name'));
        self::$comment           = $options->get('Comment');
        self::$private           = $options->get('Password') !== '';
        self::$maxPlayers        = $options->get('CurrentMaxPlayers');
        self::$maxSpectators     = $options->get('CurrentMaxSpectators');
        self::$ladderMode        = $options->get('CurrentLadderMode');
        self::$vehicleNetQuality = $options->get('CurrentVehicleNetQuality');
        self::$callVoteTimeout   = $options->get('CurrentCallVoteTimeOut');
        self::$callVoteRatio     = $options->get('CallVoteRatio');
    }
}
