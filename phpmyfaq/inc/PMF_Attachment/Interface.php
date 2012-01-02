<?php
/**
 * Interface to create new attachment types
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
interface PMF_Attachment_Interface
{    
    /**
     * Save current attachment to the appropriate storage
     * 
     * @param string $filepath full path to the attachment file
     * 
     * @return boolean
     */
    function save($filepath);
    
    /**
     * Delete attachment
     * 
     * @return boolean
     */
    function delete();
    
    /**
     * Retrieve file contents into a variable
     * 
     * @return string
     */
    function get();
    
    /**
     * Output current file to stdout
     * 
     * @param boolean $headers     if headers must be sent
     * @param string  $disposition diposition type (ignored if $headers false)
     * 
     * @return null
     */
    function rawOut($headers = true, $disposition = 'attachment');
}
