<?php

declare(strict_types=1);

namespace Yuha\Trna\Service\Internal;

final class CallbackHelper
{
    private static array $paramMap = [
        'TrackMania.PlayerConnect'     => ['Login', 'isSpectator'],
        'TrackMania.PlayerDisconnect'  => ['Login'],
        'TrackMania.PlayerChat'        => ['playerId', 'Login', 'text'],
        'TrackMania.PlayerCheckpoint'  => ['playerId', 'Login', 'time', 'curLap', 'checkpointIndex'],
        'TrackMania.PlayerFinish'      => ['playerId', 'Login', 'time'],
        'TrackMania.EndRace'           => ['rankings', 'challenge'],
        'TrackMania.StatusChanged'     => ['statusCode', 'statusName'],
        'TrackMania.BeginChallenge'    => ['challenge', 'warmUp', 'matchContinuation'],
        'TrackMania.EndChallenge'      => ['rankings', 'challenge', 'wasWarmUp', 'matchContinuesOnNextChallenge', 'restartChallenge'],
        'TrackMania.PlayerManialinkPageAnswer' => ['playerId', 'Login', 'answer'],
        'TrackMania.BillUpdated'       => ['billId', 'state', 'stateName', 'transactionId'],
        'TrackMania.ChallengeListModified' => ['curIndex', 'nextIndex', 'isModified'],
        'TrackMania.PlayerInfoChanged' => ['playerInfo'],
        'TrackMania.VoteUpdated'       => ['stateName', 'Login', 'cmdAction', 'cmdMod'],
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
