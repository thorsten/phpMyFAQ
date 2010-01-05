<?php
/**
 * Abstract class for all PMF_Category_* classes
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
 * @package   PMF_Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-04
 */

/**
 * PMF_Category_Abstract
 * 
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-04
 */
abstract class PMF_Category_Abstract
{
    /**
     * Database object
     * 
     * @var PMF_DB_Driver
     */
    protected $db = null;
    
    /**
     * Language
     *
     * @var string
     */
    protected $language = null;
    
    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        $this->db = PMF_Db::getInstance();
    }

    /**
     * Returns the current language
     * 
     * @return string
     */
    public function getLanguage ()
    {
        return $this->language;
    }
    
    /**
     * Sets the current language
     * 
     * @param string $language
     */
    public function setLanguage ($language)
    {
        $this->language = $language;
    }
}