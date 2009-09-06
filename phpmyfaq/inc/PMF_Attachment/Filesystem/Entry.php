<?php
/**
 * File handler class
 *
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id: File.php 4872 2009-09-06 10:54:06Z anatoliy $
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
 * PMF_Atachment_Filesystem_File
 * 
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id: File.php 4872 2009-09-06 10:54:06Z anatoliy $
 * @copyright  2009 phpMyFAQ Team
 */
abstract class PMF_Attachment_Filesystem_Entry
{
    /**
     * Path to the entry in the filesystem
     * 
     * @var string
     */
    protected $path;
    
    /**
     * This opened handle
     * 
     * @var resource
     */
    protected $handle;
    
    /**
     * Move file to another location
     * 
     * @param object|string $entry an entry to move to
     * 
     * @return boolean
     */
    public function moveTo($entry)
    {
        return $this->copyTo($entry) && $this->delete();
    }

    /**
     * Copy file to another location
     * 
     * @param object|string $entry an entry to copy to
     * 
     * @return boolean
     */
    abstract public function copyTo($entry);
    
    /**
     * Delete this file
     * 
     * @return boolean
     */
    abstract public function delete();
    
    /**
     * Either file is encrypted
     * 
     * @return boolean
     */
    public function isEncrypted()
    {
        return false;
    }
    
    /**
     * Return current filepath
     * 
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
    
    /**
     * Magic to use in string context
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->path;
    }
}