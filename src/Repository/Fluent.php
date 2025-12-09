<?php

declare(strict_types=1);

namespace Yuha\Trna\Repository;

use Envms\FluentPDO\Query;
use PDO;
use Yuha\Trna\Core\Enums\Table;
use Yuha\Trna\Core\Server;
use Yuha\Trna\Service\Aseco;

final class Fluent
{
    public PDO $pdo;
    public Query $query;

    public function __construct()
    {
        $dsn = \sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s;sslmode=verify-ca;sslrootcert=ca.pem',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_DATABASE'],
            $_ENV['DB_CHARSET'],
        );

        try {
            $this->pdo = new PDO($dsn, $_ENV['DB_LOGIN'], $_ENV['DB_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
            $this->query = new Query($this->pdo);
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    public function executeFile(Table $table): void
    {
        $table = strtolower($table->name);
        $filePath = Server::$sqlDir . $table . '.sql';

        $sql = Aseco::safeFileGetContents($filePath);
        if (!\is_string($sql) || empty($sql)) {
            throw new \RuntimeException("Failed to read the contents of the file at path '{$filePath}'.");
        }

        try {
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    public function dropTable(Table $table): void
    {
        $table = strtolower($table->name);

        try {
            $this->pdo->exec("DROP TABLE IF EXISTS {$table}");
        } catch (\PDOException $e) {
            throw $e;
        }
    }
}
