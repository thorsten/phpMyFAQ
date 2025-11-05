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

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Glossary\GlossaryRepository;
use phpMyFAQ\Glossary\GlossaryRepositoryInterface;

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

    // Repository to access storage
    private GlossaryRepositoryInterface $repository;

    public function __construct(
        private readonly Configuration $configuration,
        ?GlossaryRepositoryInterface $repository = null,
    ) {
        $this->repository = $repository ?? new GlossaryRepository($this->configuration);
    }

    /**
     * Fill the passed string with the current Glossary items.
     *
     * @param string $content Content
     */
    public function insertItemsIntoContent(string $content = ''): string
    {
        if ($content === '') {
            return '';
        }

        foreach ($this->fetchAll() as $item) {
            $this->definition = $item['definition'];
            $quotedItem = preg_quote($item['item'], delimiter: '/');
            $pattern = '/(^|\W)(' . $quotedItem . ')(\W|$)/';

            $content = Strings::preg_replace_callback(
                pattern: $pattern,
                callback: $this->setTooltip(...),
                subject: $content,
                limit: 1,
            );
        }

        return $content;
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
        $item = '';
        $count = count($matches);

        if ($count > 9) {
            // if the word is at the end of the string
            $prefix = $matches[9];
            $item = $matches[10];
        }

        if ($item === '' && $count > 7) {
            // if the word is at the beginning of the string
            $item = $matches[7];
            $postfix = $matches[8];
        }

        if ($item === '' && $count > 4) {
            // if the word is else where in the string
            $prefix = $matches[4];
            $item = $matches[5];
            $postfix = $matches[6];
        }

        if ($item === '' && $count >= 3) {
            // simplified pattern fallback: (^|\W) (item) (\W|$)
            $prefix = $matches[1] ?? '';
            $item = $matches[2] ?? '';
            $postfix = $matches[3] ?? '';
        }

        if ($item === '') {
            // Fallback: the original matched string
            return $matches[0];
        }

        $fmt = '%s<abbr data-bs-toggle="tooltip" data-bs-placement="bottom" title="%s" class="initialism">%s</abbr>%s';
        return sprintf($fmt, $prefix, $this->definition, $item, $postfix);
    }

    /**
     * Gets one item and definition from the database.
     *
     * @param int $id Glossary ID
     */
    public function fetch(int $id): array
    {
        return $this->repository->fetch($id, $this->currentLanguage());
    }

    /**
     * Gets all items and definitions from the database.
     *
     * @return array<int, array{id:int, language:string, item:string, definition:string}>
     */
    public function fetchAll(): array
    {
        $language = $this->currentLanguage();

        if (isset($this->cachedItems[$language])) {
            return $this->cachedItems[$language];
        }

        $items = $this->repository->fetchAll($language);

        $this->cachedItems[$language] = $items;

        return $items;
    }

    /**
     * Inserts an item and definition into the database.
     *
     * @param string $item       Item
     * @param string $definition Definition
     */
    public function create(string $item, string $definition): bool
    {
        return $this->repository->create($this->currentLanguage(), $item, $definition);
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
        return $this->repository->update($id, $this->currentLanguage(), $item, $definition);
    }

    /**
     * Deletes an item and definition into the database.
     *
     * @param int $id Glossary ID
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id, $this->currentLanguage());
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): Glossary
    {
        $this->language = $language;
        // Reset cache when language changes
        $this->cachedItems = [];
        return $this;
    }

    /**
     * Returns explicitly set language or falls back to the configuration language.
     */
    private function currentLanguage(): string
    {
        if (isset($this->language) && $this->language !== '') {
            return $this->language;
        }
        return $this->configuration->getLanguage()->getLanguage();
    }
}
