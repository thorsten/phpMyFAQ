<?php
/**
 * Helper class for phpMyFAQ search
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
 * @package   PMF_Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-07
 */

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
class PMF_Helper_Search extends PMF_Helper 
{
    /**
     * Instance
     * 
     * @var PMF_Helper_Search
     */
    private static $instance = null;
    
    /**
     * Language
     * 
     * @var PMF_Language
     */
    private $language = null;
    
    /**
     * PMF_Pagination object
     * 
     * @var PMF_Pagination
     */
    private $pagination = null;
    
    /**
     * Constructor
     * 
     * @return PMF_Helper_Search
     */
    private function __construct()
    {
        $this->pmfLang = $this->getTranslations();
    }
    
    /**
     * Returns the single instance
     *
     * @access static
     * @return PMF_Helper_Search
     */
    public static function getInstance()
    {
        if (null == self::$instance) {
            $className = __CLASS__;
            self::$instance = new $className();
        }
        return self::$instance;
    }
   
    /**
     * __clone() Magic method to prevent cloning
     * 
     * @return void
     */
    private function __clone()
    {
    }
    
    /**
     * PMF_Language setter
     * 
     * @param PMF_Language $language PMF_Language
     */
    public function setLanguage(PMF_Language $language)
    {
        $this->language = $language;
    }
    
    /**
     * PMF_Pagination setter
     * 
     * @param PMF_Pagination $pagination PMF_Pagination
     */
    public function setPagination(PMF_Pagination $pagination)
    {
        $this->pagination = $pagination;
    }
    
    /**
     * Renders the OpenSearchLink
     * 
     * @return string
     */
    public function renderOpenSearchLink()
    {
        return sprintf('<a class="searchplugin" href="#" onclick="window.external.AddSearchProvider(\'%s/opensearch.php\');">%s</a>',
            PMF_Link::getSystemUri('/index.php'),
            $this->translation['opensearch_plugin_install']);
    }
    
    /**
     * Renders the result page for Instant Response
     * 
     * @param PMF_Search_Resultset $resultSet PMF_Search_Resultset object
     * 
     * @return string
     */
    public function renderInstantResponseResult(PMF_Search_Resultset $resultSet)
    {
        
    }
    
    /**
     * Renders the result page for the main search page
     * 
     * @param PMF_Search_Resultset $resultSet PMF_Search_Resultset object
     * 
     * @return string
     */
    public function renderSearchResult(PMF_Search_Resultset $resultSet)
    {
        $html        = '';
        $confPerPage = PMF_Configuration::getInstance()->get('main.numberOfRecordsPerPage');
        
        /*
        $html .= '<p>' . $this->plurals->GetMsg('plmsgSearchAmount', $num);
        
        $html .= '</p>';
        */
        
        
        $html .= $this->pagination->render();
        
        return $html;
    }
    
    /**
     * Renders the list of the most popular search terms
     * 
     * @param array $mostPopularSearches Array with popular search terms
     * 
     * @return string
     */
    public function renderMostPopularSearches(Array $mostPopularSearches)
    {
        $html = '<ul class="mostpupularsearcheslist">';
        
        foreach ($mostPopularSearches as $searchItem) {
            if (PMF_String::strlen($searchItem['searchterm']) > 0) {
                $html .= sprintf('<li><a href="?search=%s&submit=Search&action=search">%s</a> (%dx)</li>',
                    urlencode($searchItem['searchterm']),
                    $searchItem['searchterm'],
                    $searchItem['number']);
            }
        }
        
        return $html . '</ul>';
    }
    
}