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
            if(null === $this->id || null === $this->recordId ||
               null === $this->hash || null === $this->filename ||
               null === $this->key) {
                throw new PMF_Attachment_Exception('Each of id, ' .
                                     'recordId, hash, filename, ' .
                                     'key is missing but needed to generate ' . 
                                     'fs hash for encrypted files');
            }
            
            $hash = md5($this->id . $this->recordId . $this->hash .
                        $this->filename . $this->key);
        } else {
            $hash = $this->hash;
        }
        
        return $hash;
    }
    
    /**
     * Build filepath under which the attachment
     * 
     * file is accessible in filesystem
     * 
     * @return string
     */
    protected function buildFilePath()
    {        
        $retval = PMF_ATTACHMENTS_DIR;
        $fsHash = $this->getHashForFS();
        $subDirCount = 3;
        $subDirNameLength = 5;
        
        for($i = 0; $i < $subDirCount; $i++) {
            $retval .= DIRECTORY_SEPARATOR
                     . substr($fsHash, $i*$subDirNameLength, $subDirNameLength);
        }
        $retval .= DIRECTORY_SEPARATOR . substr($fsHash, $i*$subDirNameLength);
        
        return $retval;
    }

    /**
     * Create subdirs to save file to
     * 
     * @param string $filepath filpath to create subdirs for
     * 
     * @return boolean success
     */
    public function createSubDirs($filepath)
    {
        clearstatcache();
        
        $attDir = dirname($filepath);
        
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
            
            $targetFile = $this->buildFilePath();
            
            if(null !== $this->id && $this->createSubDirs($targetFile)) {
                $retval = $this->moveFile($filepath, $targetFile);
            }
        }
        
        return $retval;
    }
    
    /**
     * Delete attachment
     * 
     * @return boolean
     */
    public function delete()
    {
        
    }
    
    /**
     * Move file
     * 
     * @param string $source absolute filepath
     * @param string $target absolute filepath
     * 
     * @return boolean
     * FIXME do we deal only with uploaded files?  
     */
    protected function moveFile($source, $target)
    {
        $retval = false;
        
        if(!$this->encrypted && file_exists($target)) {
            $retval = true;
        } else {
            if(is_uploaded_file($source)) {
                $retval = move_uploaded_file($source, $target);
            } else {
                $retval = rename($source, $target);
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
