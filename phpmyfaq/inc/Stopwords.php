<?php
/**
 * The main Stopwords class
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   PMF_Stopwords
 * @author    Anatoliy Belsky
 * @copyright 2009-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
 * @copyright 2009-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
     * DB handle
     *
     * @var PMF_Db
     */
    private $db;

    /**
     * Language
     *
     * @var PM_Language
     */
    private $language;
    
    /**
     * Table name
     * 
     * @var string
     */
    private $table_name;

    /**
     * Constructor
     *
     * @param PMF_DB_Driver $database Database connection
     * @param PMF_Language  $language Language object
     *
     * @return PMF_Stopwords
     */
    private function __construct(PMF_DB_Driver $database, PMF_Language $language)
    {
        $this->db       = $database;
        $this->language = $language;
        $this->table_name = SQLPREFIX . "faqstopwords";
    }
    
    /**
     * @return PMF_Language
     */
    public function getLanguage ()
    {
        return $this->language;
    }
    
    /**
     * @return string
     */
    public function getTableName ()
    {
        return $this->table_name;
    }
    
    /**
     * @param PMF_Language $language
     */
    public function setLanguage ($language)
    {
        $this->language = $language;
    }
    
    /**
     * @param string $table_name
     */
    public function setTableName ($table_name)
    {
        $this->table_name = $table_name;
    }
    
    /**
     * Returns the single instance
     *
     * @access static
     *
     * @param PMF_DB_Driver $database Database connection
     * @param PMF_Language  $language Language object
     *
     * @return PMF_Stopwords
     */
    public static function getInstance(PMF_DB_Driver $database, PMF_Language $language)
    {
        if (null == self::$instance) {
            $className = __CLASS__;
            self::$instance = new $className($database, $language);
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
            $sql = sprintf($sql, $this->db->nextId($this->table_name, 'id'), $this->language->getLanguage(), $word);
            
            $this->db->query($sql);
            
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
        $sql = sprintf($sql, $word, $id, $this->language->getLanguage());
        
        $this->db->query($sql);
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
            $this->language->getLanguage()
        );
        
        $this->db->query($sql);
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
        $sql = sprintf($sql, $word, $this->language->getLanguage());
        
        $result = $this->db->query($sql);
        
        return $this->db->numRows($result) > 0;
    }
    
    
    /**
     * Retrieve all the stop words by a certain language
     * 
     * @param string $lang Language to retrieve stop words by
     * @param boolean wordsOnly
     * 
     * @return array
     */
    public function getByLang($lang = null, $wordsOnly = false)
    {
        $lang = is_null($lang) ? $this->language->getLanguage() : $lang;
        $sql  = sprintf("SELECT id, lang, LOWER(stopword) AS stopword FROM $this->table_name WHERE lang = '%s'", $lang);
        
        $result = $this->db->query($sql);
        
        $retval = array();
        
        if($wordsOnly) {
            while(($row = $this->db->fetchObject($result)) == true) {
                $retval[] = $row->stopword; 
            }
        } else {
            return $this->db->fetchAll($result);
        }
        
        return $retval;
    }
    
    
    /**
     * Filter some text cutting out all non words and stop words
     * 
     * @param string $input text to filter
     * 
     * @return string 
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
}