<?php

/**
 * Provides methods for phpMyFAQ backups
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-10-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use DateTimeImmutable;
use phpMyFAQ\Administration\Backup\BackupExecuteResult;
use phpMyFAQ\Administration\Backup\BackupExportResult;
use phpMyFAQ\Administration\Backup\BackupParseResult;
use phpMyFAQ\Administration\Backup\BackupRepository;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Enums\BackupType;
use phpMyFAQ\Strings;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SodiumException;
use ZipArchive;

/**
 * Class Backup
 *
 * @package phpMyFAQ
 */
readonly class Backup
{
    /** @var BackupRepository */
    private BackupRepository $repository;

    /**
     * Constructor.
     */
    public function __construct(
        private Configuration $configuration,
        private DatabaseHelper $databaseHelper,
    ) {
        $this->repository = new BackupRepository($this->configuration);
    }

    /**
     * Returns last backup date (formatted) and whether it's older than 30 days.
     *
     * @return array{lastBackupDate: ?string, isBackupOlderThan30Days: bool}
     */
    public function getLastBackupInfo(): array
    {
        $lastBackupDateFormatted = null;
        try {
            $backups = $this->getRepository()->getAll();
            $lastBackup = $backups[0] ?? null;

            if ($lastBackup !== null && isset($lastBackup->created)) {
                $createdRaw = (string) $lastBackup->created;
                $createdDate = DateTimeImmutable::createFromFormat(format: 'Y-m-d H:i:s', datetime: $createdRaw)
                ?: null;
                if ($createdDate !== null) {
                    $lastBackupDateFormatted = $createdDate->format(format: 'Y-m-d H:i:s');
                    $threshold = new DateTimeImmutable(datetime: '-30 days');
                    $isBackupOlderThan30Days = $createdDate < $threshold;
                } else {
                    $isBackupOlderThan30Days = true;
                }
            } else {
                $isBackupOlderThan30Days = true;
            }
        } catch (\Throwable) {
            $isBackupOlderThan30Days = true;
        }

        return [
            'lastBackupDate' => $lastBackupDateFormatted,
            'isBackupOlderThan30Days' => $isBackupOlderThan30Days,
        ];
    }

    /**
     * @throws SodiumException
     */
    public function createBackup(string $backupType, string $backupFile): string
    {
        $backupDate = date(format: 'Y-m-d-H-i-s');

        $fileNamePrefix = Database::getTablePrefix() !== '' ? Database::getTablePrefix() . '.phpmyfaq' : 'phpmyfaq';
        $fileName = sprintf('%s-%s.%s.sql', $fileNamePrefix, $backupType, $backupDate);

        $authKey = sodium_crypto_auth_keygen();
        $authCode = sodium_crypto_auth($backupFile, $authKey);

        // persist backup metadata via repository
        $this->getRepository()->add($fileName, sodium_bin2hex($authKey), sodium_bin2hex($authCode), $backupDate);

        return $fileName;
    }

    /**
     * @throws SodiumException
     */
    public function verifyBackup(string $backup, string $backupFileName): bool
    {
        $row = $this->getRepository()->findByFilename($backupFileName);
        if ($row !== null) {
            return sodium_crypto_auth_verify(
                sodium_hex2bin((string) $row->authcode),
                $backup,
                sodium_hex2bin((string) $row->authkey),
            );
        }

        return false;
    }

    public function generateBackupQueries(string $tableNames): string
    {
        $backup = implode(separator: "\r\n", array: $this->getBackupHeader($tableNames));

        foreach (explode(separator: ' ', string: $tableNames) as $tableName) {
            if ('' === $tableName) {
                continue;
            }

            $backup .= implode(separator: "\r\n", array: $this->databaseHelper->buildInsertQueries(
                'SELECT * FROM ' . $tableName,
                $tableName,
            ));
        }

        return $backup;
    }

    /**
     * @throws \Exception
     */ public function getBackupTableNames(BackupType $backupType): string
    {
        $tables = $this->configuration->getDb()->getTableNames(Database::getTablePrefix());
        $tableNames = '';

        switch ($backupType) {
            case BackupType::BACKUP_TYPE_DATA:
                foreach ($tables as $table) {
                    if (Database::getTablePrefix() . 'faqadminlog' === trim((string) $table)) {
                        continue;
                    }

                    if (Database::getTablePrefix() . 'faqsessions' === trim((string) $table)) {
                        continue;
                    }

                    $tableNames .= $table . ' ';
                }

                break;
            case BackupType::BACKUP_TYPE_LOGS:
                foreach ($tables as $table) {
                    if (
                        !(
                            Database::getTablePrefix() . 'faqadminlog' === trim((string) $table)
                            || Database::getTablePrefix() . 'faqsessions' === trim((string) $table)
                        )
                    ) {
                        continue;
                    }

                    $tableNames .= $table . ' ';
                }

                break;
            case BackupType::BACKUP_TYPE_CONTENT:
                throw new \Exception(message: 'To be implemented');
        }

        return $tableNames;
    }

    /**
     * Returns the backup file header
     * @return string[]
     */
    private function getBackupHeader(string $tableNames): array
    {
        return [
            sprintf(
                '-- pmf%s: %s',
                substr(string: $this->configuration->getVersion(), offset: 0, length: 3),
                $tableNames,
            ),
            '-- DO NOT REMOVE THE FIRST LINE!',
            '-- pmftableprefix: ' . Database::getTablePrefix(),
            '-- DO NOT REMOVE THE LINES ABOVE!',
            '-- Otherwise this backup will be broken.',
        ];
    }

    /**
     * Creates a ZipArchive of the content-folder
     *
     * @throws \Exception
     */
    public function createContentFolderBackup(): string
    {
        $zipFile = PMF_ROOT_DIR . DIRECTORY_SEPARATOR . 'content.zip';

        $zipArchive = new ZipArchive();
        if (!$zipArchive->open($zipFile, ZipArchive::CREATE)) {
            throw new Exception(message: 'Error while creating ZipArchive');
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(PMF_CONTENT_DIR),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();
            $relativePath = substr((string) $filePath, strlen(PMF_CONTENT_DIR) + 1);
            $zipArchive->addFile($filePath, $relativePath);
        }

        $zipArchive->close();

        return $zipFile;
    }

    /**
     * Creates a backup for the given type and returns filename + content.
     *
     * @throws SodiumException
     * @throws \Exception
     *
     */
    public function export(BackupType $type): BackupExportResult
    {
        $tableNames = $this->getBackupTableNames($type);

        $backupContent = $this->generateBackupQueries($tableNames);

        $fileName = $this->createBackup($type->value, $backupContent);

        return new BackupExportResult($fileName, $backupContent);
    }

    /**
     * Parses a backup file, checks the version and creates SQL queries + table prefix.
     *
     * @throws Exception
     */
    public function parseBackupFile(string $filePath, string $currentVersion): BackupParseResult
    {
        $handle = fopen($filePath, mode: 'r');
        if (false === $handle) {
            throw new Exception(message: sprintf('Cannot open backup file "%s".', $filePath));
        }

        $firstLine = fgets($handle, length: 65536);
        if (false === $firstLine) {
            fclose($handle);
            throw new Exception(message: 'Empty backup file.');
        }

        $versionFound = Strings::substr(string: $firstLine, start: 0, length: 9);

        $versionExpected = '-- pmf' . substr(string: $currentVersion, offset: 0, length: 3);

        // Tabellen aus der ersten Zeile extrahieren
        $tablesLine = trim(Strings::substr(string: $firstLine, start: 11));
        $tables = explode(separator: ' ', string: $tablesLine);

        $queries = [];
        foreach ($tables as $tableName) {
            if ('' === $tableName) {
                continue;
            }

            $queries[] = sprintf('DELETE FROM %s', $tableName);
        }

        $tablePrefix = '';
        $currentQuery = '';

        while ($line = fgets($handle, length: 65536)) {
            $trimmedLine = trim($line);
            $backupPrefixPattern = '-- pmftableprefix:';
            $backupPrefixPatternLength = Strings::strlen($backupPrefixPattern);

            if (
                Strings::substr(string: $trimmedLine, start: 0, length: $backupPrefixPatternLength)
                === $backupPrefixPattern
            ) {
                $tablePrefix = trim(Strings::substr($trimmedLine, $backupPrefixPatternLength));

                continue;
            }

            // Skip comment lines (-- or # at start of line)
            if (
                Strings::substr(string: $trimmedLine, start: 0, length: 2) === '--'
                || Strings::substr(string: $trimmedLine, start: 0, length: 1) === '#'
            ) {
                continue;
            }

            // Skip empty lines
            if ($trimmedLine === '') {
                continue;
            }

            // Accumulate lines to handle multi-line SQL statements
            $currentQuery .= $line;

            // Check if statement is complete (ends with ; and quotes are balanced)
            if ($this->isCompleteStatement($currentQuery)) {
                $queries[] = trim(rtrim(trim($currentQuery), characters: ';'));
                $currentQuery = '';
            }
        }

        // Handle any remaining incomplete query
        $remainingQuery = trim($currentQuery);
        if ($remainingQuery !== '' && $remainingQuery !== ';') {
            $queries[] = rtrim($remainingQuery, characters: ';');
        }

        fclose($handle);

        $versionMatches = $versionFound === $versionExpected;

        return new BackupParseResult(
            versionMatches: $versionMatches,
            versionFound: $versionFound,
            versionExpected: $versionExpected,
            queries: $queries,
            tablePrefix: $tablePrefix,
        );
    }

    /**
     * Executes the given backup queries with the correct table prefix.
     */
    public function executeBackupQueries(array $queries, string $tablePrefix): BackupExecuteResult
    {
        $db = $this->configuration->getDb();

        $ok = 0;
        $failed = 0;
        $lastErrorQuery = null;
        $lastErrorReason = null;

        foreach ($queries as $query) {
            $alignedQuery = $this->databaseHelper::alignTablePrefix($query, $tablePrefix, Database::getTablePrefix());

            $result = $db->query($alignedQuery);
            if (!$result) {
                ++$failed;
                $lastErrorQuery = $alignedQuery;
                $lastErrorReason = $db->error();

                continue;
            }

            ++$ok;
        }

        return new BackupExecuteResult(
            queriesOk: $ok,
            queriesFailed: $failed,
            lastErrorQuery: $lastErrorQuery,
            lastErrorReason: $lastErrorReason,
        );
    }

    private function getRepository(): BackupRepository
    {
        return $this->repository;
    }

    /**
     * Checks if a SQL statement is complete (ends with semicolon outside of string literals).
     */
    private function isCompleteStatement(string $query): bool
    {
        $trimmed = rtrim($query);
        if (!str_ends_with($trimmed, ';')) {
            return false;
        }

        // Count unescaped single quotes to determine if we're inside a string literal
        // We need to account for escaped quotes: \' and '' (SQL escape)
        $inString = false;
        $length = strlen($trimmed);

        for ($i = 0; $i < $length; $i++) {
            $char = $trimmed[$i];

            if ($char !== "'") {
                continue;
            }

            if (!$inString) {
                $inString = true;
                continue;
            }

            // Inside a string - check for escaped quotes
            // Double single quote escape ('')
            if (($i + 1) < $length && $trimmed[$i + 1] === "'") {
                $i++;
                continue;
            }

            // Backslash escape (\')
            if ($i > 0 && $trimmed[$i - 1] === '\\') {
                continue;
            }

            // End of string literal
            $inString = false;
        }

        // Statement is complete if we're not inside a string literal
        return !$inString;
    }
}
