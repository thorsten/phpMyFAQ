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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

readonly class GlossaryRepository implements GlossaryRepositoryInterface
{
    /** Maximum number of characters the item column can hold. */
    private const int ITEM_MAX_LENGTH = 254;

    private LoggerInterface $logger;

    public function __construct(
        private Configuration $configuration,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function fetchAll(string $language): array
    {
        $db = $this->configuration->getDb();
        $items = [];

        $sql = "SELECT id, lang, item, definition FROM %sfaqglossary WHERE lang = '%s' ORDER BY item ASC";
        $query = sprintf($sql, Database::getTablePrefix(), $db->escape($language));

        $result = $db->query($query);
        if ($result === false) {
            $this->logger->error(message: 'Glossary fetchAll query failed', context: ['language' => $language]);
            return [];
        }
        $row = $db->fetchObject($result);
        while ($row) {
            $items[] = [
                'id' => (int) $row->id,
                'language' => (string) $row->lang,
                'item' => stripslashes((string) $row->item),
                'definition' => stripslashes((string) $row->definition),
            ];
            $row = $db->fetchObject($result);
        }

        return $items;
    }

    public function fetch(int $id, string $language): array
    {
        $db = $this->configuration->getDb();

        $sql = "SELECT id, lang, item, definition FROM %sfaqglossary WHERE id = %d AND lang = '%s'";
        $query = sprintf($sql, Database::getTablePrefix(), $id, $db->escape($language));

        $result = $db->query($query);
        if ($result === false) {
            $this->logger->warning(message: 'Glossary fetch failed', context: ['id' => $id, 'language' => $language]);
            return [];
        }
        $item = [];
        $row = $db->fetchObject($result);
        while ($row) {
            $item = [
                'id' => (int) $row->id,
                'language' => (string) $row->lang,
                'item' => stripslashes((string) $row->item),
                'definition' => stripslashes((string) $row->definition),
            ];
            $row = $db->fetchObject($result);
        }

        return $item;
    }

    public function create(string $language, string $item, string $definition): bool
    {
        $db = $this->configuration->getDb();

        $escapedItem = $db->escape($this->prepareItem($item));
        $escapedDefinition = $db->escape(Strings::htmlspecialchars($definition));
        $escapedLanguage = $db->escape($language);

        $id = $db->nextId(Database::getTablePrefix() . 'faqglossary', column: 'id');
        $sql = "INSERT INTO %sfaqglossary (id, lang, item, definition) VALUES (%d, '%s', '%s', '%s')";
        $query = sprintf($sql, Database::getTablePrefix(), $id, $escapedLanguage, $escapedItem, $escapedDefinition);

        $ok = (bool) $db->query($query);
        if (!$ok) {
            $this->logger->error(message: 'Glossary create failed', context: [
                'language' => $language,
                'item' => $item,
            ]);
        }
        return $ok;
    }

    public function update(int $id, string $language, string $item, string $definition): bool
    {
        $db = $this->configuration->getDb();

        $escapedItem = $db->escape($this->prepareItem($item));
        $escapedDefinition = $db->escape(Strings::htmlspecialchars($definition));
        $escapedLanguage = $db->escape($language);

        $sql = "UPDATE %sfaqglossary SET item = '%s', definition = '%s' WHERE id = %d AND lang = '%s'";
        $query = sprintf($sql, Database::getTablePrefix(), $escapedItem, $escapedDefinition, $id, $escapedLanguage);

        $ok = (bool) $db->query($query);
        if (!$ok) {
            $this->logger->error(message: 'Glossary update failed', context: ['id' => $id, 'language' => $language]);
        }
        return $ok;
    }

    /**
     * Encodes and length-limits an item before it is escaped for the query.
     *
     * Truncating must happen before escaping: cutting an escaped string can strip a quote while
     * keeping the escape character in front of it, which breaks out of the SQL string literal.
     */
    private function prepareItem(string $item): string
    {
        $encoded = Strings::htmlspecialchars(Strings::substr($item, 0, self::ITEM_MAX_LENGTH));

        if (Strings::strlen($encoded) > self::ITEM_MAX_LENGTH) {
            $encoded = Strings::substr($encoded, 0, self::ITEM_MAX_LENGTH);
            // Drop an entity the cut may have left half-written
            $encoded = (string) preg_replace('/&[#0-9a-zA-Z]*$/', replacement: '', subject: $encoded);
        }

        return $encoded;
    }

    public function delete(int $id, string $language): bool
    {
        $db = $this->configuration->getDb();

        $sql = "DELETE FROM %sfaqglossary WHERE id = %d AND lang = '%s'";
        $query = sprintf($sql, Database::getTablePrefix(), $id, $db->escape($language));

        $ok = (bool) $db->query($query);
        if (!$ok) {
            $this->logger->warning(message: 'Glossary delete failed', context: ['id' => $id, 'language' => $language]);
        }
        return $ok;
    }
}
