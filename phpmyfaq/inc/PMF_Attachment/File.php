<?php
/**
 * Attachment handler class for files stored in filesystem 
 *
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id$
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
 * PMF_Atachment_File
 * 
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
 */
class PMF_Attachment_File extends PMF_Attachment_Abstract implements PMF_Attachment_Interface
{
    
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
    protected function getHashForFS()
    {
        $hash = '';
        
        if($this->encrypted) {
            $hash = md5($this->id . $this->recordId);
        } else {
            $hash = $this->hash;
        }
        
        return $hash;
    }
    
    /**
     * Build filepath under which the attachment
     * file is accessible in filesystem
     * 
     * @return string
     */
    protected function buildFilePath()
    {        
        $retval = PMF_ATTACHMENTS_DIR;
        $fsHash = $this->getHashForFS();
        $subDirCount = 3;
        
        for($i = 0; $i < $subDirCount; $i++) {
            $retval .= DIRECTORY_SEPARATOR
                     . substr($fsHash, $i*$subDirCount, $subDirCount);
        }
        $retval .= DIRECTORY_SEPARATOR . substr($fsHash, $i*$subDirCount);
        
        return $retval;
    }

    /**
     * Create subdirs to save file to
     * 
     * @return boolean success
     */
    public function createSubDirs()
    {
        $attDir = dirname($this->buildFilePath());
        
        return file_exists($attDir) && is_dir($attDir) ||
               mkdir($attDir, 0700, true);
    }
    
    /**
     * Check weither the filestorage is ok
     * 
     * @return boolean
     */
    public function isStorageOk()
    {
        clearstatcache();
        
        $attachmentDir = dirname($this->buildFilePath()); 
        
        return false !== PMF_ATTACHMENTS_DIR &&
               file_exists(PMF_ATTACHMENTS_DIR) &&
               is_dir(PMF_ATTACHMENTS_DIR) &&
               file_exists($recordAttachmentsDir) &&
               is_dir($recordAttachmentsDir);
    }
    
    /**
     * Save current attachment to the appropriate storage. The
     * filepath given will be processed and moved to appropriate
     * location.
     * 
     * @param string $filepath full path to the attachment file
     * 
     * @return boolean
     */
    public function save($filepath)
    {
        $retval = false;
        
        if(file_exists($filepath)) {

            $this->hash = md5_file($filepath);
            $this->filename = basename($filepath);
            
            $this->saveMeta();
            if(null !== $this->id && $this->createSubDirs()) {

            }
        }
        
        return $retval;
    }
    
    /**
     * Retrieve file contents into a variable
     * 
     * @return string
     */
    public function get()
    {
        
    }
    
    /**
     * Output current file to stdout
     * 
     * @return null
     */
    public function rawOut()
    {
        
    }
}
