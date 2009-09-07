<?php
/**
 * Main helper class for phpMyFAQ
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Helper
 * @license    MPL
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-09-07
 * @version    SVN: $Id$
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
 * PMF_Helper
 * 
 * @package    phpMyFAQ
 * @subpackage PMF_Helper
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-09-07
 * @copyright  2009 phpMyFAQ Team
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
     * Category Setter
     * 
     * @return void
     */
    protected function setCategory(PMF_Category $Category)
    {
        $this->Category = $Category;
    }
    
    /**
     * Tagging Setter
     * 
     * @return void
     */
    protected function setTags(PMF_Tags $Tags)
    {
        $this->Tags = $Tags;
    }
    
    /**
     * Template Setter
     * 
     * @var PMF_Template
     */
    protected function setTemplate(PMF_Template $Template)
    {
        $this->Template = $Template;
    }
}