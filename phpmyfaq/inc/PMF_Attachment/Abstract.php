<?php
/**
 * Abstract attachment class
 *
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id: Abstract.php 4459 2009-06-10 15:57:47Z thorsten $
 * @copyright  2009 phpMyFAQ Team
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
/**
 * PMF_Atachment_Abstract
 * 
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id: Abstract.php 4459 2009-06-10 15:57:47Z thorsten $
 * @copyright  2009 phpMyFAQ Team
 */
abstract class PMF_Attachment_Abstract
{
    /**
     * Attachment id
     * 
     * @var int
     */
    protected $id;
    
    /**
     * The key to encrypt with
     * 
     * @var string
     */
    protected $key;
    
    /**
     * Errors
     * @var array
     */
    protected $error = array();
    
    /**
     * Database instance
     * 
     * @var PMF_Db_Driver
     */
    protected $db;
    
    /**
     * Record ID
     * 
     * @var integer
     */
    protected $recordId;
    
    /**
     * Record language
     * 
     * @var string
     */
    protected $recordLang;
    
    /**
     * Hash
     * 
     * @var string
     */
    protected $hash;
    
    /**
     * Filename
     * 
     * @var string
     */
    protected $filename;
    
    /**
     * Encrypted
     * 
     * @var string
     */
    protected $encrypted;
        
    /**
     * Constructor
     * 
     * @param int $id attachment id
     * 
     * @return null
     */
    public function __construct ($id = null)
    {   
        $this->db = PMF_Db::getInstance();
        
        if (null !== $id) {
            $this->id = $id;
            $this->getMeta();
        }
    }
    
    /**
     * Build attachment url
     * 
     * @param boolean $forHTML either to use ampersands directly
     * 
     * @return string
     */
    public function buildUrl ($forHTML = true)
    {
        $amp = true == $forHTML ? '&amp;' : '&';
        
        return "index.php?action=attachment{$amp}id={$this->id}";
    }
    
    /**
     * Set encryption key
     * 
     * @param string $key
     * 
     * @return null
     */
    public function setKey ($key)
    {
        $this->key = $key;
        $this->encrypted = null == $key;
    }
    
    /**
     * Get meta data
     * 
     * @return boolean
     */
    protected function getMeta ()
    {
        $retval = false;
        
        $sql = sprintf("
            SELECT 
		      record_id, record_lang, hash, filename, encrypted
            FROM
                %sfaqattachment
            WHERE 
                id = %d", SQLPREFIX, (int) $this->id);
        
        $result = $this->db->query($sql);
        
        if ($result) {
            $assoc = $this->db->fetch_assoc($result);
            if (! empty($assoc)) {
                $this->recordId = $assoc['record_id'];
                $this->recordLang = $assoc['record_lang'];
                $this->hash = $assoc['hash'];
                $this->filename = $assoc['filename'];
                $this->encrypted = $assoc['encrypted'];
                $retval = true;
            }
        }
        
        return $retval;
    }
    
    /**
     * Save attachment meta data
     * 
     * @return integer saved attachment id
     * 
     * TODO implement update case
     */
    protected function saveMeta()
    {
        $retval = null;
        
        if(null == $this->id) {
            $sql = sprintf("INSERT INTO 
                        %sfaqattachment(record_id, record_lang, hash, filename, encrypted)
                        VALUES(NULL, %d, '%s', '%s', '%s', %d)",
                        $this->recordId,
                        $this->recordLang,
                        $this->hash,
                        $this->filename,
                        $this->encrypted);            
            $this->db->query($sql);
            
            $sql = "SELECT MAX(id) FROM %sfaqattachment";
            $retval = $this->db->getOne();
            $this->id = $retval;
        } else {
            // do update here
        }
        
        return $retval;
    }
    
    /**
     * Full path to the attachment file
     * 
     * @param $filepath
     * 
     * @return boolean
     */
    public function importUploadedFile($filepath)
    {
        $retval = false;
        
        if(file_exists($filepath)) {
            
        }
        
        return $retval;
    }
}
