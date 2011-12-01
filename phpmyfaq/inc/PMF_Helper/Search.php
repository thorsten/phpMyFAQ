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
 * @copyright 2009-2011 phpMyFAQ Team
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
     * Search term
     * 
     * @var string
     */
    private $searchterm = '';
    
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
     * 
     * @return void
     */
    public function setLanguage(PMF_Language $language)
    {
        $this->language = $language;
    }
    
    /**
     * PMF_Pagination setter
     * 
     * @param PMF_Pagination $pagination PMF_Pagination
     * 
     * @return void
     */
    public function setPagination(PMF_Pagination $pagination)
    {
        $this->pagination = $pagination;
    }
    
    /**
     * Searchterm setter
     * 
     * @param string $searchterm Searchterm
     * 
     * @return void
     */
    public function setSearchterm($searchterm)
    {
        $this->searchterm = $searchterm;
    }
    
    /**
     * Renders the OpenSearchLink
     * 
     * @return string
     */
    public function renderOpenSearchLink()
    {
        return sprintf('<a class="searchplugin" href="#" onclick="window.external.AddSearchProvider(\'%s\'); return false;">%s</a>',
            PMF_Link::getSystemUri('/index.php') . '/opensearch.php',
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
        $html         = '';
        $confPerPage  = PMF_Configuration::getInstance()->get('records.numberOfRecordsPerPage');
        $numOfResults = $resultSet->getNumberOfResults();
        
        if (0 < $numOfResults) {
            
            $html .= sprintf("<p>%s", $this->plurals->GetMsg('plmsgSearchAmount', $numOfResults));
            $html .= sprintf($this->translation['msgInstantResponseMaxRecords'], $confPerPage);
            $html .= "<ul class=\"phpmyfaq_ul\">\n";
            
            $i = 0;
            foreach ($resultSet->getResultset() as $result) {
                
                if ($i > $confPerPage) {
                    continue;
                }
                
                $categoryName  = $this->Category->getPath($result->category_id);
                $question      = PMF_Utils::chopString($result->question, 15);
                $answerPreview = PMF_Utils::chopString(strip_tags($result->answer), 25);
                
                // Build the link to the faq record
                $currentUrl = sprintf('%s?%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s&amp;highlight=%s',
                    PMF_Link::getSystemRelativeUri('ajaxresponse.php').'index.php',
                    $this->sessionId,
                    $result->category_id,
                    $result->id,
                    $result->lang,
                    urlencode($this->searchterm));

                $oLink       = new PMF_Link($currentUrl);
                $oLink->text = $oLink->itemTitle = $oLink->tooltip = $question;
                
                $html .= sprintf("<li><strong>%s</strong>: %s<br /><div class=\"searchpreview\"><strong>%s</strong> %s...</div><br /></li>\n",
                    $categoryName,
                    $oLink->toHtmlAnchor(),
                    $this->translation['msgSearchContent'],
                    $answerPreview);
                $i++;
            }
            
            $html .= "</ul>\n";
            
        } else {
            $html = $this->translation['err_noArticles'];
        }
        
        return $html;
    }
    
    /**
     * Renders the result page for Instant Response
     * 
     * @param PMF_Search_Resultset $resultSet PMF_Search_Resultset object
     * 
     * @return string
     */
    public function renderAdminSuggestionResult(PMF_Search_Resultset $resultSet)
    {
        $html         = '';
        $confPerPage  = PMF_Configuration::getInstance()->get('records.numberOfRecordsPerPage');
        $numOfResults = $resultSet->getNumberOfResults();
        
        if (0 < $numOfResults) {
            $i = 0;
            foreach ($resultSet->getResultset() as $result) {
                
                if ($i > $confPerPage) {
                    continue;
                }
                
                // Build the link to the faq record
                $currentUrl = sprintf('index.php?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $result->category_id,
                    $result->id,
                    $result->lang);
                
                $html .= sprintf('<label for="%d"><input id="%d" type="radio" name="faqURL" value="%s"> %s</label><br />',
                    $result->id,
                    $result->id,
                    $currentUrl, 
                    $result->question);
                $i++;
            }
            
        } else {
            $html = $this->translation['err_noArticles'];
        }
        
        return $html;
    }
    
    /**
     * Renders the result page for the main search page
     * 
     * @param PMF_Search_Resultset $resultSet   PMF_Search_Resultset object
     * @param integer              $currentPage Current page number
     * 
     * @return string
     */
    public function renderSearchResult(PMF_Search_Resultset $resultSet, $currentPage)
    {
        $html         = '';
        $confPerPage  = PMF_Configuration::getInstance()->get('records.numberOfRecordsPerPage');
        $numOfResults = $resultSet->getNumberOfResults();
        
        $totalPages = ceil($numOfResults / $confPerPage);
        $lastPage   = $currentPage * $confPerPage;
        $firstPage  = $lastPage - $confPerPage;
        if ($lastPage > $numOfResults) {
            $lastPage = $numOfResults;
        }

        if (0 < $numOfResults) {
            
            $html .= sprintf("<p>%s</p>\n", 
                $this->plurals->GetMsg('plmsgSearchAmount', $numOfResults));
            
            if (1 < $totalPages) {
                $html .= sprintf("<p><strong>%s%d %s %s</strong></p>\n",
                    $this->translation['msgPage'],
                    $currentPage,
                    $this->translation['msgVoteFrom'],
                    $this->plurals->GetMsg('plmsgPagesTotal',$totalPages));
            }
            
            $html .= "<ul class=\"phpmyfaq_ul\">\n";

            $counter = $displayedCounter = 0;
            foreach ($resultSet->getResultset() as $result) {
                if ($displayedCounter >= $confPerPage) {
                    continue;
                }
                $counter++;
                if ($counter <= $firstPage) {
                    continue;
                }
                $displayedCounter++;
                
                $categoryName  = $this->Category->getPath($result->category_id);
                $question      = PMF_Utils::chopString($result->question, 15);
                $answerPreview = PMF_Utils::chopString(strip_tags($result->answer), 25);
                $searchterm    = str_replace(
                                    array('^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']', '"'), '', 
                                    $this->searchterm
                                    );
                $searchterm    = preg_quote($searchterm, '/');
                $searchItems   = explode(' ', $searchterm);
                
                if (PMF_String::strlen($searchItems[0]) > 1) {
                    foreach ($searchItems as $item) {
                         if (PMF_String::strlen($item) > 2) {
                             $question      = PMF_Utils::setHighlightedString($question, $item);
                             $answerPreview = PMF_Utils::setHighlightedString($answerPreview, $item);
                         }
                    }
                }
                
                // Build the link to the faq record
                $currentUrl = sprintf(
                    '%s?%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s&amp;highlight=%s',
                    PMF_Link::getSystemRelativeUri(),
                    $this->sessionId,
                    $result->category_id,
                    $result->id,
                    $result->lang,
                    urlencode($searchterm));

                $oLink       = new PMF_Link($currentUrl);
                $oLink->text = $question;
                $oLink->itemTitle = $oLink->tooltip = $result->question;

                $html .= "<li>";
                $html .= sprintf("<strong>%s</strong>: %s<br />",
                    $categoryName,
                    $oLink->toHtmlAnchor());
                $html .= sprintf(
                    "<div class=\"searchpreview\"><strong>%s</strong> %s...</div><br />\n",
                    $this->translation['msgSearchContent'],
                    $answerPreview);
                $html .= "</li>";
            }
            
            $html .= "</ul>\n";
            
            if (1 < $totalPages) {
                $html .= $this->pagination->render();
            }
            
        } else {
            $html = $this->translation['err_noArticles'];
        }
        
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
        $html = '<ul class="mostpopularsearcheslist">';
        
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