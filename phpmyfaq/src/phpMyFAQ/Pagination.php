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
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-27
 */

declare(strict_types=1);

namespace phpMyFAQ;

/**
 * Class Pagination
 *
 * @package phpMyFAQ
 */
class Pagination
{
    /** Template vars. */
    private const string TPL_VAR_LINK_URL = '{LINK_URL}';

    private const string TPL_VAR_LINK_TEXT = '{LINK_TEXT}';

    private const string TPL_VAR_LAYOUT_CONTENT = '{LAYOUT_CONTENT}';

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
     * Template configuration.
     */
    protected array $templates = [
        'link' => '<li class="page-item"><a class="page-link" href="{LINK_URL}">{LINK_TEXT}</a></li>',
        'currentPage' => '<li class="page-item active"><a class="page-link" href="{LINK_URL}">{LINK_TEXT}</a></li>',
        'nextPage' => '<li class="page-item"><a class="page-link" href="{LINK_URL}">&rarr;</a></li>',
        'prevPage' => '<li class="page-item"><a class="page-link" href="{LINK_URL}">&larr;</a></li>',
        'firstPage' => '<li class="page-item"><a class="page-link" href="{LINK_URL}">&#8676;</a></li>',
        'lastPage' => '<li class="page-item"><a class="page-link" href="{LINK_URL}">&#8677;</a></li>',
        'layout' => '<ul class="pagination justify-content-center">{LAYOUT_CONTENT}</ul>',
    ];

    /**
     * Current page index.
     */
    protected int $currentPage = 0;

    /**
     * URL configuration.
     */
    protected array $urlConfig = [
        'pageParamName' => 'page',
        'seoName' => '',
        'rewriteUrl' => '',
    ];

    /**
     * Constructor.
     * We read in the current page from the baseUrl, so if it contains
     * no pageParamName, the first page is assumed
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
     *                               pageParamName (default "page")
     */
    public function __construct(?array $options = null)
    {
        if (isset($options['baseUrl'])) {
            $this->baseUrl = $options['baseUrl'];
        }

        if (isset($options['total'])) {
            $this->total = $options['total'];
        }

        if (isset($options['perPage'])) {
            $this->perPage = (int) $options['perPage'];
        }

        if (isset($options['linkTpl'])) {
            $this->templates['link'] = $options['linkTpl'];
        }

        if (isset($options['currentPageLinkTpl'])) {
            $this->templates['currentPage'] = $options['currentPageLinkTpl'];
        }

        if (isset($options['nextPageLinkTpl'])) {
            $this->templates['nextPage'] = $options['nextPageLinkTpl'];
        }

        if (isset($options['prevPageLinkTpl'])) {
            $this->templates['prevPage'] = $options['prevPageLinkTpl'];
        }

        if (isset($options['firstPageLinkTpl'])) {
            $this->templates['firstPage'] = $options['firstPageLinkTpl'];
        }

        if (isset($options['lastPageLinkTpl'])) {
            $this->templates['lastPage'] = $options['lastPageLinkTpl'];
        }

        if (isset($options['layoutTpl'])) {
            $this->templates['layout'] = $options['layoutTpl'];
        }

        if (isset($options['pageParamName'])) {
            $this->urlConfig['pageParamName'] = $options['pageParamName'];
        }

        if (isset($options['seoName'])) {
            $this->urlConfig['seoName'] = $options['seoName'];
        }

        if (isset($options['rewriteUrl'])) {
            $this->urlConfig['rewriteUrl'] = $options['rewriteUrl'];
        }

        // Let this call to be last cuz it needs some options to be set before
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

        if ($url !== '' && $url !== '0') {
            $match = [];
            if (Strings::preg_match('/[?&]' . $this->urlConfig['pageParamName'] . '=(\d+)/', $url, $match) !== 0) {
                $page = $match[1] ?? $page;
            }
        }

        return (int) $page;
    }

    /**
     * Render full pagination string.
     */
    public function render(): string
    {
        $pages = (int) ceil($this->total / $this->perPage);
        $content = $this->renderPageNumbers($pages);
        $content = $this->addNavigationButtons($content, $pages);

        return $this->renderLayout(implode(separator: '&nbsp;&nbsp;', array: $content));
    }

    /**
     * Render page number links.
     *
     * @param int $pages Total number of pages
     * @return array<string> Array of rendered page links
     */
    protected function renderPageNumbers(int $pages): array
    {
        $content = [];
        $adjacent = (int) max(floor($this->adjacent / 2), 1);

        for ($page = 1; $page <= $pages; ++$page) {
            if ($this->shouldSkipBeforeCurrentPage($page, $adjacent)) {
                $content[] = '<li class="disabled"><a>&hellip;</a></li>';
                $page = $this->currentPage - $adjacent - 1;
                continue;
            }

            if ($this->shouldSkipAfterCurrentPage($page, $adjacent, $pages)) {
                $content[] = '<li class="disabled"><a>&hellip;</a></li>';
                $page = $pages - $this->adjacent;
                continue;
            }

            $content[] = $this->renderPageLink($page);
        }

        return $content;
    }

    /**
     * Check if pages before the current page should be skipped.
     */
    protected function shouldSkipBeforeCurrentPage(int $page, int $adjacent): bool
    {
        return $page > $this->adjacent && $page < ($this->currentPage - $adjacent);
    }

    /**
     * Check if pages after the current page should be skipped.
     */
    protected function shouldSkipAfterCurrentPage(int $page, int $adjacent, int $totalPages): bool
    {
        return $page > ($this->currentPage + $adjacent) && $page <= ($totalPages - $this->adjacent);
    }

    /**
     * Render a single-page link.
     */
    protected function renderPageLink(int $page): string
    {
        $link = $this->renderUrl($this->baseUrl, $page);
        $template = $page === $this->currentPage ? $this->templates['currentPage'] : $this->templates['link'];

        return $this->renderLink($template, $link, $page);
    }

    /**
     * Add first/prev/next/last navigation buttons.
     *
     * @param array<string> $content Array of page links
     * @param int $pages Total number of pages
     * @return array<string> Array with navigation buttons added
     */
    protected function addNavigationButtons(array $content, int $pages): array
    {
        if (1 < $this->currentPage) {
            array_unshift($content, $this->renderLink(
                $this->templates['prevPage'],
                $this->renderUrl($this->baseUrl, $this->currentPage - 1),
                $this->currentPage - 1,
            ));
            array_unshift($content, $this->renderLink(
                $this->templates['firstPage'],
                $this->renderUrl($this->baseUrl, page: 1),
                linkText: 1,
            ));
        }

        if ($pages > $this->currentPage) {
            $content[] = $this->renderLink(
                $this->templates['nextPage'],
                $this->renderUrl($this->baseUrl, $this->currentPage + 1),
                $this->currentPage + 1,
            );
            $content[] = $this->renderLink(
                $this->templates['lastPage'],
                $this->renderUrl($this->baseUrl, $pages),
                $pages,
            );
        }

        return $content;
    }

    /**
     * Render url for a given page.
     *
     * @param string $url url
     * @param int    $page page number
     */
    protected function renderUrl(string $url = '', int $page = 1): string
    {
        if ($url === '') {
            return sprintf($this->urlConfig['rewriteUrl'], $page);
        }

        $urlToParse = '%s%s%s=%d';
        $cleanedUrl = Strings::preg_replace(
            ['$&(amp;|)' . $this->urlConfig['pageParamName'] . '=(\d+)$'],
            replacement: '',
            subject: $url,
        );
        $separator = str_contains($cleanedUrl, needle: '?') ? '&' : '?';
        return sprintf($urlToParse, $cleanedUrl, $separator, $this->urlConfig['pageParamName'], $page);
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
        return str_replace(self::TPL_VAR_LAYOUT_CONTENT, $content, $this->templates['layout']);
    }
}
