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
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-10-08
 */

namespace phpMyFAQ;

use phpMyFAQ\Database\DatabaseHelper;
use SodiumException;

/**
 * Class Backup
 *
 * @package phpMyFAQ
 */
class Backup
{
    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config, private readonly DatabaseHelper $databaseHelper)
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
            $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqbackup', 'id'),
            $this->config->getDb()->escape($fileName),
            $this->config->getDb()->escape(sodium_bin2hex($authKey)),
            $this->config->getDb()->escape(sodium_bin2hex($authCode)),
            $backupDate
        );

        $this->config->getDb()->query($query);

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
            $this->config->getDb()->escape($backupFileName),
        );

        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) > 0) {
            $row = $this->config->getDb()->fetchObject($result);

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

    /**
     * Returns the backup file header
     * @return string[]
     */
    private function getBackupHeader(string $tableNames): array
    {
        return [
            sprintf('-- pmf%s: %s', substr($this->config->getVersion(), 0, 3), $tableNames),
            '-- DO NOT REMOVE THE FIRST LINE!',
            '-- pmftableprefix: ' . Database::getTablePrefix(),
            '-- DO NOT REMOVE THE LINES ABOVE!',
            '-- Otherwise this backup will be broken.'
        ];
    }
}
