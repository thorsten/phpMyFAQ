<?php
/**
 * Main helper class for phpMyFAQ
 * 
 * PHP version 5.2
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
 * @package   PMF_Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-07
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Helper
 * 
 * @category  phpMyFAQ
 * @package   PMF_Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-07
 */
abstract class PMF_Helper
{
    /**
     * Category class
     * 
     * @var PMF_Category
     */
    protected $Category = null;
    
    /**
     * Tagging class
     * 
     * @var PMF_Tags
     */
    protected $Tags = null;
    
    /**
     * Template class
     * 
     * @var PMF_Template
     */
    protected $Template = null;
    
    /**
     * Plurals class
     * 
     * @var PMF_Language_Plurals
     */
    protected $plurals = null;
    
    /**
     * phpMyFAQ's session ID
     * 
     * @var onteger
     */
    protected $sessionId = null;
    
    /**
     * Array with all translations
     * 
     * @var array
     */
    protected $translation = array();

    /**
     * Category Setter
     * 
     * @param PMF_Category $Category PMF_Category object
     * 
     * @return void
     */
    public function setCategory(PMF_Category $Category)
    {
        $this->Category = $Category;
    }
    
    /**
     * Tagging Setter
     * 
     * @param PMF_Tags $Tags PMF_Tags object
     * 
     * @return void
     */
    public function setTags(PMF_Tags $Tags)
    {
        $this->Tags = $Tags;
    }
    
    /**
     * Template Setter
     * 
     * @param PMF_Template $Template PMF_Template object
     * 
     * @var PMF_Template
     */
    public function setTemplate(PMF_Template $Template)
    {
        $this->Template = $Template;
    }
    
    /**
     * Plurals setter
     * 
     * @param PMF_Language_Plurals $plurals PMF_Language_Plurals object
     */
    public function setPlurals(PMF_Language_Plurals $plurals)
    {
        $this->plurals = $plurals;
    }
    
    /**
     * Session ID setter
     * 
     * @param integer $sid Session id
     */
    public function setSessionId($sid)
    {
        $this->sessionId = $sid;
    }
    
    /**
     * Loads all translation strings
     * 
     * @return void
     */
    public function getTranslations()
    {
        global $PMF_LANG;
        
        $this->translation = $PMF_LANG;
    }
}