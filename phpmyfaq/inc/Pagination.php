<?php
/**
 * Pagination handler class
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Pagination
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-09-27
 * @copyright  2004-2009 phpMyFAQ Team
 * @version    SVN: $Id: Pagination.php,v 1.56 2008-01-26 01:02:56 thorstenr Exp $
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
 * PMF_Pagination
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Pagination
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2007-09-27
 * @copyright  2004-2009 phpMyFAQ Team
 * @version    SVN: $Id: Pagination.php,v 1.56 2008-01-26 01:02:56 thorstenr Exp $
 */
class PMF_Pagination
{
    /**
     * Template vars
     */
    const TPL_VAR_LINK_URL        = '{LINK_URL}';
    const TPL_VAR_LINK_TEXT       = '{LINK_TEXT}';
    const TPL_VAR_LAYOUT_CONTENT  = '{LAYOUT_CONTENT}';
    
    /**
     * Url style variants
     * - URL_STYLE_DEFAULT is a normal query string style
     * - URL_STYLE_REWRITE is the style of host/param0/val0/param1/val1
     */
    const URL_STYLE_DEFAULT = 0;
    const URL_STYLE_REWRITE = 1;
    
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
    protected $currentPageLinkTpl = '';
    
    /**
     * Next page link template
     * 
     * @var string
     */
    protected $nextPageLinkTpl = '';
    
    /**
     * Previous page link template
     * 
     * @var string
     */
    protected $prevPageLinkTpl = '';
    
    /**
     * First page link template
     * 
     * @var string
     */
    protected $firstPageLinkTpl = '';
    
    /**
     * Last page link template
     * 
     * @var string
     */
    protected $lastPageLinkTpl = '';
    
    /**
     * Layout template
     * 
     * @var string
     */
    protected $layoutTpl = '<div>{LAYOUT_CONTENT}</div>';
    
    /**
     * Url style 
     * 
     * @var string
     */
    protected $urlStyle = self::URL_STYLE_DEFAULT;
    
    /**
     * Param name to associate the page numbers to
     * 
     * @var string
     */
    protected $pageParamName = 'page';
    
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
     * - urlStyle
     * - pageParamName (default "page")
     * 
     * @return null
     * 
     * TODO do some checks to params (eq. arguments count for templates etc)
     */
    public function __construct($options = null)
    {
        if(isset($options['baseUrl'])) {
            $this->baseUrl = $options['baseUrl'];
        }
       
        if(isset($options['total'])) {
            $this->total = $options['total'];
        }

        if(isset($options['perPage'])) {
            $this->perPage = $options['perPage'];
        }
       
        if(isset($options['linkTpl'])) {
            $this->linkTpl = $options['linkTpl'];
        }
       
        if(isset($options['currentPageLinkTpl'])) {
            $this->currentPageLinkTpl = $options['currentPageLinkTpl'];
        }
       
        if(isset($options['nextPageLinkTpl'])) {
            $this->nextPageLinkTpl = $options['nextPageLinkTpl'];
        }
       
        if(isset($options['prevPageLinkTpl'])) {
            $this->prevPageLinkTpl = $options['prevPageLinkTpl'];
        }
       
        if(isset($options['firstPageLinkTpl'])) {
           $this->firstPageLinkTpl = $options['firstPageLinkTpl'];
        }
        
        if(isset($options['lastPageLinkTpl'])) {
           $this->lastPageLinkTpl = $options['lastPageLinkTpl'];
        }
        
        if(isset($options['layoutTpl'])) {
           $this->layoutTpl = $options['layoutTpl'];
        }
        
        if(isset($options['urlStyle'])) {
           $this->urlStyle = $options['urlStyle'];
        }
        
        if(isset($options['pageParamName'])) {
           $this->pageParamName = $options['pageParamName'];
        }
    }
    
    /**
     * Render full pagination string
     * 
     * @return string
     */
    public function render()
    {
        $content = '';
        
        $page = 1;
        for($i = 0; $i < $this->total; $i += $this->perPage, $page++) {
            $link = $this->renderUrl($this->baseUrl, $page);
            
            $content .= $this->renderLink($this->linkTpl, $link, $page);
        }
        
        return $this->renderLayout($content);
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
        switch($this->urlStyle) {
            case self::URL_STYLE_REWRITE:
                $cleanedUrl = PMF_String::preg_replace(array('$/' . $this->pageParamName . '/(\d+)$',
                                                             '$//$'),
                                                       "",
                                                       $this->baseUrl);
                $url = "$cleanedLink/{$this->pageParamName}/$page";
            break;
                
            case self::URL_STYLE_DEFAULT:
            default:
                $cleanedUrl = PMF_String::preg_replace(array('$&(amp;?)' . $this->pageParamName . '=(\d+)$'),
                                                       "",
                                                       $this->baseUrl);
                $url = "$cleanedUrl&amp;{$this->pageParamName}=$page";
            break;
        }
        
        return $url;
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
        return str_replace(self::TPL_VAR_LAYOUT_CONTENT,
                           $content,
                           $this->layoutTpl);
    }
}
 