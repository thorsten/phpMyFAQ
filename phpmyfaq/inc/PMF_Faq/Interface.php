<?php
/**
 * Interface for all PMF_Faq_* classes
 *
 * PHP Version 5.2.0
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
 * @package   PMF_Faq
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
 * @package   PMF_Faq
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-12-28
 */
interface PMF_Faq_Interface
{
	/**
	 * Creates a new entry
	 *
	 * @param integer $id   ID
	 * @param array   $data Array of data
	 * 
	 * @return boolean
	 * @throws PMF_Faq_Exception
	 */
	public function create($id, Array $data);

    /**
     * Updates an existing entry
     *
     * @param integer $id   ID
     * @param array   $data Array of data
     * 
     * @return boolean
     * @throws PMF_Faq_Exception
     */
	public function update($id, Array $data);

    /**
     * Deletes an entry
     *
     * @param integer $id ID
     * 
     * @return boolean
     * @throws PMF_Faq_Exception
     */
	public function delete($id);
	
    /**
     * Fetches one entry
     *
     * @param integer $id ID
     * 
     * @return array
     * @throws PMF_Faq_Exception
     */
	public function fetch($id);
	
    /**
     * Fetches all entries, if parameter = null, otherwise all from the given
     * array like array(1, 2, 3)
     *
     * @param integer $id ID
     * 
     * @return array
     * @throws PMF_Faq_Exception
     */
	public function fetchAll(Array $ids = null);

    /**
     * Sets the language
     *
     * @param string $language Language
     * 
     * @return boolean
     * @throws PMF_Faq_Exception
     */
	public function setLanguage($language);
	
    /**
     * Returns the current language
     *
     * @return string
     * @throws PMF_Faq_Exception
     */
	public function getLanguage();
}