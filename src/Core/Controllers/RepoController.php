<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Controllers;

use Yuha\Trna\Core\Enums\Table;
use Yuha\Trna\Repository\Fluent;

class RepoController
{
    private const int ZERO_ITEMS = 0;
    private const int ONE_ITEM   = 1;
    private const int TWO_ITEMS  = 2;

    public function __construct(private Fluent $fluent)
    {
    }

    /**
     * Insert a row into the specified table, if it does not exist.
     *
     * @return array{ok: bool, reason?: string, message?: string}
     */
    public function insert(Table $table, array $data, mixed $condition): array
    {
        $check = $this->check($table);

        if ($this->exist($table, $check, $condition)) {
            return ['ok' => false, 'reason' => 'already_exist'];
        }

        try {
            $table = strtolower($table->name);
            $r = $this->fluent->query->insertInto($table)->values($data)->execute();
            if (!$r) {
                return ['ok' => false, 'reason' => 'query_failed'];
            }
            return ['ok' => true];
        } catch (\Exception $e) {
            return ['ok' => false, 'reason' => 'execute_failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Update a row in the specified table, if it exists.
     *
     * @return array{ok: bool, reason?: string, message?: string}
     */
    public function update(Table $table, array $data, mixed $condition): array
    {
        $check = $this->check($table);

        if (!$this->exist($table, $check, $condition)) {
            return ['ok' => false, 'reason' => 'doesnt_exist'];
        }

        try {
            $table = strtolower($table->name);
            $r = $this->fluent->query->update($table)->set($data)->where($check, $condition)->execute();
            if (!\is_int($r)) {
                return ['ok' => false, 'reason' => 'query_failed' . $r];
            }
            return ['ok' => true];
        } catch (\Exception $e) {
            return ['ok' => false, 'reason' => 'execute_failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Deletes data in the specified table, if it exists.
     *
     * @return array{ok: bool, reason?: string, message?: string}
     */
    public function delete(Table $table, string $column, mixed $condition): array
    {
        if (!$this->exist($table, $column, $condition)) {
            return ['ok' => false, 'reason' => 'doesnt_exist'];
        }

        try {
            $table = strtolower($table->name);
            $r = $this->fluent->query->deleteFrom($table)->where($column, $condition)->execute();
            if (!$r) {
                return ['ok' => false, 'reason' => 'query_failed'];
            }
            return ['ok' => true];
        } catch (\Exception $e) {
            return ['ok' => false, 'reason' => 'execute_failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Retrieve data from a table with optional filtering and column selection.
     *
     * @param Table $table  The table to query
     * @param mixed $column Column to filter by
     * @param mixed $value  Value to filter by
     * @param array $item   Columns to return (0=all, 1=single, 2=key-value, >2=selected)
     */
    public function fetch(
        Table $table,
        mixed $column = null,
        mixed $value = null,
        array $item = [],
        bool $all = false,
    ): mixed {
        if (!$this->exist($table, $column, $value) && !$all) {
            return ['ok' => false, 'reason' => 'doesnt_exist'];
        }

        $table = strtolower($table->name);
        $stmt = $this->fluent->query->from($table)->where($column, $value);
        $cnt = \count($item);

        if ($cnt === self::ZERO_ITEMS) {
            return $column ? $stmt->fetch() : $stmt->fetchAll();
        } elseif ($cnt === self::ONE_ITEM) {
            return $column ? $stmt->fetch(...$item) : array_keys($stmt->fetchAll(...$item));
        } elseif ($cnt === self::TWO_ITEMS) {
            return $stmt->fetchPairs(...$item);
        }

        return $column ? $stmt->fetchAll(...$item) : $stmt->fetchAll(...$item);
    }

    private function exist(Table $table, ?string $column = null, ?string $value = null): bool
    {
        $table = strtolower($table->name);
        $stmt = $this->fluent->query->from($table);

        if (isset($column)) {
            $stmt->where($column, $value);
        }

        return $stmt->fetch() !== false;
    }

    private function check(Table $table): string
    {
        return match ($table) {
            Table::PLAYERS, Table::RS_RANK, Table::PLAYERS_EXTRA => 'playerID',
            Table::CHALLENGES => 'Uid',
            Table::RECORDS => 'ChallengeId',
            Table::RS_KARMA => 'IDK',
            default => 'FUCKOFF'
        };
    }
}
