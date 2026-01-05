<?php

/**
 * The News class for phpMyFAQ news.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-06-25
 */

declare(strict_types=1);

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Entity\NewsMessage;
use phpMyFAQ\Link\Util\TitleSlugifier;
use phpMyFAQ\News\NewsRepository;
use phpMyFAQ\News\NewsRepositoryInterface;
use stdClass;

/**
 * Class News
 *
 * @package phpMyFAQ
 */
readonly class News
{
    private NewsRepositoryInterface $newsRepository;

    /**
     * Constructor.
     */
    public function __construct(
        private Configuration $configuration,
        ?NewsRepositoryInterface $newsRepository = null,
    ) {
        $this->newsRepository = $newsRepository ?? new NewsRepository($this->configuration);
    }

    /**
     * Returns the current news as an array.
     *
     * @param bool $showArchive Show archived news
     * @param bool $active Show active news
     * @return stdClass[]
     * @throws Exception
     */
    public function getAll(bool $showArchive = false, bool $active = true): array
    {
        $output = [];
        $date = new Date($this->configuration);
        $language = $this->configuration->getLanguage()->getLanguage();
        $limit = $showArchive
            ? null
            : ($active ? (int) $this->configuration->get(item: 'records.numberOfShownNewsEntries') : null);

        foreach ($this->newsRepository->getLatest($language, $active, $limit) as $row) {
            $entry = new stdClass();
            $entry->url = sprintf(
                '%snews/%d/%s/%s.html',
                $this->configuration->getDefaultUrl(),
                $row->id,
                $row->lang,
                TitleSlugifier::slug($row->header),
            );
            $entry->header = $row->header;
            $entry->content = strip_tags((string) $row->artikel);
            $entry->date = $date->format($row->datum);
            $output[] = $entry;
        }

        return $output;
    }

    /**
     * Return the latest news data.
     *
     * @param bool $showArchive Show archived news
     * @param bool $active Show active news
     * @param bool $forceConfLimit Force to limit in configuration
     * @return array<int, array>
     */
    public function getLatestData(bool $showArchive = false, bool $active = true, bool $forceConfLimit = false): array
    {
        $news = [];
        $language = $this->configuration->getLanguage()->getLanguage();
        $configuredLimit = (int) $this->configuration->get(item: 'records.numberOfShownNewsEntries');
        $limit = null;
        if ($configuredLimit > 0) {
            if ($showArchive) {
                $limit = null; // show all
            } elseif ($forceConfLimit) {
                $limit = $configuredLimit; // force limit
            } else {
                // default behavior: same as original logic for active front-end listing
                $limit = $configuredLimit;
            }
        }

        foreach ($this->newsRepository->getLatest($language, $active, $limit) as $row) {
            $url = sprintf(
                '%snews/%d/%s/%s.html',
                $this->configuration->getDefaultUrl(),
                $row->id,
                $row->lang,
                TitleSlugifier::slug($row->header),
            );
            $item = [
                'id' => (int) $row->id,
                'lang' => $row->lang,
                'date' => Date::createIsoDate($row->datum, DATE_ATOM),
                'header' => $row->header,
                'content' => $row->artikel,
                'authorName' => $row->author_name,
                'authorEmail' => $row->author_email,
                'active' => 'y' === $row->active,
                'allowComments' => 'y' === $row->comment,
                'link' => $row->link,
                'linkTitle' => $row->linktitel,
                'target' => $row->target,
                'url' => $url,
            ];
            $news[] = $item;
        }

        return $news;
    }

    /**
     * Fetches all news headers.
     */
    public function getHeader(): array
    {
        $headers = [];
        $language = $this->configuration->getLanguage()->getLanguage();
        foreach ($this->newsRepository->getHeaders($language) as $header) {
            $headers[] = [
                'id' => $header->id,
                'lang' => $header->lang,
                'header' => $header->header,
                'date' => Date::createIsoDate($header->datum),
                'active' => $header->active,
            ];
        }

        return $headers;
    }

    /**
     * Fetches a news entry identified by its ID.
     *
     * @param int  $newsId ID of news
     * @param bool $admin Is admin
     */
    public function get(int $newsId, bool $admin = false): array
    {
        $row = $this->newsRepository->getById($newsId, $this->configuration->getLanguage()->getLanguage());
        if (!$row) {
            return [];
        }

        $content = $row->artikel;
        $active = 'y' === $row->active;
        $allowComments = 'y' === $row->comment;
        if (!$admin && !$active) {
            $content = Translation::get(key: 'err_inactiveNews');
        }

        return [
            'id' => $row->id,
            'lang' => $row->lang,
            'date' => Date::createIsoDate($row->datum),
            'header' => $row->header,
            'content' => $content,
            'authorName' => $row->author_name,
            'authorEmail' => $row->author_email,
            'active' => $active,
            'allowComments' => $allowComments,
            'link' => $row->link,
            'linkTitle' => $row->linktitel,
            'target' => $row->target,
        ];
    }

    /**
     * Adds a new news entry.
     *
     * @param NewsMessage $newsMessage NewsMessage object with news data
     */
    public function create(NewsMessage $newsMessage): bool
    {
        return $this->newsRepository->insert($newsMessage);
    }

    /**
     * Updates a new news entry identified by its ID.
     *
     * @param NewsMessage $newsMessage NewsMessage object with news data
     */
    public function update(NewsMessage $newsMessage): bool
    {
        return $this->newsRepository->update($newsMessage);
    }

    /**
     * Deletes a news entry identified by its ID.
     *
     * @param int $newsId News ID
     * @todo   check if there are comments attached to the deleted news
     */
    public function delete(int $newsId): bool
    {
        return $this->newsRepository->delete($newsId, $this->configuration->getLanguage()->getLanguage());
    }

    /**
     * Activates/Deactivates a news message
     *
     * @param int $newsId News ID
     */
    public function activate(int $newsId): bool
    {
        return $this->newsRepository->activate($newsId, status: true);
    }

    public function deactivate(int $newsId): bool
    {
        return $this->newsRepository->activate($newsId, status: false);
    }
}
