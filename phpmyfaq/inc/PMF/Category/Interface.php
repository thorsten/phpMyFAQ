<?php
/**
 * Interface for all PMF_Category_* classes
 *
 * PHP Version 5.3.0
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-12-28
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Faq_Interface
 *
 * @category  phpMyFAQ
 * @package   Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-12-28
 */
interface PMF_Category_Interface
{
    /**
     * Creates a new entry
     *
     * @param integer $id   ID
     * @param array   $data Array of data
     *
     * @return boolean
     * @throws PMF_Category_Exception
     */
    public function create($id, Array $data);

    /**
     * Updates an existing entry
     *
     * @param integer $id   ID
     * @param array   $data Array of data
     *
     * @return boolean
     * @throws PMF_Category_Exception
     */
    public function update($id, Array $data);

    /**
     * Deletes an entry
     *
     * @param integer $id ID
     *
     * @return boolean
     * @throws PMF_Category_Exception
     */
    public function delete($id);
    
    /**
     * Sets the language
     *
     * @param string $language Language
     *
     * @return boolean
     * @throws PMF_Category_Exception
     */
    public function setLanguage($language);
    
    /**
     * Returns the current language
     *
     * @return string
     * @throws PMF_Category_Exception
     */
    public function getLanguage();
}