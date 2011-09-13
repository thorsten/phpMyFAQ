<?php
/**
 * Pagination handler class
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
 * @package   PMF_Pagination
 * @author    Anatoliy Belsky <ab@php.net>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-27
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Pagination
 *
 * @category  phpMyFAQ
 * @package   PMF_Pagination
 * @author    Anatoliy Belsky <ab@php.net>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-27
 */
class PMF_Pagination
{
    /**
     * Template vars
     */
    const TPL_VAR_LINK_URL       = '{LINK_URL}';
    const TPL_VAR_LINK_TEXT      = '{LINK_TEXT}';
    const TPL_VAR_LAYOUT_CONTENT = '{LAYOUT_CONTENT}';

    /**
     * Base url used for links
     * 
     * @var string
     */
    protected $baseUrl = '';
    
    /**
     * Total items count
     * 
     * @var integer
     */
    protected $total = 0;
    
    /**
     * Items per page count
     * 
     * @var integer
     */
    protected $perPage = 0;
    
    /**
     * Number of adjacent links
     * 
     * @var integer
     */
    protected $adjacents = 4;
    
    /**
     * Default link template. 
     * Possible variables are {LINK}, {TITLE}, {TEXT}
     * 
     * @var string
     */
    protected $linkTpl = '<a href="{LINK_URL}">{LINK_TEXT}</a>';
    
    /**
     * Current page link template
     * 
     * @var string
     */
    protected $currentPageLinkTpl = '{LINK_TEXT}';
    
    /**
     * Next page link template
     * 
     * @var string
     */
    protected $nextPageLinkTpl = '<a href="{LINK_URL}">&gt;</a>';
    
    /**
     * Previous page link template
     * 
     * @var string
     */
    protected $prevPageLinkTpl = '<a href="{LINK_URL}">&lt;</a>';
    
    /**
     * First page link template
     * 
     * @var string
     */
    protected $firstPageLinkTpl = '<a href="{LINK_URL}">&lt;&lt;</a>';
    
    /**
     * Last page link template
     * 
     * @var string
     */
    protected $lastPageLinkTpl = '<a href="{LINK_URL}">&gt;&gt;</a>';
    
    /**
     * Layout template
     * 
     * @var string
     */
    protected $layoutTpl = '<div>{LAYOUT_CONTENT}</div>';

    /**
     * Current page index
     * 
     * @var integer
     */
    protected $currentPage = 0;
    
    /**
     * Param name to associate the page numbers to
     * 
     * @var string
     */
    protected $pageParamName = 'page';

    /**
     * SEO name
     *
     * @var string
     */
    protected $seoName = '';
    
    /**
     * Constructor
     *
     * @param array $options initialization options,
     * possible options:
     * - baseUrl (default "")
     * - total
     * - perPage
     * - linkTpl
     * - currentPageLinkTpl
     * - nextPageLinkTpl
     * - prevPageLinkTpl
     * - firstPageLinkTpl
     * - lastPageLinkTpl
     * - layoutTpl
     * - pageParamName (default "page")
     * 
     * NOTE we read in the current page from the baseUrl, so if it contains
     *      no pageParamName, first page is asumed
     * 
     * @return null
     * 
     * TODO do some checks to params (eq. arguments count for templates etc)
     */
    public function __construct(Array $options = null)
    {
        if (isset($options['baseUrl'])) {
            $this->baseUrl = $options['baseUrl'];
        }
       
        if (isset($options['total'])) {
            $this->total = $options['total'];
        }

        if (isset($options['perPage'])) {
            $this->perPage = $options['perPage'];
        }
       
        if (isset($options['linkTpl'])) {
            $this->linkTpl = $options['linkTpl'];
        }
       
        if (isset($options['currentPageLinkTpl'])) {
            $this->currentPageLinkTpl = $options['currentPageLinkTpl'];
        }
       
        if (isset($options['nextPageLinkTpl'])) {
            $this->nextPageLinkTpl = $options['nextPageLinkTpl'];
        }
       
        if (isset($options['prevPageLinkTpl'])) {
            $this->prevPageLinkTpl = $options['prevPageLinkTpl'];
        }
       
        if (isset($options['firstPageLinkTpl'])) {
           $this->firstPageLinkTpl = $options['firstPageLinkTpl'];
        }
        
        if (isset($options['lastPageLinkTpl'])) {
           $this->lastPageLinkTpl = $options['lastPageLinkTpl'];
        }
        
        if (isset($options['layoutTpl'])) {
           $this->layoutTpl = $options['layoutTpl'];
        }
        
        if (isset($options['pageParamName'])) {
           $this->pageParamName = $options['pageParamName'];
        }

        if (isset($options['seoName'])) {
           $this->seoName = $options['seoName'];
        }
        
        /**
         * Let this call to be last cuz it 
         * needs some options to be set before
         */
        $this->currentPage = $this->getCurrentPageFromUrl($this->baseUrl);
        
    }
    
    /**
     * Returns the current page URL
     * 
     * @param string $url URL
     * 
     * @return integer
     */
    protected function getCurrentPageFromUrl($url)
    {
        $page = 1;
        
        if (!empty($url)) {
            $match = array();
            if (PMF_String::preg_match('$&(amp;|)' . $this->pageParamName . '=(\d+)$', $url, $match)) {
                $page = isset($match[2]) ? $match[2] : $page;
            }
        }

        return $page;
    }
    
    /**
     * Render full pagination string
     * 
     * @return string
     */
    public function render()
    {
        $content   = array();
        $pages     = ceil($this->total / $this->perPage);
        $adjacents = floor($this->adjacents / 2) >= 1 ? floor($this->adjacents / 2) : 1;
        
        for ($page = 1; $page <= $pages; $page++) {
            
            if ($page > $this->adjacents && $page < $this->currentPage - $adjacents) {
                $content[] = '&hellip;';
                $page      = $this->currentPage - $adjacents - 1;
                continue;
            }
            
            if ($page > $this->currentPage + $adjacents && $page <= $pages - $this->adjacents) {
                $content[] = '&hellip;';
                $page      = $pages - $this->adjacents;
                continue;
            }
            
            $link = $this->renderUrl($this->baseUrl, $page);
            
            if ($page == $this->currentPage) {
                $template = $this->currentPageLinkTpl;
            } else {
                $template = $this->linkTpl;
            }
            
            $content[] = $this->renderLink($template, $link, $page);
        }
        
        if (1 < $this->currentPage) {
            array_unshift($content,
                          $this->renderLink($this->prevPageLinkTpl,
                                            $this->renderUrl($this->baseUrl, $this->currentPage - 1),
                                            $this->currentPage - 1));
            array_unshift($content,
                          $this->renderLink($this->firstPageLinkTpl,
                                            $this->renderUrl($this->baseUrl, 1),
                                            1));
        }
        
        if ($page - 1 > $this->currentPage) {
            array_push($content,
                       $this->renderLink($this->nextPageLinkTpl,
                                         $this->renderUrl($this->baseUrl, $this->currentPage + 1),
                                         $this->currentPage + 1));
            array_push($content,
                       $this->renderLink($this->lastPageLinkTpl,
                                         $this->renderUrl($this->baseUrl, $page - 1),
                                         $page - 1));
        }
        
        return $this->renderLayout(implode('&nbsp;&nbsp;', $content));
    }
    
    /**
     * Render url for a given page
     * 
     * @param string  $url  url
     * @param integer $page page number
     * 
     * @return string
     */
    protected function renderUrl($url, $page)
    {
        $cleanedUrl = PMF_String::preg_replace(
            array('$&(amp;|)' . $this->pageParamName . '=(\d+)$'),
            '',
            $url
        );
        
        $url             = sprintf('%s&amp;%s=%d', $cleanedUrl, $this->pageParamName, $page);
        $link            = new PMF_Link($url);
        $link->itemTitle = $this->seoName;

        return $link->toString();
    }
    
    /**
     * Render a link
     * 
     * @param string $tpl      link template
     * @param string $url      url value for template container
     * @param string $linkText text value for template container
     * 
     * @return string
     */
    protected function renderLink($tpl, $url, $linkText)
    {
        $search  = array(self::TPL_VAR_LINK_URL, self::TPL_VAR_LINK_TEXT);
        $replace = array($url, $linkText);
        
        return str_replace($search, $replace, $tpl);
    }
    
    /**
     * Render the whole pagination layout
     * 
     * @param string $content layout contents
     * 
     * @return string
     */
    protected function renderLayout($content)
    {
        return str_replace(self::TPL_VAR_LAYOUT_CONTENT, $content, $this->layoutTpl);
    }
}