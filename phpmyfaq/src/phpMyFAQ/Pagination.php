<?php

/**
 * Pagination handler class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-27
 */

namespace phpMyFAQ;

/**
 * Class Pagination
 *
 * @package phpMyFAQ
 */
class Pagination
{
    /** Template vars. */
    private const TPL_VAR_LINK_URL = '{LINK_URL}';
    private const TPL_VAR_LINK_TEXT = '{LINK_TEXT}';
    private const TPL_VAR_LAYOUT_CONTENT = '{LAYOUT_CONTENT}';

    /**
     * Base url used for links.
     */
    protected string $baseUrl = '';

    /**
     * Total items count.
     */
    protected int $total = 0;

    /**
     * Items per page count.
     */
    protected int $perPage = 0;

    /**
     * Number of adjacent links.
     */
    protected int $adjacent = 4;

    /**
     * Default link template.
     * Possible variables are {LINK}, {TITLE}, {TEXT}.
     */
    protected string $linkTpl = '<li class="page-item"><a class="page-link" href="{LINK_URL}">{LINK_TEXT}</a></li>';

    /**
     * Current page link template.
     */
    protected string $currentPageLinkTpl =
        '<li class="page-item active"><a class="page-link" href="{LINK_URL}">{LINK_TEXT}</a></li>';

    /**
     * Next page link template.
     */
    protected string $nextPageLinkTpl = '<li class="page-item"><a class="page-link" href="{LINK_URL}">&rarr;</a></li>';

    /**
     * Previous page link template.
     */
    protected string $prevPageLinkTpl = '<li class="page-item"><a class="page-link" href="{LINK_URL}">&larr;</a></li>';

    /**
     * First page link template.
     */
    protected string $firstPageLinkTpl =
        '<li class="page-item"><a class="page-link" href="{LINK_URL}">&#8676;</a></li>';

    /**
     * Last page link template.
     */
    protected string $lastPageLinkTpl = '<li class="page-item"><a class="page-link" href="{LINK_URL}">&#8677;</a></li>';

    /**
     * Layout template.
     */
    protected string $layoutTpl = '<ul class="pagination justify-content-center">{LAYOUT_CONTENT}</ul>';

    /**
     * Current page index.
     */
    protected int $currentPage = 0;

    /**
     * Param name to associate the page numbers to.
     */
    protected string $pageParamName = 'page';

    /**
     * SEO name.
     */
    protected string $seoName = '';

    /**
     * Use rewritten URLs without GET variables.
     */
    protected bool $useRewrite = false;

    /**
     * Rewritten URL format for page param.
     */
    protected string $rewriteUrl = '';

    /**
     * Constructor.
     * We read in the current page from the baseUrl, so if it contains
     * no pageParamName, first page is assumed
     *
     * @param array<string|int|bool> $options initialization options,
     *                               possible options: -
     *                               baseUrl (default "") -
     *                               total - perPage -
     *                               linkTpl -
     *                               currentPageLinkTpl -
     *                               nextPageLinkTpl -
     *                               prevPageLinkTpl -
     *                               firstPageLinkTpl -
     *                               lastPageLinkTpl -
     *                               layoutTpl -
     *                               pageParamName (default
     *                               "page") - useRewrite
     */
    public function __construct(array $options = null)
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
     */
    protected function getCurrentPageFromUrl(string $url): int
    {
        $page = 1;

        if (!empty($url)) {
            $match = [];
            if (Strings::preg_match('$&(amp;|)' . $this->pageParamName . '=(\d+)$', $url, $match)) {
                $page = $match[2] ?? $page;
            }
        }

        return $page;
    }

    /**
     * Render full pagination string.
     */
    public function render(): string
    {
        $content = [];
        $pages = ceil($this->total / $this->perPage);
        $adjacents = max(floor($this->adjacent / 2), 1);

        for ($page = 1; $page <= $pages; ++$page) {
            if ($page > $this->adjacent && $page < $this->currentPage - $adjacents) {
                $content[] = '<li class="disabled"><a>&hellip;</a></li>';
                $page = $this->currentPage - $adjacents - 1;
                continue;
            }

            if ($page > $this->currentPage + $adjacents && $page <= $pages - $this->adjacent) {
                $content[] = '<li class="disabled"><a>&hellip;</a></li>';
                $page = $pages - $this->adjacent;
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
            $content[] = $this->renderLink(
                $this->nextPageLinkTpl,
                $this->renderUrl($this->baseUrl, $this->currentPage + 1),
                $this->currentPage + 1
            );
            $content[] = $this->renderLink(
                $this->lastPageLinkTpl,
                $this->renderUrl($this->baseUrl, $page - 1),
                $page - 1
            );
        }

        return $this->renderLayout(implode('&nbsp;&nbsp;', $content));
    }

    /**
     * Render url for a given page.
     *
     * @param string $url url
     * @param int    $page page number
     */
    protected function renderUrl(string $url, int $page): string
    {
        if ($this->useRewrite) {
            $url = sprintf($this->rewriteUrl, $page);
        } else {
            $cleanedUrl = Strings::preg_replace(['$&(amp;|)' . $this->pageParamName . '=(\d+)$'], '', $url);
            $url = sprintf('%s&amp;%s=%d', $cleanedUrl, $this->pageParamName, $page);
        }

        return $url;
    }

    /**
     * Render a link.
     *
     * @param string           $tpl link template
     * @param string           $url url value for template container
     * @param string|int|float $linkText text value for template container
     */
    protected function renderLink(string $tpl, string $url, string|int|float $linkText): string
    {
        $search = [self::TPL_VAR_LINK_URL, self::TPL_VAR_LINK_TEXT];
        $replace = [$url, $linkText];

        return str_replace($search, $replace, $tpl);
    }

    /**
     * Render the whole pagination layout.
     *
     * @param string $content layout contents
     */
    protected function renderLayout(string $content): string
    {
        return str_replace(self::TPL_VAR_LAYOUT_CONTENT, $content, $this->layoutTpl);
    }
}
