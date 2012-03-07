<?php
/**
 * File handler class
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Attachment
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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