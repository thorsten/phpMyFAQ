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
 * @copyright 2005-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-15
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Glossary\GlossaryHelper;
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
    private GlossaryRepositoryInterface $glossaryRepository;

    private GlossaryHelper $glossaryHelper;

    public function __construct(
        private readonly Configuration $configuration,
        ?GlossaryRepositoryInterface $glossaryRepository = null,
    ) {
        $this->glossaryRepository = $glossaryRepository ?? new GlossaryRepository($this->configuration);
        $this->glossaryHelper = new GlossaryHelper();
    }

    /**
     * Fill the passed string with the current Glossary items.
     *
     * @param string $content Content
     */
    public function insertItemsIntoContent(string $content = ''): string
    {
        // Lazy init in case a test created a mock without running the constructor
        if (!property_exists($this, 'glossaryHelper') || $this->glossaryHelper === null) {
            $this->glossaryHelper = new GlossaryHelper();
        }

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
        if (!property_exists($this, 'glossaryHelper') || $this->glossaryHelper === null) {
            $this->glossaryHelper = new GlossaryHelper();
        }

        [$prefix, $item, $postfix] = $this->glossaryHelper->extractMatchParts($matches);
        if ($item === '') {
            return $matches[0];
        }

        return $this->glossaryHelper->formatTooltip($this->definition, $item, $prefix, $postfix);
    }

    /**
     * Gets one item and definition from the database.
     *
     * @param int $id Glossary ID
     */
    public function fetch(int $id): array
    {
        return $this->glossaryRepository->fetch($id, $this->currentLanguage());
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

        $items = $this->glossaryRepository->fetchAll($language);

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
        $ok = $this->glossaryRepository->create($this->currentLanguage(), $item, $definition);
        if ($ok) {
            unset($this->cachedItems[$this->currentLanguage()]);
        }

        return $ok;
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
        $ok = $this->glossaryRepository->update($id, $this->currentLanguage(), $item, $definition);
        if ($ok) {
            unset($this->cachedItems[$this->currentLanguage()]);
        }

        return $ok;
    }

    /**
     * Deletes an item and definition into the database.
     *
     * @param int $id Glossary ID
     */
    public function delete(int $id): bool
    {
        $ok = $this->glossaryRepository->delete($id, $this->currentLanguage());
        if ($ok) {
            unset($this->cachedItems[$this->currentLanguage()]);
        }

        return $ok;
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
