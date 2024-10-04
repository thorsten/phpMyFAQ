<?php

/**
 * The main StopWords class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-01
 */

namespace phpMyFAQ;

use function _PHPStan_5f1729e44\React\Async\waterfall;

/**
 * Class StopWords
 *
 * @package phpMyFAQ
 */
class StopWords
{
    private string $language;

    /**
     * Table name.
     */
    private readonly string $tableName;

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $configuration)
    {
        $this->tableName = Database::getTablePrefix() . 'faqstopwords';
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function setLanguage(string $language): StopWords
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Add a word to the stop words dictionary.
     * If the given word already exists, false is returned.
     */
    public function add(string $word): bool
    {
        if (!$this->match($word)) {
            $sql = sprintf(
                "INSERT INTO %s VALUES(%d, '%s', '%s')",
                $this->getTableName(),
                $this->configuration->getDb()->nextId($this->tableName, 'id'),
                $this->configuration->getDb()->escape($this->language),
                $word
            );

            return (bool) $this->configuration->getDb()->query($sql);
        }

        return false;
    }

    /**
     * Update a word in the stop words dictionary.
     */
    public function update(int $id, string $word): bool
    {
        $sql = "UPDATE %s SET stopword = '%s' WHERE id = %d AND lang = '%s'";
        $sql = sprintf(
            $sql,
            $this->getTableName(),
            $this->configuration->getDb()->escape($word),
            $id,
            $this->configuration->getDb()->escape($this->language)
        );

        return (bool) $this->configuration->getDb()->query($sql);
    }

    /**
     * Remove a word from the stop word dictionary.
     */
    public function remove(int $id): bool
    {
        $sql = sprintf(
            "DELETE FROM %s WHERE id = %d AND lang = '%s'",
            $this->getTableName(),
            $id,
            $this->configuration->getDb()->escape($this->language)
        );

        return (bool) $this->configuration->getDb()->query($sql);
    }

    /**
     * Match a word against the stop words dictionary.
     */
    public function match(string $word): bool
    {
        $sql = sprintf(
            "SELECT id FROM %s WHERE LOWER(stopword) = LOWER('%s') AND lang = '%s'",
            $this->getTableName(),
            $this->configuration->getDb()->escape($word),
            $this->configuration->getDb()->escape($this->language)
        );

        $result = $this->configuration->getDb()->query($sql);

        return $this->configuration->getDb()->numRows($result) > 0;
    }

    /**
     * Retrieve all the stop words by a certain language.
     *
     * @param  string|null $lang      Language to retrieve stop words by
     * @return string[]
     */
    public function getByLang(?string $lang = null, bool $wordsOnly = false): array
    {
        $lang = is_null($lang) ? $this->configuration->getLanguage()->getLanguage() : $lang;
        $sql = sprintf(
            "SELECT id, lang, LOWER(stopword) AS stopword FROM %s WHERE lang = '%s'",
            $this->getTableName(),
            $this->configuration->getDb()->escape($lang)
        );

        $result = $this->configuration->getDb()->query($sql);

        $stopWords = [];

        if ($wordsOnly) {
            while (($row = $this->configuration->getDb()->fetchObject($result)) == true) {
                $stopWords[] = Strings::htmlentities($row->stopword);
            }
        } else {
            return $this->configuration->getDb()->fetchAll($result);
        }

        return $stopWords;
    }

    /**
     * Filter some text cutting out all non-words and stop words.
     *
     * @param string $input text to filter
     * @return string[]
     */
    public function clean(string $input): array
    {
        $words = explode(' ', $input);
        $stopWords = $this->getByLang(null, true);
        $result = [];

        foreach ($words as $word) {
            $word = Strings::strtolower($word);
            if (is_numeric($word)) {
                continue;
            }
            if (1 >= Strings::strlen($word)) {
                continue;
            }
            if (in_array($word, $stopWords)) {
                continue;
            }
            if (in_array($word, $result)) {
                continue;
            }
            $result[] = $word;
        }

        return $result;
    }

    /**
     * This function checks the content against a bad word list if the banned
     * word spam protection has been activated from the general phpMyFAQ
     * configuration.
     */
    public function checkBannedWord(string $content): bool
    {
        // Sanity checks
        $content = Strings::strtolower(trim($content));
        if (('' === $content) || (!$this->configuration->get('spam.checkBannedWords'))) {
            return true;
        }

        // Check if we check more than one word
        $checkWords = explode(' ', $content);
        if (1 === count($checkWords)) {
            $checkWords = [$content];
        }

        $bannedWords = $this->getBannedWords();

        // We just search a match of, at least, one banned word into $content
        foreach ($bannedWords as $bannedWord) {
            foreach ($checkWords as $checkWord) {
                if (Strings::strtolower($checkWord) === Strings::strtolower($bannedWord)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * This function returns the banned words dictionary as an array.
     *
     * @return string[]
     */
    private function getBannedWords(): array
    {
        $bannedTrimmedWords = [];
        $bannedWordsFile = PMF_SRC_DIR . '/blockedwords.txt';
        $bannedWords = [];

        // Read the dictionary
        if (file_exists($bannedWordsFile) && is_readable($bannedWordsFile)) {
            $bannedWords = file_get_contents($bannedWordsFile);
        }

        // Trim it
        foreach (explode("\n", $bannedWords) as $word) {
            $bannedTrimmedWords[] = trim($word);
        }

        return $bannedTrimmedWords;
    }
}
