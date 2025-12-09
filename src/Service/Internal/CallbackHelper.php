<?php

declare(strict_types=1);

namespace Yuha\Trna\Service\Internal;

final class CallbackHelper
{
    private static array $paramMap = [
        'TrackMania.PlayerChat'        => ['chatType', 'Login', 'message'],
        'TrackMania.PlayerConnect'     => ['Login', 'isSpectator'],
        'TrackMania.PlayerDisconnect'  => ['Login'],
        'TrackMania.PlayerFinish'      => ['finishTime', 'Login', 'rank'],
        'TrackMania.PlayerInfoChanged' => ['playerInfo'],
        'TrackMania.StatusChanged'     => ['statusCode', 'statusName'],
        'TrackMania.EndRace'           => ['playerInfo', 'challengeInfo'],
        'TrackMania.EndChallenge'      => ['playerInfo', 'challengeInfo', 'forceRestart', null, 'reservedFlag'],
        'TrackMania.PlayerCheckpoint'  => ['playerId', 'Login', 'time', null, 'checkpointIndex'],
        'TrackMania.PlayerManialinkPageAnswer' => ['playerId', 'Login', 'maniaLinkId'],
    ];

    public static function getNamedParams(string $methodName): array
    {
        return self::$paramMap[$methodName] ?? [];
    }

    public static function setMapping(string $methodName, array $names): void
    {
        self::$paramMap[$methodName] = $names;
    }
}
