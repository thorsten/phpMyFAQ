<?php
/**
 * Attachment handler class for files stored in database
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
class PMF_Attachment_DB extends PMF_Attachment_Abstract implements PMF_Attachment_Interface
{
    
    /**
     * Constructor
     * 
     * @param int $id attachment id
     * 
     * @return null
     */
    public function __construct ($id = null)
    {
        // TODO implement this
        throw new PMF_Attachment_Exception('The database attachment storage type is not yet implemented');
    }
    
    /**
     * Delete attachment
     * 
     * @return boolean
     */
    function delete()
    {
        // TODO implement this
    }
    
    /**
     * Save current attachment to the appropriate storage
     * 
     * @return boolean
     */
    public function save()
    {
        // TODO implement this
    }
    
    /**
     * Retrieve file contents into a variable
     * 
     * @return string
     */
    public function get()
    {
        // TODO implement this
    }
    
    /**
     * Output current file to stdout
     * 
     * @return null
     */
    public function rawOut()
    {
        // TODO implement this
    }
}
