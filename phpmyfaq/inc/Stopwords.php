<?php

/**
* $Id: Stopwords.php,v 1.39 2007-08-12 13:56:28 thorstenr Exp $
*
* The main Tags class
*
* @package   phpMyFAQ
* @author    Anatoliy Belsky
* @since     2009-04-01
* @copyright 2006-2009 phpMyFAQ Team
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
     * @var object PMF_Db
     */
    private $db;

    /**
     * Language
     *
     * @var string
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
     */
    private function __construct()
    {
        $this->db       = PMF_Db::getInstance();
        $this->language = PMF_Init::$language;
        $this->table_name = SQLPREFIX . "faqstopwords";
    }
    
    
    /**
     * Returns the single instance
     *
     * @access static
     * @return PMF_Stopwords
     */
    public static function getInstance()
    {
        if (null == self::$instance) {
            $className = __CLASS__;
            self::$instance = new $className();
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
        if(!$this->match($word)) {
            $sql = "INSERT INTO $this->table_name VALUES(%d, '%s', '%s')";
            $sql = sprintf($sql, $this->db->nextID($this->table_name, 'id'), $this->language, $word);
            
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
        $sql = sprintf($sql, $word, $id, $this->language);
        
        $this->db->query($sql);
    }
    
    
    /**
     * Remove a word from the stop word dictionary
     *
     * @param unknown_type $id
     * 
     * @return void
     */
    public function remove($id)
    {
        $sql = sprintf("DELETE FROM $this->table_name WHERE id = %d AND lang = '%s'", $id, $this->language);
        
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
        $sql = "SELECT id FROM $this->table_name WHERE stopword = '%s' AND lang = '%s'";
        $sql = sprintf($sql, $word, $this->language);
        
        $result = $this->db->query($sql);
        
        return $this->db->num_rows($result) > 0;
    }
    
    
    /**
     * Retrieve all the stop words by a certain language
     * 
     * @param string $lang Language to retrieve stop words by
     * 
     * @return array
     */
    public function getByLang($lang)
    {
        $sql = sprintf("SELECT * FROM $this->table_name WHERE lang = '%s'", $lang);
        
        $result = $this->db->query($sql);
        
        $retval = array();
        
        while(($row = $this->db->fetch_object($result)) == true) {
            $retval[] = $row; 
        }
        
        return $retval;
    }
}
