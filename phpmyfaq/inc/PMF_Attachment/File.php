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
 * TODO refactor to move filesystem stuff in more specialized places
 */
class PMF_Attachment_File extends PMF_Attachment_Abstract implements PMF_Attachment_Interface
{   
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
        $fsHash = $this->mkVirtualHash();
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
     * 
     * TODO rollback if something went wrong
     */
    public function save($filepath)
    {
        $retval = false;
        
        if(file_exists($filepath)) {
            
            $this->realHash = md5_file($filepath);
            $this->filesize = filesize($filepath);
            $this->filename = basename($filepath);
            
            $this->saveMeta();
            
            $targetFile = $this->buildFilePath();
            
            if(null !== $this->id && $this->createSubDirs($targetFile)) {                
                if(!$this->linkedRecords()) {
                    $source = new PMF_Attachment_Filesystem_File_Vanilla($filepath);
                    $target = $this->getFile(PMF_Attachment_Filesystem_File::MODE_WRITE);
                    
                    $retval = $source->moveTo($target);
                } else {
                    $retval = true;
                }
                
                if($retval) {
                    $this->postUpdateMeta();
                } else {
                    /**
                     * File wasn't saved
                     */
                    $this->delete();
                    $retval = false;
                }
            }
        }
        
        return $retval;
    }
    
    /**
     * Delete attachment
     * 
     * @return null
     */
    public function delete()
    {
        /**
         * Won't delete the file if there are still some
         * records hanging on it
         */
        if(!$this->linkedRecords()) {
            $this->getFile()->delete();
        }
        
        $this->deleteMeta();
        
        $this->__destruct();
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
     * @param boolean $headers     if headers must be sent
     * @param string  $disposition diposition type (ignored if $headers false)
     * 
     * @return null
     */
    public function rawOut($headers = true, $disposition = 'attachment')
    {
        $file = $this->getFile();
        
        if($headers) {
            $disposition = 'attachment' == $disposition ? 'attachment' : 'inline';
            header('Content-Type: ' . $this->mimeType, true);
            header('Content-Length: ' . $this->filesize, true);
            header("Content-Disposition: $disposition; filename={$this->filename}" , true);
            header("Content-MD5: {$this->realHash}", true);
        }
        
        while(!$file->eof()) {
            echo $file->getChunk();   
        }
    }
    
    /**
     * Factory method to initialise the corresponding file object
     * 
     * @param string $mode filemode for file open
     * 
     * @return object
     */
    private function getFile($mode = PMF_Attachment_Filesystem_File::MODE_READ)
    {
        if($this->encrypted) {
            $file = new PMF_Attachment_Filesystem_File_Encrypted(
                            $this->buildFilePath(),
                            $mode,
                            $this->key
                            );
        } else {
            $file = new PMF_Attachment_Filesystem_File_Vanilla($this->buildFilePath());
        }
        
        return $file;
    }
}
