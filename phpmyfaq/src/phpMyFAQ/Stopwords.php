<?php

/**
 * The main Stopwords class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-01
 */

namespace phpMyFAQ;

/**
 * Class Stopwords
 *
 * @package phpMyFAQ
 */
class Stopwords
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var string
     */
    private $language;

    /**
     * Table name.
     *
     * @var string
     */
    private $tableName;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->tableName = Database::getTablePrefix() . 'faqstopwords';
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $language
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    /**
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * Add a word to the stop words dictionary.
     * If the given word already exists, false is returned.
     *
     * @param string $word
     * @return bool
     */
    public function add(string $word): bool
    {
        if (!$this->match($word)) {
            $sql = sprintf(
                "INSERT INTO %s VALUES(%d, '%s', '%s')",
                $this->tableName,
                $this->config->getDb()->nextId($this->tableName, 'id'),
                $this->language,
                $word
            );
            $this->config->getDb()->query($sql);

            return true;
        }

        return false;
    }

    /**
     * Update a word in the stop words dictionary.
     *
     * @param int    $id
     * @param string $word
     * @return bool
     */
    public function update(int $id, string $word): bool
    {
        $sql = "UPDATE %s SET stopword = '%s' WHERE id = %d AND lang = '%s'";
        $sql = sprintf(
            $sql,
            $this->tableName,
            $word,
            $id,
            $this->language
        );

        return (bool) $this->config->getDb()->query($sql);
    }

    /**
     * Remove a word from the stop word dictionary.
     *
     * @param int $id
     * @return bool
     */
    public function remove(int $id): bool
    {
        $sql = sprintf(
            "DELETE FROM %s WHERE id = %d AND lang = '%s'",
            $this->tableName,
            $id,
            $this->language
        );

        return (bool) $this->config->getDb()->query($sql);
    }

    /**
     * Match a word against the stop words dictionary.
     *
     * @param string $word
     * @return bool
     */
    public function match(string $word): bool
    {
        $sql = sprintf(
            "SELECT id FROM %s WHERE LOWER(stopword) = LOWER('%s') AND lang = '%s'",
            $this->tableName,
            $word,
            $this->language
        );

        $result = $this->config->getDb()->query($sql);

        return $this->config->getDb()->numRows($result) > 0;
    }

    /**
     * Retrieve all the stop words by a certain language.
     *
     * @param string $lang      Language to retrieve stop words by
     * @param bool   $wordsOnly
     *
     * @return string[]
     */
    public function getByLang($lang = null, $wordsOnly = false): array
    {
        $lang = is_null($lang) ? $this->config->getLanguage()->getLanguage() : $lang;
        $sql = sprintf(
            "SELECT id, lang, LOWER(stopword) AS stopword FROM %s WHERE lang = '%s'",
            $this->tableName,
            $lang
        );

        $result = $this->config->getDb()->query($sql);

        $stopWords = [];

        if ($wordsOnly) {
            while (($row = $this->config->getDb()->fetchObject($result)) == true) {
                $stopWords[] = $row->stopword;
            }
        } else {
            return $this->config->getDb()->fetchAll($result);
        }

        return $stopWords;
    }

    /**
     * Filter some text cutting out all non words and stop words.
     *
     * @param string $input text to filter
     * @return string[]
     */
    public function clean(string $input): array
    {
        $words = explode(' ', $input);
        $stop_words = $this->getByLang(null, true);
        $retval = [];

        foreach ($words as $word) {
            $word = Strings::strtolower($word);
            if (
                !is_numeric($word) && 1 < Strings::strlen($word)
                && !in_array($word, $stop_words) && !in_array($word, $retval)
            ) {
                $retval[] = $word;
            }
        }

        return $retval;
    }

    /**
     * This function checks the content against a bad word list if the banned
     * word spam protection has been activated from the general phpMyFAQ
     * configuration.
     *
     * @param string $content
     * @return bool
     */
    public function checkBannedWord(string $content): bool
    {
        // Sanity checks
        $content = Strings::strtolower(trim($content));
        if (('' === $content) || (!$this->config->get('spam.checkBannedWords'))) {
            return true;
        }

        // Check if we check more than one word
        $checkWords = explode(' ', $content);
        if (1 === count($checkWords)) {
            $checkWords = [$content];
        }

        $bannedWords = $this->getBannedWords();
        // We just search a match of, at least, one banned word into $content
        if (is_array($bannedWords)) {
            foreach ($bannedWords as $bannedWord) {
                foreach ($checkWords as $word) {
                    if (Strings::strtolower($word) === Strings::strtolower($bannedWord)) {
                        return false;
                    }
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
