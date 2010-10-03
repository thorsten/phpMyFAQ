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
class PMF_Attachment_Filesystem_File_Vanilla extends PMF_Attachment_Filesystem_File
{   
    /**
     * Chunk size read/write operations will deal with
     * (in bytes)
     * 
     * @var integer
     */
    const chunkSize = 512;
    
    /**
     * @see PMF_Attachment_Filesystem_File#getChunk()
     */
    public function getChunk()
    {
        return fread($this->handle, self::chunkSize);
    }
    
    /**
     * @see PMF_Attachment_Filesystem_File#putChunk($chunk)
     */
    public function putChunk($chunk)
    {
        return fwrite($this->handle, $chunk);
    }
    
    /**
     * @see inc/PMF_Attachment/Filesystem/PMF_Attachment_Filesystem_Entry#copyTo($entry)
     */
    public function copyTo($target)
    {
        $retval = false;

        $doSimple = is_string($target) ||
                    $target instanceof PMF_Attachment_Filesystem_File_Vanilla;
        
        if ($doSimple) {
        /**
         * If the target is a string or vanilla object, just move
         * it the simplest way we can. 
         */
            $retval = $this->copyToSimple((string)$target);
        } else {
            $target->setMode(self::MODE_WRITE);
            while (!$this->eof()) {
                $target->putChunk($this->getChunk());
            }
            
            $retval = true;
        }
        
        return $retval;
    }
}