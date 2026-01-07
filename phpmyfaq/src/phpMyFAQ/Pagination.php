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

use phpMyFAQ\Pagination\PaginationTemplates;
use phpMyFAQ\Pagination\UrlConfig;

/**
 * Class Pagination
 */
class Pagination
{
    private const string TPL_VAR_LINK_URL = '{LINK_URL}';
    private const string TPL_VAR_LINK_TEXT = '{LINK_TEXT}';
    private const string TPL_VAR_LAYOUT_CONTENT = '{LAYOUT_CONTENT}';

    private int $currentPage;

    public string $baseUrl {
        set {
            $this->baseUrl = $value;
            $this->currentPage = $this->getCurrentPageFromUrl($value);
        }
    }

    public int $total {
        set => $this->total = max(0, $value);
    }

    public int $perPage {
        set => $this->perPage = max(1, $value);
    }

    public int $adjacent {
        set => $this->adjacent = max(1, $value);
    }

    public function __construct(
        string $baseUrl = '',
        int $total = 0,
        int $perPage = 10,
        public readonly PaginationTemplates $templates = new PaginationTemplates(),
        public readonly UrlConfig $urlConfig = new UrlConfig(),
        int $adjacent = 4,
    ) {
        $this->adjacent = $adjacent;
        $this->perPage = $perPage;
        $this->total = $total;
        $this->baseUrl = $baseUrl;
    }

    protected function getCurrentPageFromUrl(string $url): int
    {
        $page = 1;

        if ($url !== '' && $url !== '0') {
            $match = [];
            if (Strings::preg_match('/[?&]' . $this->urlConfig->pageParamName . '=(\d+)/', $url, $match) !== 0) {
                $page = $match[1] ?? $page;
            }
        }

        return (int) $page;
    }

    public function render(): string
    {
        $pages = (int) ceil($this->total / $this->perPage);
        $content = $this->renderPageNumbers($pages);
        $content = $this->addNavigationButtons($content, $pages);

        return $this->renderLayout(implode(separator: '&nbsp;&nbsp;', array: $content));
    }

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

    protected function shouldSkipBeforeCurrentPage(int $page, int $adjacent): bool
    {
        return $page > $this->adjacent && $page < ($this->currentPage - $adjacent);
    }

    protected function shouldSkipAfterCurrentPage(int $page, int $adjacent, int $totalPages): bool
    {
        return $page > ($this->currentPage + $adjacent) && $page <= ($totalPages - $this->adjacent);
    }

    protected function renderPageLink(int $page): string
    {
        $link = $this->renderUrl($this->baseUrl, $page);
        $template = $page === $this->currentPage ? $this->templates->currentPage : $this->templates->link;

        return $this->renderLink($template, $link, $page);
    }

    protected function addNavigationButtons(array $content, int $pages): array
    {
        if (1 < $this->currentPage) {
            array_unshift($content, $this->renderLink(
                $this->templates->prevPage,
                $this->renderUrl($this->baseUrl, $this->currentPage - 1),
                $this->currentPage - 1,
            ));
            array_unshift($content, $this->renderLink(
                $this->templates->firstPage,
                $this->renderUrl($this->baseUrl, page: 1),
                linkText: 1,
            ));
        }

        if ($pages > $this->currentPage) {
            $content[] = $this->renderLink(
                $this->templates->nextPage,
                $this->renderUrl($this->baseUrl, $this->currentPage + 1),
                $this->currentPage + 1,
            );
            $content[] = $this->renderLink(
                $this->templates->lastPage,
                $this->renderUrl($this->baseUrl, $pages),
                $pages,
            );
        }

        return $content;
    }

    protected function renderUrl(string $url = '', int $page = 1): string
    {
        if ($url === '') {
            return sprintf($this->urlConfig->rewriteUrl, $page);
        }

        $urlToParse = '%s%s%s=%d';
        $cleanedUrl = Strings::preg_replace(
            ['/[?&](amp;|)' . $this->urlConfig->pageParamName . '=(\d+)/'],
            replacement: '',
            subject: $url,
        );
        $separator = str_contains($cleanedUrl, needle: '?') ? '&' : '?';
        return sprintf($urlToParse, $cleanedUrl, $separator, $this->urlConfig->pageParamName, $page);
    }

    protected function renderLink(string $tpl, string $url, string|int|float $linkText): string
    {
        $search = [self::TPL_VAR_LINK_URL, self::TPL_VAR_LINK_TEXT];
        $replace = [$url, $linkText];

        return str_replace($search, $replace, $tpl);
    }

    protected function renderLayout(string $content): string
    {
        return str_replace(self::TPL_VAR_LAYOUT_CONTENT, $content, $this->templates->layout);
    }
}
