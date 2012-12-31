<?php
/**
 * Unencrypted file handler class
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
 * @copyright 2009-2013 phpMyFAQ Team
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
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-08-21
 */
class PMF_Attachment_Filesystem_File_Encrypted extends PMF_Attachment_Filesystem_File
{
    /**
     * Chunk delimiter
     * 
     * @var string
     */
    const chunkDelimiter = "ฒૐᥤ";
    
    /**
     * AES instance
     * 
     * @var object
     */
    protected $aes;
    
    public function __construct($filepath, $mode, $key)
    {
        $this->aes = new Crypt_AES();
        $this->aes->setKey($key);
        
        parent::__construct($filepath, $mode);
    }
    
    /**
     * Chunk here is an encrypted piece of file
     * until delimiter.
     * 
     * @see PMF_Attachment_Filesystem_File#getChunk()
     */
    public function getChunk()
    {
        $readEnd = false;
        $chunk   = '';
        $chunkDelimLen = strlen(self::chunkDelimiter);
        
        while(!$readEnd && !$this->eof()) {
            $chunk .= fread($this->handle, 1);
            $readEnd = self::chunkDelimiter == substr($chunk, -$chunkDelimLen);
        }
        
        $chunk = substr($chunk, 0, -$chunkDelimLen);
        return empty($chunk) ? '' : $this->aes->decrypt($chunk);
    }
    
    /**
     * Either file is encrypted
     * 
     * @return boolean
     */
    public function isEncrypted()
    {
        return true;
    }
    
    /**
     * @see PMF_Attachment_Filesystem_File#putChunk()
     */
    public function putChunk($chunk)
    {
        $content = $this->aes->encrypt($chunk) . self::chunkDelimiter;
        
        return fwrite($this->handle, $content);
    }
    
    /**
     * @see inc/PMF_Attachment/Filesystem/PMF_Attachment_Filesystem_Entry#copyTo($entry)
     */
    public function copyTo($target)
    {
        $retval = false;

        if(is_string($target)) {
            $target = new PMF_Attachment_Filesystem_File_Vanilla($target, self::MODE_WRITE);
        } else {
            $target->setMode(self::MODE_WRITE);
        }
        
        if($target->isOk()) {
            while(!$this->eof()) {
                $target->putChunk($this->getChunk());
            }
            
            $retval = true;        
        }

        return $retval;
    }
}