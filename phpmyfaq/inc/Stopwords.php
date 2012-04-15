<?php
/**
 * The main Stopwords class
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Stopwords
 * @author    Anatoliy Belsky
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-04-01
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Stopwords
 *
 * @category  phpMyFAQ
 * @package   PMF_Stopwords
 * @author    Anatoliy Belsky
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-04-01
 */
class PMF_Stopwords
{
    /**
     * Instance
     *
     * @var PMF_Stopwords
     */
    private static $instance = null;
    
    /**
     * @var PMF_Configuration
     */
    private $_config;

    /**
     * @var PMF_Language
     */
    private $_language;

    /**
     * Table name
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Stopwords
     */
    private function __construct(PMF_Configuration $config)
    {
        $this->_config    = $config;
        $this->table_name = SQLPREFIX . "faqstopwords";
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
     * Returns the single instance
     *
     * @access static
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Stopwords
     */
    public static function getInstance(PMF_Configuration $config)
    {
        if (null == self::$instance) {
            $className = __CLASS__;
            self::$instance = new $className($config);
        }
        return self::$instance;
    }
    
    /**
     * Add a word to the stop words dictionary.
     * If the given word already exists, false is returned. 
     *
     * @param string $word
     *  
     * @return boolean 
     */
    public function add($word)
    {
        if (!$this->match($word)) {

            $sql = "INSERT INTO $this->table_name VALUES(%d, '%s', '%s')";
            $sql = sprintf(
                $sql,
                $this->_config->getDb()->nextId($this->table_name, 'id'),
                $this->_language->getLanguage(),
                $word
            );
            
            $this->_config->getDb()->query($sql);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Update a word in the stop words dictionary
     *
     * @param int $id
     * @param strng $word
     *
     * @return void
     */
    public function update($id, $word)
    {
        $sql = "UPDATE $this->table_name SET stopword = '%s' WHERE id = %d AND lang = '%s'";
        $sql = sprintf(
            $sql,
            $word,
            $id,
            $this->_language->getLanguage()
        );
        
        $this->_config->getDb()->query($sql);
    }
    
    
    /**
     * Remove a word from the stop word dictionary
     *
     * @param integer $id
     *
     * @return void
     */
    public function remove($id)
    {
        $sql = sprintf(
            "DELETE FROM $this->table_name WHERE id = %d AND lang = '%s'",
            $id,
            $this->_language->getLanguage()
        );
        
        $this->_config->getDb()->query($sql);
    }
    
    
    /**
     * Match a word against the stop words dictionary
     *
     * @param string $word
     *
     * @return boolean
     */
    public function match($word)
    {
        $sql = "SELECT id FROM $this->table_name WHERE LOWER(stopword) = LOWER('%s') AND lang = '%s'";
        $sql = sprintf($sql, $word, $this->_language->getLanguage());
        
        $result = $this->_config->getDb()->query($sql);
        
        return $this->_config->getDb()->numRows($result) > 0;
    }
    
    
    /**
     * Retrieve all the stop words by a certain language
     *
     * @param string  $lang      Language to retrieve stop words by
     * @param boolean $wordsOnly
     *
     * @return array
     */
    public function getByLang($lang = null, $wordsOnly = false)
    {
        $lang = is_null($lang) ? $this->_language->getLanguage() : $lang;
        $sql  = sprintf(
            "SELECT id, lang, LOWER(stopword) AS stopword FROM $this->table_name WHERE lang = '%s'",
            $lang
        );
        
        $result = $this->_config->getDb()->query($sql);
        
        $retval = array();
        
        if ($wordsOnly) {
            while(($row = $this->_config->getDb()->fetchObject($result)) == true) {
                $retval[] = $row->stopword; 
            }
        } else {
            return $this->_config->getDb()->fetchAll($result);
        }
        
        return $retval;
    }
    
    
    /**
     * Filter some text cutting out all non words and stop words
     *
     * @param string $input text to filter
     *
     * @return array
     */
    public function clean($input)
    {
        $words      = explode(' ', $input);
        $stop_words = $this->getByLang(null, true); 
        $retval     = array();
        
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
     * This function checks the content against a dab word list
     * if the banned word spam protection has been activated from the general PMF configuration.
     *
     * @param string $content
     *
     * @return bool
     */
    public function checkBannedWord($content)
    {
        // Sanity checks
        $content = trim($content);
        if (('' == $content) && (!$this->_config->get('spam.checkBannedWords'))) {
            return true;
        }

        $bannedWords = $this->getBannedWords();
        // We just search a match of, at least, one banned word into $content
        $content = PMF_String::strtolower($content);
        if (is_array($bannedWords)) {
            foreach ($bannedWords as $bannedWord) {
                if (PMF_String::strpos($content, PMF_String::strtolower($bannedWord)) !== false) {
                    return false;
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
        $bannedTrimmedWords = array();
        $bannedWordsFile    = __DIR__ . '/blockedwords.txt';
        $bannedWords        = array();

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