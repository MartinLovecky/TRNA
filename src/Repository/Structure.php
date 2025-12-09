<?php

declare(strict_types=1);

namespace Yuha\Trna\Repository;

use Yuha\Trna\Core\Enums\Table;

/**
 * Used to validate DB structure
 */
final class Structure
{
    public const NOT_NULL = 'NO';
    public array $requiredStructure = [
        Table::CHALLENGES->name => [
            [
                "Field" => "Id",
                "Type" => "int unsigned",
                "Null" => "NO",
                "Key" => "PRI",
                "Default" => null,
                "Extra" => "auto_increment",
            ],
            [
                "Field" => "Uid",
                "Type" => "varchar(27)",
                "Null" => "NO",
                "Key" => "UNI",
                "Default" => "",
                "Extra" => "",
            ],
            [
                "Field" => "Name",
                "Type" => "char(100)",
                "Null" => "NO",
                "Key" => "",
                "Default" => "",
                "Extra" => "",
            ],
            [
                "Field" => "Author",
                "Type" => "varchar(30)",
                "Null" => "NO",
                "Key" => "",
                "Default" => "",
                "Extra" => "",
            ],
            [
                "Field" => "Environment",
                "Type" => "varchar(10)",
                "Null" => "NO",
                "Key" => "",
                "Default" => "",
                "Extra" => "",
            ],
        ],
        Table::PLAYERS->name => [
            [
                "Field" => "Id",
                "Type" => "int unsigned",
                "Null" => "NO",
                "Key" => "PRI",
                "Default" => null,
                "Extra" => "auto_increment",
            ],
            [
                "Field" => "Login",
                "Type" => "varchar(50)",
                "Null" => "NO",
                "Key" => "UNI",
                "Default" => "",
                "Extra" => "",
            ],
            [
                "Field" => "Game",
                "Type" => "char(3)",
                "Null" => "NO",
                "Key" => "MUL",
                "Default" => "",
                "Extra" => "",
            ],
            [
                "Field" => "NickName",
                "Type" => "varchar(100)",
                "Null" => "NO",
                "Key" => "",
                "Default" => "",
                "Extra" => "",
            ],
            [
                "Field" => "playerID",
                "Type" => "varchar(255)",
                "Null" => "NO",
                "Key" => "",
                "Default" => "",
                "Extra" => "",
            ],
            [
                "Field" => "Nation",
                "Type" => "char(3)",
                "Null" => "NO",
                "Key" => "",
                "Default" => "",
                "Extra" => "",
            ],
            [
                "Field" => "UpdatedAt",
                "Type" => "datetime",
                "Null" => "NO",
                "Key" => "",
                "Default" => "CURRENT_TIMESTAMP",
                "Extra" => "ON UPDATE CURRENT_TIMESTAMP",
            ],
            [
                "Field" => "Wins",
                "Type" => "mediumint",
                "Null" => "NO",
                "Key" => "",
                "Default" => "0",
                "Extra" => "",
            ],
            [
                "Field" => "TimePlayed",
                "Type" => "int unsigned",
                "Null" => "NO",
                "Key" => "",
                "Default" => "0",
                "Extra" => "",
            ],
            [
                "Field" => "TeamName",
                "Type" => "char(60)",
                "Null" => "NO",
                "Key" => "",
                "Default" => "",
                "Extra" => "",
            ],
            [
                "Field" => "LastSeen",
                "Type" => "datetime",
                "Null" => "NO",
                "Key" => "",
                "Default" => "CURRENT_TIMESTAMP",
                "Extra" => "",
            ],
        ],
        Table::RECORDS->name => [
            [
                "Field" => "Id",
                "Type" => "int unsigned",
                "Null" => "NO",
                "Key" => "PRI",
                "Default" => null,
                "Extra" => "auto_increment",
            ],
            [
                "Field" => "ChallengeId",
                "Type" => "varchar(255)",
                "Null" => "NO",
                "Key" => "",
                "Default" => "",
                "Extra" => "",
            ],
            [
                "Field" => "Times",
                "Type" => "longtext",
                "Null" => "NO",
                "Key" => "",
                "Default" => null,
                "Extra" => "",
            ],
            [
                "Field" => "Date",
                "Type" => "datetime",
                "Null" => "NO",
                "Key" => "",
                "Default" => "CURRENT_TIMESTAMP",
                "Extra" => "ON UPDATE CURRENT_TIMESTAMP",
            ],
            [
                "Field" => "Checkpoints",
                "Type" => "text",
                "Null" => "YES",
                "Key" => "",
                "Default" => null,
                "Extra" => "",
            ],
        ],
        Table::PLAYERS_EXTRA->name => [
            [
                "Field" => "Id",
                "Type" => "int unsigned",
                "Null" => "NO",
                "Key" => "PRI",
                "Default" => null,
                "Extra" => "auto_increment",
            ],
            [
                "Field" => "cps",
                "Type" => "smallint",
                "Null" => "NO",
                "Key" => "",
                "Default" => "-1",
                "Extra" => "",
            ],
            [
                "Field" => "dedicps",
                "Type" => "smallint",
                "Null" => "NO",
                "Key" => "",
                "Default" => "-1",
                "Extra" => "",
            ],
            [
                "Field" => "donations",
                "Type" => "mediumint unsigned",
                "Null" => "NO",
                "Key" => "MUL",
                "Default" => "0",
                "Extra" => "",
            ],
            [
                "Field" => "style",
                "Type" => "varchar(20)",
                "Null" => "NO",
                "Key" => "",
                "Default" => null,
                "Extra" => "",
            ],
            [
                "Field" => "panels",
                "Type" => "varchar(255)",
                "Null" => "NO",
                "Key" => "",
                "Default" => null,
                "Extra" => "",
            ],
            [
                "Field" => "playerID",
                "Type" => "varchar(255)",
                "Null" => "NO",
                "Key" => "",
                "Default" => null,
                "Extra" => "",
            ],
        ],
        Table::RS_KARMA->name => [
            [
                "Field" => "Id",
                "Type" => "int unsigned",
                "Null" => "NO",
                "Key" => "PRI",
                "Default" => null,
                "Extra" => "auto_increment",
            ],
            [
                "Field" => "ChallengeId",
                "Type" => "varchar(255)",
                "Null" => "NO",
                "Key" => "MUL",
                "Default" => "",
                "Extra" => "",
            ],
            [
                "Field" => "playerID",
                "Type" => "varchar(255)",
                "Null" => "NO",
                "Key" => "MUL",
                "Default" => "",
                "Extra" => "",
            ],
            [
                "Field" => "Vote",
                "Type" => "enum('Plus','PlusPlus','PlusPlusPlus','Minus','MinusMinus','MinusMinusMinus')",
                "Null" => "NO",
                "Key" => "",
                "Default" => "Plus",
                "Extra" => "",
            ],
            [
                "Field" => "Score",
                "Type" => "tinyint(1)",
                "Null" => "NO",
                "Key" => "",
                "Default" => "0",
                "Extra" => "",
            ],
        ],
        Table::RS_RANK->name => [
            [
                "Field" => "Id",
                "Type" => "int unsigned",
                "Null" => "NO",
                "Key" => "PRI",
                "Default" => null,
                "Extra" => "auto_increment",
            ],
            [
                "Field" => "playerID",
                "Type" => "varchar(255)",
                "Null" => "NO",
                "Key" => "UNI",
                "Default" => "",
                "Extra" => "",
            ],
            [
                "Field" => "avg_score",
                "Type" => "decimal(5,3)",
                "Null" => "NO",
                "Key" => "",
                "Default" => "0.000",
                "Extra" => "",
            ],
        ],
    ];
    public array $requiredIndexes = [
        Table::CHALLENGES->name => [
            ['columns' => ['Uid'], 'unique' => true],
        ],
        Table::PLAYERS->name => [
            ['columns' => ['Login'], 'unique' => true],
            ['columns' => ['Game'], 'unique' => false],
        ],
        Table::RECORDS->name => [
            ['columns' => ['ChallengeId'], 'unique' => true],
        ],
        Table::PLAYERS_EXTRA->name => [
            ['columns' => ['donations'], 'unique' => false],
        ],
        Table::RS_KARMA->name => [
            ['columns' => ['playerID', 'ChallengeId'], 'unique' => true],
        ],
        Table::RS_RANK->name => [
            ['columns' => ['playerID'], 'unique' => true],
        ],
    ];

    public function __construct(private Fluent $fluent)
    {
    }

    public function validate(Table $table): void
    {
        $actualStructure = $this->describeTable($table);
        $actualIndexes = $this->describeIndexes($table);
        $expectedStructure = $this->requiredStructure[$table->name] ?? [];

        if (empty($expectedStructure)) {
            return;
        }

        $differences = $this->getStructureDifferences($actualStructure, $expectedStructure);
        foreach ($differences as $type => $fields) {
            foreach ($fields as $field) {
                match ($type) {
                    'missing' => $this->addFieldToTable($table->name, $field),
                    'extra' => $this->dropFieldFromTable($table->name, $field),
                    'mismatch' => $this->modifyFieldInTable($table->name, $field, $expectedStructure),
                };
            }
        }

        foreach ($this->requiredIndexes[$table->name] ?? [] as $index) {
            $this->ensureIndex($table, $index, $actualIndexes);
        }
    }

    protected function describeTable(Table $table): array
    {
        $table = strtolower($table->name);
        $query = $this->fluent->pdo->query("DESCRIBE `{$table}`");
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function describeIndexes(Table $table): array
    {
        $table = strtolower($table->name);
        $stmt = $this->fluent->pdo->query("SHOW INDEX FROM `$table`");
        $indexes = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $indexes[$row['Key_name']]['columns'][] = $row['Column_name'];
            $indexes[$row['Key_name']]['unique'] = !$row['Non_unique'];
        }
        return $indexes;
    }

    protected function getStructureDifferences(array $actual, array $expected): array
    {
        $actualFields = array_column($actual, null, 'Field');
        $expectedFields = array_column($expected, null, 'Field');
        $missing = array_diff_key($expectedFields, $actualFields);
        $extra = array_diff_key($actualFields, $expectedFields);

        $mismatches = [];
        foreach ($expectedFields as $field => $expectedDef) {
            if (isset($actualFields[$field])) {
                foreach ($expectedDef as $key => $value) {
                    if (($actualFields[$field][$key] ?? null) !== $value) {
                        $mismatches[] = $field;
                        break;
                    }
                }
            }
        }

        return [
            'missing' => array_keys($missing),
            'extra' => array_keys($extra),
            'mismatch' => $mismatches,
        ];
    }

    protected function addFieldToTable(string $table, string $fieldName): void
    {
        $definition = $this->findFieldDefinition($table, $fieldName);
        if (!$definition) {
            throw new \InvalidArgumentException("Field $fieldName not defined for table $table");
        }

        $default = $this->buildDefaultClause($definition['Default']);
        $null = $definition['Null'] === self::NOT_NULL ? 'NOT NULL' : 'NULL';
        $extra = $this->buildExtraClause($definition['Extra']);
        $key = ($definition['Key'] === 'PRI') ? ' PRIMARY KEY' : ''; // Add PRIMARY KEY if needed
        $table = strtolower($table);
        $sql = \sprintf(
            "ALTER TABLE `%s` ADD COLUMN `%s` %s %s%s%s",
            $table,
            $definition['Field'],
            $definition['Type'],
            $null,
            $default,
            $extra,
            $key,
        );

        $this->exe($sql);
    }

    protected function findFieldDefinition(string $table, string $field): ?array
    {
        foreach ($this->requiredStructure[$table] ?? [] as $def) {
            if ($def['Field'] === $field) {
                return $def;
            }
        }
        return null;
    }

    protected function buildDefaultClause($default): string
    {
        if ($default === null) {
            return '';
        }

        // For CURRENT_TIMESTAMP and functions, return directly without quotes
        if (\is_string($default)) {
            $upper = strtoupper($default);
            if (\in_array($upper, ['CURRENT_TIMESTAMP'], true)) {
                return " DEFAULT $upper";
            }
            // Otherwise quote string defaults
            return " DEFAULT '" . addslashes($default) . "'";
        }

        // For numeric defaults
        if (is_numeric($default)) {
            return " DEFAULT $default";
        }

        return '';
    }

    protected function buildExtraClause(string $extra): string
    {
        $clauses = [];
        if (stripos($extra, 'auto_increment') !== false) {
            $clauses[] = "AUTO_INCREMENT";
        }
        if (stripos($extra, 'ON UPDATE CURRENT_TIMESTAMP') !== false) {
            $clauses[] = "ON UPDATE CURRENT_TIMESTAMP";
        }
        return $clauses ? " " . implode(" ", $clauses) : '';
    }

    protected function dropFieldFromTable(string $table, string $field): void
    {
        $sql = \sprintf(
            "ALTER TABLE `%s` DROP COLUMN `%s`",
            $table,
            $field,
        );
        $this->exe($sql);
    }

    protected function modifyFieldInTable(string $table, string $field): void
    {
        $definition = $this->findFieldDefinition($table, $field);
        if (!$definition) {
            throw new \InvalidArgumentException("Expected definition for $field not found.");
        }

        $default = $this->buildDefaultClause($definition['Default']);
        $null = $definition['Null'] === self::NOT_NULL ? 'NOT NULL' : 'NULL';
        $extra = $this->buildExtraClause($definition['Extra']);
        $key = ($definition['Key'] === 'PRI') ? ' PRIMARY KEY' : '';
        $table = strtolower($table);
        $sql = \sprintf(
            "ALTER TABLE `%s` MODIFY COLUMN `%s` %s %s%s%s",
            $table,
            $definition['Field'],
            $definition['Type'],
            $null,
            $default,
            $extra,
            $key,
        );

        $this->exe($sql);
    }

    protected function ensureIndex(
        Table $table,
        array $index,
        array $existingIndexes
    ): void {
        $columns = $index['columns'];
        sort($columns);
        $found = false;
        foreach ($existingIndexes as $keyName => $idx) {
            $existingCols = $idx['columns'];
            sort($existingCols);
            if ($columns === $existingCols) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $unique = $index['unique'] ? 'UNIQUE' : '';
            $idxName = 'idx_' . implode('_', $columns);
            $cols = implode('`,`', $columns);
            $table = strtolower($table->name);
            $sql = \sprintf(
                "ALTER TABLE `%s` ADD %s INDEX `%s` (`%s`)",
                $table,
                $unique,
                $idxName,
                $cols,
            );
            $this->exe($sql);
        }
    }

    protected function exe(string $sql): void
    {
        try {
            $this->fluent->pdo->exec($sql);
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
