<?php
/**
 * Helper class for phpMyFAQ search
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
     * @param PMF_Configuration $config
     *
     * @return PMF_Helper_Search
     */
    private function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
        $this->pmfLang = $this->getTranslations();
    }
    
    /**
     * Returns the single instance
     *
     * @access static
     * @return PMF_Helper_Search
     */
    public static function getInstance(PMF_Configuration $config)
    {
        if (null == self::$instance) {
            $className = __CLASS__;
            self::$instance = new $className($config);
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
        return sprintf(
            '<a class="searchplugin" href="#" onclick="window.external.AddSearchProvider(\'%s\'); return false;">%s</a>',
                $this->_config->get('main.referenceURL') . '/opensearch.php',
            $this->translation['opensearch_plugin_install']
        );
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
        $confPerPage  = $this->_config->get('records.numberOfRecordsPerPage');
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
                    urlencode($this->searchterm)
                );

                $oLink       = new PMF_Link($currentUrl, $this->_config);
                $oLink->text = $oLink->itemTitle = $oLink->tooltip = $question;
                
                $html .= sprintf(
                    "<li><strong>%s</strong>: %s<br /><div class=\"searchpreview\"><strong>%s</strong> %s...</div><br /></li>\n",
                    $categoryName,
                    $oLink->toHtmlAnchor(),
                    $this->translation['msgSearchContent'],
                    $answerPreview
                );
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
        $confPerPage  = $this->_config->get('records.numberOfRecordsPerPage');
        $numOfResults = $resultSet->getNumberOfResults();
        
        if (0 < $numOfResults) {
            $i = 0;
            foreach ($resultSet->getResultset() as $result) {
                
                if ($i > $confPerPage) {
                    continue;
                }
                
                // Build the link to the faq record
                $currentUrl = sprintf(
                    'index.php?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $result->category_id,
                    $result->id,
                    $result->lang
                );
                
                $html .= sprintf(
                    '<label for="%d"><input id="%d" type="radio" name="faqURL" value="%s"> %s</label><br />',
                    $result->id,
                    $result->id,
                    $currentUrl, 
                    $result->question
                );
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
        $confPerPage  = $this->_config->get('records.numberOfRecordsPerPage');
        $numOfResults = $resultSet->getNumberOfResults();
        
        $totalPages = ceil($numOfResults / $confPerPage);
        $lastPage   = $currentPage * $confPerPage;
        $firstPage  = $lastPage - $confPerPage;
        if ($lastPage > $numOfResults) {
            $lastPage = $numOfResults;
        }

        if (0 < $numOfResults) {

            $html .= sprintf(
                "<p>%s</p>\n",
                $this->plurals->GetMsg('plmsgSearchAmount', $numOfResults)
            );
            
            if (1 < $totalPages) {
                $html .= sprintf(
                    "<p><strong>%s%d %s %s</strong></p>\n",
                    $this->translation['msgPage'],
                    $currentPage,
                    $this->translation['msgVoteFrom'],
                    $this->plurals->GetMsg('plmsgPagesTotal',$totalPages)
                );
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

                // Set language for current category to fetch the correct category name
                $this->Category->setLanguage($result->lang);

                $categoryInfo  = $this->Category->getCategoriesFromArticle($result->id);
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
                    urlencode($searchterm)
                );

                $oLink       = new PMF_Link($currentUrl, $this->_config);
                $oLink->text = $question;
                $oLink->itemTitle = $oLink->tooltip = $result->question;

                $html .= "<li>";
                $html .= sprintf("<strong>%s</strong>: %s<br />",
                    $categoryInfo[0]['name'],
                    $oLink->toHtmlAnchor()
                );
                $html .= sprintf(
                    "<div class=\"searchpreview\"><strong>%s</strong> %s...</div><br />\n",
                    $this->translation['msgSearchContent'],
                    $answerPreview
                );
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
     * @param PMF_Search_Resultset $resultSet
     * @param integer              $recordId
     *
     * @return string
     */
    public function renderRelatedFaqs(PMF_Search_Resultset $resultSet, $recordId)
    {
        $html         = '';
        $numOfResults = $resultSet->getNumberOfResults();

        if ($numOfResults > 0) {

            $html   .= '<ul>';
            $counter = 0;
            foreach ($resultSet->getResultset() as $result) {
                if ($counter >= 5) {
                    continue;
                }
                if ($recordId == $result->id) {
                    continue;
                }
                $counter++;

                $url = sprintf(
                    '%s?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    PMF_Link::getSystemRelativeUri(),
                    $result->category_id,
                    $result->id,
                    $result->lang
                );
                $oLink             = new PMF_Link($url, $this->_config);
                $oLink->itemTitle  = $result->question;
                $oLink->text       = $result->question;
                $oLink->tooltip    = $result->question;
                $html .= '<li>' . $oLink->toHtmlAnchor() . '</li>';
            }
            $html .= '</ul>';
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
                $html .= sprintf(
                    '<li><a href="?search=%s&submit=Search&action=search">%s</a> (%dx)</li>',
                    urlencode($searchItem['searchterm']),
                    $searchItem['searchterm'],
                    $searchItem['number']
                );
            }
        }
        
        return $html . '</ul>';
    }
}