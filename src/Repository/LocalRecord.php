<?php

declare(strict_types=1);

namespace Yuha\Trna\Repository;

use Yuha\Trna\Core\Controllers\RepoController;
use Yuha\Trna\Core\Enums\Table;
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Service\Aseco;

class LocalRecord
{
    use LoggerAware;

    public function __construct(private RepoController $repoController)
    {
        $this->initLog('LocalRecord');
    }

    public function getRecord(string $uid): ?TmContainer
    {
        $res = $this->repoController->fetch(
            Table::RECORDS,
            'ChallengeId',
            $uid,
            ['Times', 'Checkpoints'],
        );

        if (isset($res['ok'])) {
            $this->logInfo("{$uid} doesnt exist in table: " . Table::RECORDS->name);
            return null;
        }

        $t = array_key_first($res);
        $x = Aseco::safeJsonDecode($t);

        if (!$x) {
            $this->logError("Failed decode json: {$t} from array:", $res);
            return null;
        }

        asort($x);
        $time = TmContainer::fromArray($x);
        $time->set('checkpoints', $res[$t]);

        return $time;
    }

    public function createRecord(string $uid): void
    {
        $data = [
            'ChallengeId' => $uid,
            'Times'       => '{}',
            'Checkpoints' => '{}',
        ];

        $res = $this->repoController->insert(Table::RECORDS, $data, $uid);

        if (!$res['ok']) {
            match ($res['reason']) {
                'query_failed' => $this->logError("createRecord {$uid} failed with data:", $data),
                'execute_failed' => $this->logError("createRecord err: {$res['message']}"),
                default => null
            };
        }
    }

    public function updateRecord(TmContainer $player, string $uid): void
    {
        $data = [
            'Times' => json_encode([
                $player->get('Login') => $player->get('finishTime'),
            ]),
            'Checkpoints' => json_encode([
                $player->get('Login') => $player->get('checkpoints'),
            ]),
        ];

        $res = $this->repoController->update(Table::RECORDS, $data, $uid);

        if (!$res['ok']) {
            match ($res['reason']) {
                'doesnt_exist'   => $this->logWarning("updateRecord can't update non-existent UID: {$uid}"),
                'query_failed'   => $this->logError("updateRecord {$uid} query failed"),
                'execute_failed' => $this->logError("updateRecord err: {$res['message']}")
            };
        }
    }

    public function deleteRecord(string $uid): void
    {
        $res = $this->repoController->delete(Table::RECORDS, 'ChallengeId', $uid);
        if (!$res['ok']) {
            match ($res['reason']) {
                'doesnt_exist'   => $this->logWarning("deleteRecord can't delete non-existent UID: {$uid}"),
                'query_failed'   => $this->logError("deleteRecord {$uid} query failed"),
                'execute_failed' => $this->logError("deleteRecord err: {$res['message']}")
            };
        }
    }
}
