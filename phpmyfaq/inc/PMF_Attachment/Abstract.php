<?php
/**
 * Abstract attachment class
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
 * @package   PMF_Attachment
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-08-21
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Atachment_Abstract
 * 
 * @category  phpMyFAQ
 * @package   PMF_Attachment
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-08-21
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
     * Real file md5 hash
     * 
     * @var string
     */
    protected $realHash;
    
    /**
     * Virtual unique md5 hash used for encrypted files.
     * Must equal real hash for unencrypted files.
     * 
     * @var string
     */
    protected $virtualHash;
    
    /**
     * If this is set, the sh1 hashed key we got must equal to it 
     * 
     * @var string
     */
    protected $passwordHash;
    
    /**
     * Filesize in bytes
     * 
     * @var integer
     */
    protected $filesize;
    
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
     * Attachment file mime type
     * 
     * @var string
     */
    protected $mimeType;
    /**
     * Constructor
     * 
     * @param integer $id attachment id
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
     * @param string $key     encryption key
     * @param string $default if the key is default system wide
     * 
     * @return null
     */
    public function setKey ($key, $default = true)
    {
        $this->key = $key;
        $this->encrypted = null !== $key;
        
        /**
         * Not default means the key was set explicitly
         * for this attachment, so lets hash it
         */
        if($this->encrypted && !$default) {
            $this->passwordHash = sha1($key);
        }
    }
    
    /**
     * Set record id
     * 
     * @param integer $id record id
     * 
     * @return null
     */
    public function setRecordId ($id)
    {
        $this->recordId = $id;
    }
    
    /**
     * Set record language
     * 
     * @param lang $lang record language
     * 
     * @return null
     */
    public function setRecordLang ($lang)
    {
        $this->recordLang = $lang;
    }
    
    /**
     * Get attachment id
     * 
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Get attachment record id
     * 
     * @return integer
     */
    public function getRecordId()
    {
        return $this->recordId;
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
                record_id, record_lang, real_hash, virtual_hash, password_hash,
                filename, filesize, encrypted, mime_type
            FROM
                %sfaqattachment
            WHERE 
                id = %d", 
            SQLPREFIX, 
            (int)$this->id);
        
        $result = $this->db->query($sql);
        
        if ($result) {
            $assoc = $this->db->fetch_assoc($result);
            if (!empty($assoc)) {
                $this->recordId     = $assoc['record_id'];
                $this->recordLang   = $assoc['record_lang'];
                $this->realHash     = $assoc['real_hash'];
                $this->virtualHash  = $assoc['virtual_hash'];
                $this->passwordHash = $assoc['password_hash'];
                $this->filename     = $assoc['filename'];
                $this->filesize     = $assoc['filesize'];
                $this->encrypted    = $assoc['encrypted'];
                $this->mimeType     = $assoc['mime_type'];
                
                $retval             = true;
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
        $faqattTableName = sprintf('%sfaqattachment', SQLPREFIX);

        if (null == $this->id) {
            
            $this->id = $this->db->nextID($faqattTableName, 'id');
            
            $sql = sprintf("
                INSERT INTO 
                    %s
                (id, record_id, record_lang, real_hash, virtual_hash,
                password_hash, filename, filesize, encrypted, mime_type)
                    VALUES
                (%d, %d, '%s', '%s', '%s', '%s', '%s', %d, %d, '%s')",
                    $faqattTableName,
                    $this->id,
                    $this->recordId,
                    $this->recordLang,
                    $this->realHash,
                    $this->virtualHash,
                    $this->passwordHash,
                    $this->filename,
                    $this->filesize,
                    $this->encrypted ? 1 : 0,
                    $this->mimeType);
            
            $result = $this->db->query($sql);
        } else {
            // do update here
        }
        
        return $this->id;
    }
    
    /**
     * Update several meta things after it was saved
     * 
     * @return null
     */
    protected function postUpdateMeta()
    {
        $sql = sprintf("UPDATE
                            %sfaqattachment
                        SET virtual_hash = '%s',
                            mime_type = '%s'
                        WHERE id = %d",
                        SQLPREFIX,
                        $this->virtualHash,
                        $this->readMimeType(),
                        $this->id);
        
        $this->db->query($sql);
    }
    
    /**
     * The function is supposed to detect the file mime
     * type.
     * 
     * @return string
     * TODO implement this
     */
    protected function readMimeType()
    {
        $ext = pathinfo($this->filename, PATHINFO_EXTENSION);
        $this->mimeType = PMF_Attachment_MimeType::guessByExt($ext);
        
        return $this->mimeType;
    }
    
    /**
     * Generate hash based on current conditions
     * 
     * @return string
     * 
     * NOTE The way a file is saved in the filesystem
     * is based on md5 hash. If the file is unencrypted,
     * it's md5 hash is used directly, otherwise a
     * hash based on several tokens gets generated.
     */
    protected function mkVirtualHash()
    {
        if($this->encrypted) {
            if(null === $this->id || null === $this->recordId ||
               null === $this->realHash || null === $this->filename ||
               null === $this->key) {
                throw new PMF_Attachment_Exception('All of id, ' .
                                     'recordId, hash, filename, ' .
                                     'key is needed to generate ' . 
                                     'fs hash for encrypted files');
            }
            
            $src = $this->id . $this->recordId . $this->realHash .
                        $this->filename . $this->key;
            $this->virtualHash = md5($src);            
        } else {
            $this->virtualHash = $this->realHash;
        }
        
        return $this->virtualHash;
    }
    
    /**
     * Check if the same virtual hash exists more than once
     * in the database. If so, this means the file
     * is already uploaded as unencrypted.
     *  
     * @return boolean
     */
    protected function linkedRecords()
    {
        $sql = sprintf("SELECT
                             COUNT(1) AS count
                        FROM 
                             %sfaqattachment
                        WHERE virtual_hash = '%s'",
                        SQLPREFIX,
                        $this->virtualHash);
        
        $result = $this->db->query($sql);
        
        if ($result) {
            $assoc = $this->db->fetch_assoc($result);
        }
                        
        return $assoc['count'] > 1;
    }
    
    /**
     * Remove meta data from the db
     * 
     * @return null
     */
    protected function deleteMeta()
    {
        $sql = sprintf("DELETE FROM
                             %sfaqattachment
                        WHERE id = %d",
                        SQLPREFIX,
                        $this->id);
                        
        $this->db->query($sql);
    }
    
    /**
     * Validate attached file with the real hash
     * 
     * @return boolean
     */
    public function validate()
    {
        // TODO implement this
    }
    
    /**
     * Destructor
     * 
     * @return null
     */
    public function __destruct()
    {
        
    }
    
    public function getFilename()
    {
        return $this->filename;
    } 
}
