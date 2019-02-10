<?php

/**
 * Pagination handler class.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <ab@php.net>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-27
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Pagination.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <ab@php.net>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-27
 */
class PMF_Pagination
{
    /**
     * Template vars.
     */
    const TPL_VAR_LINK_URL = '{LINK_URL}';
    const TPL_VAR_LINK_TEXT = '{LINK_TEXT}';
    const TPL_VAR_LAYOUT_CONTENT = '{LAYOUT_CONTENT}';

    /**
     * Base url used for links.
     *
     * @var string
     */
    protected $baseUrl = '';

    /**
     * Total items count.
     *
     * @var int
     */
    protected $total = 0;

    /**
     * Items per page count.
     *
     * @var int
     */
    protected $perPage = 0;

    /**
     * Number of adjacent links.
     *
     * @var int
     */
    protected $adjacents = 4;

    /**
     * Default link template. 
     * Possible variables are {LINK}, {TITLE}, {TEXT}.
     *
     * @var string
     */
    protected $linkTpl = '<li><a href="{LINK_URL}">{LINK_TEXT}</a></li>';

    /**
     * Current page link template.
     *
     * @var string
     */
    protected $currentPageLinkTpl = '<li class="active"><a href="{LINK_URL}">{LINK_TEXT}</a></li>';

    /**
     * Next page link template.
     *
     * @var string
     */
    protected $nextPageLinkTpl = '<li><a href="{LINK_URL}">&rarr;</a></li>';

    /**
     * Previous page link template.
     *
     * @var string
     */
    protected $prevPageLinkTpl = '<li><a href="{LINK_URL}">&larr;</a></li>';

    /**
     * First page link template.
     *
     * @var string
     */
    protected $firstPageLinkTpl = '<li><a href="{LINK_URL}">&#8676;</a></li>';

    /**
     * Last page link template.
     *
     * @var string
     */
    protected $lastPageLinkTpl = '<li><a href="{LINK_URL}">&#8677;</a></li>';

    /**
     * Layout template.
     *
     * @var string
     */
    protected $layoutTpl = '<div class="text-center"><ul class="pagination">{LAYOUT_CONTENT}</ul></div>';

    /**
     * Current page index.
     *
     * @var int
     */
    protected $currentPage = 0;

    /**
     * Param name to associate the page numbers to.
     *
     * @var string
     */
    protected $pageParamName = 'page';

    /**
     * SEO name.
     *
     * @var string
     */
    protected $seoName = '';

    /**
     * Use rewritten URLs without GET variables.
     *
     * @var bool
     */
    protected $useRewrite = false;

    /**
     * Rewritten URL format for page param.
     *
     * @var string
     */
    protected $rewriteUrl = '';

    /**
     * @var PMF_Configuration
     */
    private $_config;

    /**
     * Constructor.
     *
     * We read in the current page from the baseUrl, so if it contains
     * no pageParamName, first page is asumed
     *
     * @param PMF_Configuration $config
     * @param array             $options initialization options,
     *                                   possible options:
     *                                   - baseUrl (default "")
     *                                   - total
     *                                   - perPage
     *                                   - linkTpl
     *                                   - currentPageLinkTpl
     *                                   - nextPageLinkTpl
     *                                   - prevPageLinkTpl
     *                                   - firstPageLinkTpl
     *                                   - lastPageLinkTpl
     *                                   - layoutTpl
     *                                   - pageParamName (default "page")
     *                                   - useRewrite
     *
     * @return PMF_Pagination
     */
    public function __construct(PMF_Configuration $config, Array $options = null)
    {
        $this->_config = $config;

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

        if (isset($options['useRewrite']) && isset($options['rewriteUrl'])) {
            $this->useRewrite = $options['useRewrite'];
            $this->rewriteUrl = $options['rewriteUrl'];
        }

        // Let this call to be last cuz it  needs some options to be set before
        $this->currentPage = $this->getCurrentPageFromUrl($this->baseUrl);
    }

    /**
     * Returns the current page URL.
     *
     * @param string $url URL
     *
     * @return int
     */
    protected function getCurrentPageFromUrl($url)
    {
        $page = 1;

        if (!empty($url)) {
            $match = [];
            if (PMF_String::preg_match('$&(amp;|)'.$this->pageParamName.'=(\d+)$', $url, $match)) {
                $page = isset($match[2]) ? $match[2] : $page;
            }
        }

        return $page;
    }

    /**
     * Render full pagination string.
     *
     * @return string
     */
    public function render()
    {
        $content = [];
        $pages = ceil($this->total / $this->perPage);
        $adjacents = floor($this->adjacents / 2) >= 1 ? floor($this->adjacents / 2) : 1;

        for ($page = 1; $page <= $pages; ++$page) {
            if ($page > $this->adjacents && $page < $this->currentPage - $adjacents) {
                $content[] = '<li class="disabled"><a>&hellip;</a></li>';
                $page = $this->currentPage - $adjacents - 1;
                continue;
            }

            if ($page > $this->currentPage + $adjacents && $page <= $pages - $this->adjacents) {
                $content[] = '<li class="disabled"><a>&hellip;</a></li>';
                $page = $pages - $this->adjacents;
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
            array_unshift(
                $content,
                $this->renderLink(
                    $this->prevPageLinkTpl,
                    $this->renderUrl($this->baseUrl, $this->currentPage - 1),
                    $this->currentPage - 1
                )
            );
            array_unshift(
                $content,
                $this->renderLink(
                    $this->firstPageLinkTpl,
                    $this->renderUrl($this->baseUrl, 1),
                    1
                )
            );
        }

        if ($page - 1 > $this->currentPage) {
            array_push(
                $content,
                $this->renderLink(
                    $this->nextPageLinkTpl,
                    $this->renderUrl($this->baseUrl, $this->currentPage + 1),
                    $this->currentPage + 1
                )
            );
            array_push(
                $content,
                $this->renderLink(
                    $this->lastPageLinkTpl,
                    $this->renderUrl($this->baseUrl, $page - 1),
                    $page - 1
                )
            );
        }

        return $this->renderLayout(implode('&nbsp;&nbsp;', $content));
    }

    /**
     * Render url for a given page.
     *
     * @param string $url  url
     * @param int    $page page number
     *
     * @return string
     */
    protected function renderUrl($url, $page)
    {
        if ($this->useRewrite) {
            $url = sprintf($this->rewriteUrl, $page);
        } else {
            $cleanedUrl = PMF_String::preg_replace(array('$&(amp;|)'.$this->pageParamName.'=(\d+)$'), '', $url);
            $url = sprintf('%s&amp;%s=%d', $cleanedUrl, $this->pageParamName, $page);
        }

        return $url;
    }

    /**
     * Render a link.
     *
     * @param string $tpl      link template
     * @param string $url      url value for template container
     * @param string $linkText text value for template container
     *
     * @return string
     */
    protected function renderLink($tpl, $url, $linkText)
    {
        $search = array(self::TPL_VAR_LINK_URL, self::TPL_VAR_LINK_TEXT);
        $replace = array($url, $linkText);

        return str_replace($search, $replace, $tpl);
    }

    /**
     * Render the whole pagination layout.
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
