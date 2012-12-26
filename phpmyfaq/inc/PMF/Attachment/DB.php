<?php
/**
 * Attachment handler class for files stored in database
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Attachment
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2013 phpMyFAQ Team
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
 * @package   Attachment
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
