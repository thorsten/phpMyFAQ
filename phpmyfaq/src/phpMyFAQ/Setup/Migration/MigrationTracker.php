<?php

/**
 * Tracks applied migrations in the database.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

class MigrationTracker
{
    private const TABLE_NAME = 'faqmigrations';

    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    /**
     * Creates the migrations tracking table if it doesn't exist.
     */
    public function ensureTableExists(): void
    {
        $tableName = Database::getTablePrefix() . self::TABLE_NAME;
        $dbType = Database::getType();

        $createTableSql = match ($dbType) {
            'mysqli', 'pdo_mysql' => "CREATE TABLE IF NOT EXISTS {$tableName} (
                id INT NOT NULL AUTO_INCREMENT,
                version VARCHAR(50) NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                execution_time_ms INT DEFAULT NULL,
                checksum VARCHAR(64) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY idx_version (version)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
            'pgsql', 'pdo_pgsql' => "CREATE TABLE IF NOT EXISTS {$tableName} (
                id SERIAL NOT NULL,
                version VARCHAR(50) NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                execution_time_ms INTEGER DEFAULT NULL,
                checksum VARCHAR(64) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE (version)
            )",
            'sqlite3', 'pdo_sqlite' => "CREATE TABLE IF NOT EXISTS {$tableName} (
                id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                version VARCHAR(50) NOT NULL UNIQUE,
                applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                execution_time_ms INTEGER DEFAULT NULL,
                checksum VARCHAR(64) DEFAULT NULL,
                description TEXT DEFAULT NULL
            )",
            'sqlsrv', 'pdo_sqlsrv' => "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='{$tableName}' AND xtype='U')
                CREATE TABLE {$tableName} (
                    id INT IDENTITY(1,1) NOT NULL,
                    version VARCHAR(50) NOT NULL UNIQUE,
                    applied_at DATETIME NOT NULL DEFAULT GETDATE(),
                    execution_time_ms INT DEFAULT NULL,
                    checksum VARCHAR(64) DEFAULT NULL,
                    description NVARCHAR(MAX) DEFAULT NULL,
                    PRIMARY KEY (id)
                )",
            default => throw new \RuntimeException("Unsupported database type: {$dbType}"),
        };

        $this->configuration->getDb()->query($createTableSql);
    }

    /**
     * Checks if a migration has been applied.
     */
    public function isApplied(string $version): bool
    {
        $tableName = Database::getTablePrefix() . self::TABLE_NAME;
        $query = sprintf(
            "SELECT COUNT(*) as cnt FROM %s WHERE version = '%s'",
            $tableName,
            $this->configuration->getDb()->escape($version),
        );

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);

        return (int) $row->cnt > 0;
    }

    /**
     * Records a migration as applied.
     */
    public function recordMigration(
        string $version,
        int $executionTimeMs = 0,
        ?string $checksum = null,
        ?string $description = null,
    ): void {
        $tableName = Database::getTablePrefix() . self::TABLE_NAME;
        $db = $this->configuration->getDb();

        $query = sprintf(
            "INSERT INTO %s (version, execution_time_ms, checksum, description) VALUES ('%s', %d, %s, %s)",
            $tableName,
            $db->escape($version),
            $executionTimeMs,
            $checksum !== null ? "'" . $db->escape($checksum) . "'" : 'NULL',
            $description !== null ? "'" . $db->escape($description) . "'" : 'NULL',
        );

        $db->query($query);
    }

    /**
     * Removes a migration record (for rollback).
     */
    public function removeMigration(string $version): void
    {
        $tableName = Database::getTablePrefix() . self::TABLE_NAME;
        $query = sprintf(
            "DELETE FROM %s WHERE version = '%s'",
            $tableName,
            $this->configuration->getDb()->escape($version),
        );

        $this->configuration->getDb()->query($query);
    }

    /**
     * Returns all applied migrations.
     *
     * @return array<int, array{version: string, applied_at: string, execution_time_ms: int, checksum: string|null, description: string|null}>
     */
    public function getAppliedMigrations(): array
    {
        $tableName = Database::getTablePrefix() . self::TABLE_NAME;
        $query = sprintf(
            'SELECT version, applied_at, execution_time_ms, checksum, description FROM %s ORDER BY id ASC',
            $tableName,
        );

        $result = $this->configuration->getDb()->query($query);
        $migrations = [];

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $migrations[] = [
                'version' => $row->version,
                'applied_at' => $row->applied_at,
                'execution_time_ms' => (int) $row->execution_time_ms,
                'checksum' => $row->checksum,
                'description' => $row->description,
            ];
        }

        return $migrations;
    }

    /**
     * Returns the list of applied versions.
     *
     * @return string[]
     */
    public function getAppliedVersions(): array
    {
        return array_column($this->getAppliedMigrations(), 'version');
    }

    /**
     * Returns the last applied migration version.
     */
    public function getLastAppliedVersion(): ?string
    {
        $tableName = Database::getTablePrefix() . self::TABLE_NAME;
        $dbType = Database::getType();

        // Build database-specific query
        $query = match ($dbType) {
            'sqlsrv', 'pdo_sqlsrv' => sprintf('SELECT TOP 1 version FROM %s ORDER BY id DESC', $tableName),
            default => sprintf('SELECT version FROM %s ORDER BY id DESC LIMIT 1', $tableName),
        };

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);

        return $row?->version;
    }

    /**
     * Checks if the tracking table exists.
     */
    public function tableExists(): bool
    {
        $tableName = Database::getTablePrefix() . self::TABLE_NAME;
        $dbType = Database::getType();

        $query = match ($dbType) {
            'mysqli', 'pdo_mysql' => "SHOW TABLES LIKE '{$tableName}'",
            'pgsql', 'pdo_pgsql' => "SELECT tablename FROM pg_catalog.pg_tables WHERE tablename = '{$tableName}'",
            'sqlite3', 'pdo_sqlite' => "SELECT name FROM sqlite_master WHERE type='table' AND name='{$tableName}'",
            'sqlsrv', 'pdo_sqlsrv' => "SELECT * FROM sysobjects WHERE name='{$tableName}' AND xtype='U'",
            default => throw new \RuntimeException("Unsupported database type: {$dbType}"),
        };

        $result = $this->configuration->getDb()->query($query);
        return $this->configuration->getDb()->numRows($result) > 0;
    }
}
