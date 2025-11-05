<?php

/**
 * The glossary repository class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-05
 */

declare(strict_types=1);

namespace phpMyFAQ\Glossary;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Strings;

readonly class GlossaryRepository implements GlossaryRepositoryInterface
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    public function fetchAll(string $language): array
    {
        $db = $this->configuration->getDb();
        $items = [];

        $query = sprintf(
            "SELECT id, lang, item, definition FROM %sfaqglossary WHERE lang = '%s' ORDER BY item ASC",
            Database::getTablePrefix(),
            $db->escape($language),
        );

        $result = $db->query($query);

        while ($row = $db->fetchObject($result)) {
            $items[] = [
                'id' => (int) $row->id,
                'language' => (string) $row->lang,
                'item' => stripslashes((string) $row->item),
                'definition' => stripslashes((string) $row->definition),
            ];
        }

        return $items;
    }

    public function fetch(int $id, string $language): array
    {
        $db = $this->configuration->getDb();

        $query = sprintf(
            "SELECT id, lang, item, definition FROM %sfaqglossary WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $id,
            $db->escape($language),
        );

        $result = $db->query($query);
        $item = [];

        while ($row = $db->fetchObject($result)) {
            $item = [
                'id' => (int) $row->id,
                'language' => (string) $row->lang,
                'item' => stripslashes((string) $row->item),
                'definition' => stripslashes((string) $row->definition),
            ];
        }

        return $item;
    }

    public function create(string $language, string $item, string $definition): bool
    {
        $db = $this->configuration->getDb();

        $escapedItem = $db->escape($item);
        $escapedDefinition = $db->escape($definition);
        $escapedLanguage = $db->escape($language);

        $query = sprintf(
            "INSERT INTO %sfaqglossary (id, lang, item, definition) VALUES (%d, '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $db->nextId(Database::getTablePrefix() . 'faqglossary', column: 'id'),
            $escapedLanguage,
            Strings::htmlspecialchars(substr($escapedItem, 0, 254)),
            Strings::htmlspecialchars($escapedDefinition),
        );

        return (bool) $db->query($query);
    }

    public function update(int $id, string $language, string $item, string $definition): bool
    {
        $db = $this->configuration->getDb();

        $escapedItem = $db->escape($item);
        $escapedDefinition = $db->escape($definition);
        $escapedLanguage = $db->escape($language);

        $query = sprintf(
            "UPDATE %sfaqglossary SET item = '%s', definition = '%s' WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            Strings::htmlspecialchars(substr($escapedItem, 0, 254)),
            Strings::htmlspecialchars($escapedDefinition),
            $id,
            $escapedLanguage,
        );

        return (bool) $db->query($query);
    }

    public function delete(int $id, string $language): bool
    {
        $db = $this->configuration->getDb();

        $query = sprintf(
            "DELETE FROM %sfaqglossary WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $id,
            $db->escape($language),
        );

        return (bool) $db->query($query);
    }
}
