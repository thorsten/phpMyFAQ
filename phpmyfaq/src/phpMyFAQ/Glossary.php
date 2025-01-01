<?php

/**
 * The main glossary class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-15
 */

namespace phpMyFAQ;

/**
 * Class Glossary
 *
 * @package phpMyFAQ
 */
class Glossary
{
    private string $definition = '';

    private string $language;

    private array $cachedItems = [];

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $configuration)
    {
    }

    /**
     * Fill the passed string with the current Glossary items.
     *
     * @param string $content Content
     */
    public function insertItemsIntoContent(string $content = ''): string
    {
        if ('' == $content) {
            return '';
        }

        $attributes = [
            'href',
            'src',
            'title',
            'alt',
            'class',
            'style',
            'id',
            'name',
            'face',
            'size',
            'dir',
            'rel',
            'rev',
            'onmouseenter',
            'onmouseleave',
            'onafterprint',
            'onbeforeprint',
            'onbeforeunload',
            'onhashchange',
            'onmessage',
            'onoffline',
            'ononline',
            'onpopstate',
            'onpagehide',
            'onpageshow',
            'onresize',
            'onunload',
            'ondevicemotion',
            'ondeviceorientation',
            'onabort',
            'onblur',
            'oncanplay',
            'oncanplaythrough',
            'onchange',
            'onclick',
            'oncontextmenu',
            'ondblclick',
            'ondrag',
            'ondragend',
            'ondragenter',
            'ondragleave',
            'ondragover',
            'ondragstart',
            'ondrop',
            'ondurationchange',
            'onemptied',
            'onended',
            'onerror',
            'onfocus',
            'oninput',
            'oninvalid',
            'onkeydown',
            'onkeypress',
            'onkeyup',
            'onload',
            'onloadeddata',
            'onloadedmetadata',
            'onloadstart',
            'onmousedown',
            'onmousemove',
            'onmouseout',
            'onmouseover',
            'onmouseup',
            'onpause',
            'onplay',
            'onplaying',
            'onprogress',
            'onratechange',
            'onreset',
            'onscroll',
            'onseeked',
            'onseeking',
            'onselect',
            'onshow',
            'onstalled',
            'onsubmit',
            'onsuspend',
            'ontimeupdate',
            'onvolumechange',
            'onwaiting',
            'oncopy',
            'oncut',
            'onpaste',
            'onbeforescriptexecute',
            'onafterscriptexecute',
        ];

        foreach ($this->fetchAll() as $item) {
            $this->definition = $item['definition'];
            $item['item'] = preg_quote((string) $item['item'], '/');
            $content = Strings::preg_replace_callback(
                '/(' . $item['item'] . '="[^"]*")|'
                // b. the glossary item could be inside an attribute value
                . '((' . implode('|', $attributes) . ')="[^"]*' . $item['item'] . '[^"]*")|'
                // c. the glossary item could be everywhere as a distinct word
                . '(\W+)(' . $item['item'] . ')(\W+)|'
                // d. the glossary item could be at the beginning of the string as a distinct word
                . '^(' . $item['item'] . ')(\W+)|'
                // e. the glossary item could be at the end of the string as a distinct word
                . '(\W+)(' . $item['item'] . ')$'
                . '/mis',
                $this->setTooltip(...),
                $content,
                1
            );
        }

        return $content;
    }

    /**
     * Gets all items and definitions from the database.
     *
     * @return array<array<int, string, string>>
     */
    public function fetchAll(): array
    {
        $items = [];

        if ($this->cachedItems !== []) {
            return $this->cachedItems;
        }

        $query = sprintf(
            "SELECT id, lang, item, definition FROM %sfaqglossary WHERE lang = '%s' ORDER BY item ASC",
            Database::getTablePrefix(),
            $this->configuration->getLanguage()->getLanguage()
        );

        $result = $this->configuration->getDb()->query($query);

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $items[] = [
                'id' => $row->id,
                'language' => $row->lang,
                'item' => stripslashes((string) $row->item),
                'definition' => stripslashes((string) $row->definition),
            ];
        }

        return $this->cachedItems = $items;
    }

    /**
     * Callback function for filtering HTML from URLs and images.
     *
     * @param array $matches Matches
     */
    public function setTooltip(array $matches): string
    {
        $prefix = '';
        $postfix = '';
        if (count($matches) > 9) {
            // if the word is at the end of the string
            $prefix = $matches[9];
            $item = $matches[10];
        } elseif (count($matches) > 7) {
            // if the word is at the beginning of the string
            $item = $matches[7];
            $postfix = $matches[8];
        } elseif (count($matches) > 4) {
            // if the word is else where in the string
            $prefix = $matches[4];
            $item = $matches[5];
            $postfix = $matches[6];
        }

        if (!empty($item)) {
            return sprintf(
                '%s<abbr data-bs-toggle="tooltip" data-bs-placement="bottom" title="%s" class="initialism">%s</abbr>%s',
                $prefix,
                $this->definition,
                $item,
                $postfix
            );
        }

        // Fallback: the original matched string
        return $matches[0];
    }

    /**
     * Gets one item and definition from the database.
     *
     * @param int $id Glossary ID
     */
    public function fetch(int $id): array
    {
        $item = [];

        $query = sprintf(
            "SELECT id, lang, item, definition FROM %sfaqglossary WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $id,
            $this->getLanguage()
        );

        $result = $this->configuration->getDb()->query($query);

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $item = [
                'id' => $row->id,
                'language' => $row->lang,
                'item' => stripslashes((string) $row->item),
                'definition' => stripslashes((string) $row->definition),
            ];
        }

        return $item;
    }

    /**
     * Inserts an item and definition into the database.
     *
     * @param string $item       Item
     * @param string $definition Definition
     */
    public function create(string $item, string $definition): bool
    {
        $this->definition = $this->configuration->getDb()->escape($definition);

        $query = sprintf(
            "INSERT INTO %sfaqglossary (id, lang, item, definition) VALUES (%d, '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqglossary', 'id'),
            $this->getLanguage(),
            Strings::htmlspecialchars(substr($item, 0, 254)),
            Strings::htmlspecialchars($this->definition)
        );
        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Updates an item and definition into the database.
     *
     * @param int    $id         Glossary ID
     * @param string $item       Item
     * @param string $definition Definition
     */
    public function update(int $id, string $item, string $definition): bool
    {
        $item = $this->configuration->getDb()->escape($item);
        $definition = $this->configuration->getDb()->escape($definition);

        $query = sprintf(
            "UPDATE %sfaqglossary SET item = '%s', definition = '%s' WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            Strings::htmlspecialchars(substr($item, 0, 254)),
            Strings::htmlspecialchars($definition),
            $id,
            $this->getLanguage()
        );
        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Deletes an item and definition into the database.
     *
     * @param int $id Glossary ID
     */
    public function delete(int $id): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqglossary WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $id,
            $this->getLanguage()
        );
        return (bool) $this->configuration->getDb()->query($query);
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): Glossary
    {
        $this->language = $language;
        return $this;
    }
}
