<?php

/**
 * Repository for user- and group-level language permission restrictions.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-08-10
 */

declare(strict_types=1);

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Language;

readonly class LanguagePermissionRepository
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Returns the language codes that a user's direct right is restricted to.
     * An empty array means the right is unrestricted (applies to all languages).
     *
     * @return array<string>
     */
    public function getUserLanguageRestrictions(int $userId, int $rightId): array
    {
        if ($userId <= 0 || $rightId <= 0) {
            return [];
        }

        $select = sprintf(
            'SELECT language FROM %sfaquser_right_language WHERE user_id = %d AND right_id = %d',
            Database::getTablePrefix(),
            $userId,
            $rightId,
        );

        return $this->fetchLanguageColumn($select);
    }

    /**
     * Returns all language restrictions for a user, keyed by right ID.
     *
     * @return array<int, array<string>>
     */
    public function getAllUserLanguageRestrictions(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $select = sprintf(
            'SELECT right_id, language FROM %sfaquser_right_language WHERE user_id = %d ORDER BY right_id',
            Database::getTablePrefix(),
            $userId,
        );

        $res = $this->configuration->getDb()->query($select);
        if (!$res) {
            return [];
        }

        $result = [];
        while (true) {
            $row = $this->configuration->getDb()->fetchArray($res);
            if ($row === false || $row === null || $row === []) {
                break;
            }

            $rightId = (int) $row['right_id'];
            $result[$rightId][] = (string) $row['language'];
        }

        return $result;
    }

    /**
     * Sets the language restrictions for a user's direct right.
     * Replaces any existing restrictions for this user-right pair.
     *
     * @param array<string> $languages Language codes to restrict to (empty = unrestricted)
     */
    public function setUserLanguageRestrictions(int $userId, int $rightId, array $languages): bool
    {
        if ($userId <= 0 || $rightId <= 0) {
            return false;
        }

        return $this->replaceLanguageRows(
            'faquser_right_language',
            'user_id, right_id, language',
            sprintf('user_id = %d AND right_id = %d', $userId, $rightId),
            static fn(string $language): string => sprintf('(%d, %d, %s)', $userId, $rightId, $language),
            $languages,
        );
    }

    /**
     * Deletes all language restrictions for a specific user-right pair.
     */
    public function deleteUserLanguageRestrictions(int $userId, int $rightId): bool
    {
        if ($userId <= 0 || $rightId <= 0) {
            return false;
        }

        $delete = sprintf(
            'DELETE FROM %sfaquser_right_language WHERE user_id = %d AND right_id = %d',
            Database::getTablePrefix(),
            $userId,
            $rightId,
        );

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Deletes all language restrictions for a user.
     */
    public function deleteAllForUser(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $delete = sprintf(
            'DELETE FROM %sfaquser_right_language WHERE user_id = %d',
            Database::getTablePrefix(),
            $userId,
        );

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Returns the language codes that a group's right is restricted to.
     * An empty array means the right is unrestricted (applies to all languages).
     *
     * @return array<string>
     */
    public function getLanguageRestrictions(int $groupId, int $rightId): array
    {
        if ($groupId <= 0 || $rightId <= 0) {
            return [];
        }

        $select = sprintf(
            'SELECT language FROM %sfaqgroup_right_language WHERE group_id = %d AND right_id = %d',
            Database::getTablePrefix(),
            $groupId,
            $rightId,
        );

        return $this->fetchLanguageColumn($select);
    }

    /**
     * Returns all language restrictions for a group, keyed by right ID.
     *
     * @return array<int, array<string>>
     */
    public function getAllLanguageRestrictions(int $groupId): array
    {
        if ($groupId <= 0) {
            return [];
        }

        $select = sprintf(
            'SELECT right_id, language FROM %sfaqgroup_right_language WHERE group_id = %d ORDER BY right_id',
            Database::getTablePrefix(),
            $groupId,
        );

        $res = $this->configuration->getDb()->query($select);
        if (!$res) {
            return [];
        }

        $result = [];
        while (true) {
            $row = $this->configuration->getDb()->fetchArray($res);
            if ($row === false || $row === null || $row === []) {
                break;
            }

            $rightId = (int) $row['right_id'];
            $result[$rightId][] = (string) $row['language'];
        }

        return $result;
    }

    /**
     * Sets the language restrictions for a group's right.
     * Replaces any existing restrictions for this group-right pair.
     *
     * @param array<string> $languages Language codes to restrict to (empty = unrestricted)
     */
    public function setLanguageRestrictions(int $groupId, int $rightId, array $languages): bool
    {
        if ($groupId <= 0 || $rightId <= 0) {
            return false;
        }

        return $this->replaceLanguageRows(
            'faqgroup_right_language',
            'group_id, right_id, language',
            sprintf('group_id = %d AND right_id = %d', $groupId, $rightId),
            static fn(string $language): string => sprintf('(%d, %d, %s)', $groupId, $rightId, $language),
            $languages,
        );
    }

    /**
     * Deletes all language restrictions for a specific group-right pair.
     */
    public function deleteLanguageRestrictions(int $groupId, int $rightId): bool
    {
        if ($groupId <= 0 || $rightId <= 0) {
            return false;
        }

        $delete = sprintf(
            'DELETE FROM %sfaqgroup_right_language WHERE group_id = %d AND right_id = %d',
            Database::getTablePrefix(),
            $groupId,
            $rightId,
        );

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Deletes all language restrictions for a group.
     */
    public function deleteAllForGroup(int $groupId): bool
    {
        if ($groupId <= 0) {
            return false;
        }

        $delete = sprintf(
            'DELETE FROM %sfaqgroup_right_language WHERE group_id = %d',
            Database::getTablePrefix(),
            $groupId,
        );

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * @return array<string>
     */
    private function fetchLanguageColumn(string $select): array
    {
        $res = $this->configuration->getDb()->query($select);
        if (!$res) {
            return [];
        }

        $result = [];
        while (true) {
            $row = $this->configuration->getDb()->fetchArray($res);
            if ($row === false || $row === null || $row === []) {
                break;
            }

            $result[] = (string) $row['language'];
        }

        return $result;
    }

    /**
     * Deletes all rows matching $whereClause on $table, then re-inserts one row per
     * supported language code, built via $rowBuilder. Runs inside a transaction so
     * the replace is atomic.
     *
     * Because an empty restriction set means "unrestricted", the language list is
     * validated up front and the whole write is refused when the caller supplied
     * codes but none of them are supported. Silently dropping them would delete the
     * existing rows and widen the right to every language — a fail-open outcome for
     * a permission control.
     *
     * @param callable(string): string $rowBuilder
     * @param array<string> $languages
     */
    private function replaceLanguageRows(
        string $table,
        string $columns,
        string $whereClause,
        callable $rowBuilder,
        array $languages,
    ): bool {
        $supportedLanguages = array_values(array_filter($languages, Language::isASupportedLanguage(...)));

        // Only an explicitly empty input may clear the restrictions.
        if ($languages !== [] && $supportedLanguages === []) {
            return false;
        }

        $db = $this->configuration->getDb();

        $db->query($this->beginTransactionStatement());

        $delete = sprintf('DELETE FROM %s%s WHERE %s', Database::getTablePrefix(), $table, $whereClause);
        if (!$db->query($delete)) {
            $db->query('ROLLBACK');
            return false;
        }

        foreach ($supportedLanguages as $language) {
            $escapedLanguage = sprintf("'%s'", $db->escape($language));

            $insert = sprintf(
                'INSERT INTO %s%s (%s) VALUES %s',
                Database::getTablePrefix(),
                $table,
                $columns,
                $rowBuilder($escapedLanguage),
            );

            if (!$db->query($insert)) {
                $db->query('ROLLBACK');
                return false;
            }
        }

        $db->query('COMMIT');

        return true;
    }

    /**
     * Returns the statement that opens a transaction on the current driver.
     * SQL Server treats a bare `BEGIN` as a statement block, not a transaction,
     * so the DELETE/INSERT pair would run unscoped there.
     */
    private function beginTransactionStatement(): string
    {
        return match (Database::getType()) {
            'sqlsrv', 'pdo_sqlsrv' => 'BEGIN TRANSACTION',
            default => 'BEGIN',
        };
    }
}
