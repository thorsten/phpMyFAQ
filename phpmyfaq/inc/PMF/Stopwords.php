<?php

/**
 * The main Stopwords class.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-01
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Stopwords.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-01
 */
class PMF_Stopwords
{
    /**
     * @var PMF_Configuration
     */
    private $_config;

    /**
     * @var PMF_Language
     */
    private $_language;

    /**
     * Table name.
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Stopwords
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
        $this->table_name = PMF_Db::getTablePrefix().'faqstopwords';
    }

    /**
     * @return PMF_Language
     */
    public function getLanguage()
    {
        return $this->_language;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->table_name;
    }

    /**
     * @param PMF_Language $language
     */
    public function setLanguage($language)
    {
        $this->_language = $language;
    }

    /**
     * @param string $table_name
     */
    public function setTableName($table_name)
    {
        $this->table_name = $table_name;
    }

    /**
     * Add a word to the stop words dictionary.
     * If the given word already exists, false is returned. 
     *
     * @param  string $word
     *                       
     * @return bool
     */
    public function add($word)
    {
        if (!$this->match($word)) {
            $sql = sprintf(
                "INSERT INTO $this->table_name VALUES(%d, '%s', '%s')",
                $this->_config->getDb()->nextId($this->table_name, 'id'),
                $this->_language,
                $word
            );
            $this->_config->getDb()->query($sql);

            return true;
        }

        return false;
    }

    /**
     * Update a word in the stop words dictionary.
     *
     * @param int    $id
     * @param string $word
     */
    public function update($id, $word)
    {
        $sql = "UPDATE $this->table_name SET stopword = '%s' WHERE id = %d AND lang = '%s'";
        $sql = sprintf(
            $sql,
            $word,
            $id,
            $this->_language
        );

        $this->_config->getDb()->query($sql);
    }

    /**
     * Remove a word from the stop word dictionary.
     *
     * @param int $id
     */
    public function remove($id)
    {
        $sql = sprintf(
            "DELETE FROM $this->table_name WHERE id = %d AND lang = '%s'",
            $id,
            $this->_language
        );

        $this->_config->getDb()->query($sql);
    }

    /**
     * Match a word against the stop words dictionary.
     *
     * @param string $word
     *
     * @return bool
     */
    public function match($word)
    {
        $sql = sprintf(
            "SELECT id FROM $this->table_name WHERE LOWER(stopword) = LOWER('%s') AND lang = '%s'",
            $word,
            $this->_language
        );

        $result = $this->_config->getDb()->query($sql);

        return $this->_config->getDb()->numRows($result) > 0;
    }

    /**
     * Retrieve all the stop words by a certain language.
     *
     * @param string $lang      Language to retrieve stop words by
     * @param bool   $wordsOnly
     *
     * @return array
     */
    public function getByLang($lang = null, $wordsOnly = false)
    {
        $lang = is_null($lang) ? $this->_config->getLanguage()->getLanguage() : $lang;
        $sql = sprintf(
            "SELECT id, lang, LOWER(stopword) AS stopword FROM $this->table_name WHERE lang = '%s'",
            $lang
        );

        $result = $this->_config->getDb()->query($sql);

        $retval = [];

        if ($wordsOnly) {
            while (($row = $this->_config->getDb()->fetchObject($result)) == true) {
                $retval[] = $row->stopword;
            }
        } else {
            return $this->_config->getDb()->fetchAll($result);
        }

        return $retval;
    }

    /**
     * Filter some text cutting out all non words and stop words.
     *
     * @param string $input text to filter
     *
     * @return array
     */
    public function clean($input)
    {
        $words = explode(' ', $input);
        $stop_words = $this->getByLang(null, true);
        $retval = [];

        foreach ($words as $word) {
            $word = PMF_String::strtolower($word);
            if (!is_numeric($word) && 1 < PMF_String::strlen($word) &&
               !in_array($word, $stop_words) && !in_array($word, $retval)) {
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
     *
     * @return bool
     */
    public function checkBannedWord($content)
    {
        // Sanity checks
        $content = PMF_String::strtolower(trim($content));
        if (('' === $content) || (!$this->_config->get('spam.checkBannedWords'))) {
            return true;
        }

        // Check if we check more than one word
        $checkWords = explode(' ', $content);
        if (1 === count($checkWords)) {
            $checkWords = array($content);
        }

        $bannedWords = $this->getBannedWords();
        // We just search a match of, at least, one banned word into $content
        if (is_array($bannedWords)) {
            foreach ($bannedWords as $bannedWord) {
                foreach ($checkWords as $word) {
                    if (PMF_String::strtolower($word) === PMF_String::strtolower($bannedWord)) {
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
     * @return array
     */
    private function getBannedWords()
    {
        $bannedTrimmedWords = [];
        $bannedWordsFile = PMF_INCLUDE_DIR.'/blockedwords.txt';
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
