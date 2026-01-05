<?php

/**
 * News Service
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-02
 */

declare(strict_types=1);

namespace phpMyFAQ\News;

use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Glossary;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\News;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

/**
 * Service class for news-related business logic.
 */
final class NewsService
{
    private News $news;

    private Glossary $glossary;

    private FaqHelper $faqHelper;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly CurrentUser $currentUser,
    ) {
        $this->news = new News($this->configuration);
        $this->glossary = new Glossary($this->configuration);
        $this->faqHelper = new FaqHelper($this->configuration);
    }

    /**
     * Gets and processes news by ID.
     *
     * @return array<string, mixed>
     */
    public function getProcessedNews(int $newsId): array
    {
        $news = $this->news->get($newsId);

        // Process content with glossary
        $news['processedContent'] = $this->processContent($news['content'] ?? '');
        $news['processedHeader'] = $this->processContent($news['header'] ?? '');

        // Add an information link if available
        if ((string) $news['link'] !== '') {
            $news['processedContent'] .= $this->buildInformationLink(
                $news['link'],
                $news['target'],
                $news['linkTitle'],
            );
        }

        return $news;
    }

    /**
     * Processes content with glossary and cleanup.
     */
    private function processContent(string $content): string
    {
        $content = $this->glossary->insertItemsIntoContent($content);
        return $this->faqHelper->cleanUpContent($content);
    }

    /**
     * Builds the information link HTML.
     */
    private function buildInformationLink(string $link, string $target, string $linkTitle): string
    {
        return sprintf(
            '</p><p>%s<a href="%s" target="%s">%s</a>',
            Translation::get(key: 'msgInfo'),
            Strings::htmlentities($link),
            $target,
            Strings::htmlentities($linkTitle),
        );
    }

    /**
     * Checks if a user can edit news and returns an edit link.
     */
    public function getEditLink(int $newsId): string
    {
        if ($this->currentUser->perm->hasPermission(
            $this->currentUser->getUserId(),
            PermissionType::NEWS_EDIT->value,
        )) {
            return sprintf(
                '<a href="./admin/news/edit/%d">%s</a>',
                $newsId,
                Translation::get(key: 'ad_menu_news_edit'),
            );
        }

        return '';
    }

    /**
     * Gets a comment message based on permissions and news settings.
     */
    public function getCommentMessage(array $news): string
    {
        if (
            -1 === $this->currentUser->getUserId() && !$this->configuration->get('records.allowCommentsForGuests')
            || !$news['active']
            || !$news['allowComments']
        ) {
            return Translation::get(key: 'msgWriteNoComment');
        }

        return sprintf(
            '<a href="#" data-bs-toggle="modal" data-bs-target="#pmf-modal-add-comment">%s</a>',
            Translation::get(key: 'newsWriteComment'),
        );
    }

    /**
     * Formats the news date.
     */
    public function formatNewsDate(array $news): string
    {
        if (!$news['active']) {
            return '';
        }

        $date = new Date($this->configuration);
        return sprintf(
            '%s<span id="newsLastUpd">%s</span>',
            Translation::get(key: 'msgLastUpdateArticle'),
            $date->format($news['date']),
        );
    }

    /**
     * Gets author information.
     */
    public function getAuthorInfo(array $news): string
    {
        if (!$news['active']) {
            return '';
        }

        return Translation::get(key: 'msgAuthor') . ': ' . $news['authorName'];
    }
}
