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
 * @copyright 2022-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-10-08
 */

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Enums\BackupType;
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
    /**
     * Constructor.
     */
    public function __construct(private Configuration $configuration, private DatabaseHelper $databaseHelper)
    {
    }

    /**
     * @throws SodiumException
     */
    public function createBackup(string $backupType, string $backupFile): string
    {
        $backupDate = date('Y-m-d-H-i-s');

        $fileNamePrefix = (Database::getTablePrefix() !== '') ? Database::getTablePrefix() . '.phpmyfaq' : 'phpmyfaq';
        $fileName = sprintf('%s-%s.%s.sql', $fileNamePrefix, $backupType, $backupDate);

        $authKey = sodium_crypto_auth_keygen();
        $authCode = sodium_crypto_auth($backupFile, $authKey);

        $query = sprintf(
            "INSERT INTO %sfaqbackup (id, filename, authkey, authcode, created) VALUES (%d, '%s', '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqbackup', 'id'),
            $this->configuration->getDb()->escape($fileName),
            $this->configuration->getDb()->escape(sodium_bin2hex($authKey)),
            $this->configuration->getDb()->escape(sodium_bin2hex($authCode)),
            $backupDate
        );

        $this->configuration->getDb()->query($query);

        return $fileName;
    }

    /**
     * @throws SodiumException
     */
    public function verifyBackup(string $backup, string $backupFileName): bool
    {
        $query = sprintf(
            "SELECT id, filename, authkey, authcode, created FROM %sfaqbackup WHERE filename = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($backupFileName),
        );

        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) > 0) {
            $row = $this->configuration->getDb()->fetchObject($result);

            return sodium_crypto_auth_verify(
                sodium_hex2bin((string) $row->authcode),
                $backup,
                sodium_hex2bin((string) $row->authkey)
            );
        }

        return false;
    }

    public function generateBackupQueries(string $tableNames): string
    {
        $backup = implode("\r\n", $this->getBackupHeader($tableNames));

        foreach (explode(' ', $tableNames) as $table) {
            if ('' !== $table) {
                $backup .= implode(
                    "\r\n",
                    $this->databaseHelper->buildInsertQueries('SELECT * FROM ' . $table, $table)
                );
            }
        }

        return $backup;
    }

    public function getBackupTableNames(BackupType $type): string
    {
        $tables = $this->configuration->getDb()->getTableNames(Database::getTablePrefix());
        $tableNames = '';

        switch ($type) {
            case BackupType::BACKUP_TYPE_DATA:
                foreach ($tables as $table) {
                    if (
                        (Database::getTablePrefix() . 'faqadminlog' === trim((string) $table)) ||
                        (Database::getTablePrefix() . 'faqsessions' === trim((string) $table))
                    ) {
                        continue;
                    }
                    $tableNames .= $table . ' ';
                }
                break;
            case BackupType::BACKUP_TYPE_LOGS:
                foreach ($tables as $table) {
                    if (
                        (Database::getTablePrefix() . 'faqadminlog' === trim((string) $table)) ||
                        (Database::getTablePrefix() . 'faqsessions' === trim((string) $table))
                    ) {
                        $tableNames .= $table . ' ';
                    }
                }
                break;
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
            sprintf('-- pmf%s: %s', substr($this->configuration->getVersion(), 0, 3), $tableNames),
            '-- DO NOT REMOVE THE FIRST LINE!',
            '-- pmftableprefix: ' . Database::getTablePrefix(),
            '-- DO NOT REMOVE THE LINES ABOVE!',
            '-- Otherwise this backup will be broken.'
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
        if ($zipArchive->open($zipFile, ZipArchive::CREATE) !== true) {
            throw new Exception('Error while creating ZipArchive');
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(PMF_CONTENT_DIR)
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(PMF_CONTENT_DIR) + 1);

            if ($file->isDir()) {
                $zipArchive->addEmptyDir($relativePath);
            }

            $zipArchive->addFile($filePath, $relativePath);
        }

        $zipArchive->close();

        return $zipFile;
    }
}
