<?php

/**
 * Bookmark Formatter.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-10-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Bookmark;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Link;
use phpMyFAQ\Strings;
use phpMyFAQ\User\CurrentUser;

readonly class BookmarkFormatter
{
    public function __construct(
        private Configuration $configuration,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * Format a single bookmark DB object into the array used by the UI.
     *
     * Returns null if the bookmark cannot be resolved (missing FAQ, invalid id, ...).
     *
     * @param object $bookmark
     * @return array{url:string,title:string,id:int,answer:string}|null
     */
    public function format(object $bookmark): ?array
    {
        if (!isset($bookmark->faqid)) {
            return null;
        }

        $faqId = (int) $bookmark->faqid;
        if ($faqId <= 0) {
            return null;
        }

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);
        $faq = new Faq($this->configuration);
        $faq->setUser($currentUser)->setGroups($currentGroups);
        $faq->getFaq($faqId);
        $faqData = $faq->faqRecord;

        if (!isset($faqData['id'])) {
            return null;
        }

        $category = new Category($this->configuration);
        $categoryId = $category->getCategoryIdFromFaq((int) $faqData['id']);

        $url = strtr('base:index.php?action=faq&id=id:&cat=cat:&artlang=lang:', [
            'base:' => $this->configuration->getDefaultUrl(),
            'id:' => (string) (int) $faqData['id'],
            'cat:' => (string) $categoryId,
            'lang:' => (string) ($faqData['lang'] ?? ''),
        ]);

        $link = new Link($url, $this->configuration);
        $title = (string) ($faqData['title'] ?? '');
        $link->text = Strings::htmlentities($title);
        $link->itemTitle = $link->text;
        $link->tooltip = $link->text;

        return [
            'url' => $link->toString(),
            'title' => htmlspecialchars_decode($title),
            'id' => (int) $faqData['id'],
            'answer' => (string) ($faqData['content'] ?? ''),
        ];
    }
}
