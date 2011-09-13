<?php
/**
 * File handler class
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
 * @copyright 2009-2011 phpMyFAQ Team
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