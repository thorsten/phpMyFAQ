<?php

/**
 * Handler for queued FAQ indexing.
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
 * @since     2026-02-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Queue\Handler;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Instance\Search\Elasticsearch;
use phpMyFAQ\Queue\Message\IndexFaqMessage;
use RuntimeException;

final readonly class IndexFaqHandler
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    public function __invoke(IndexFaqMessage $message): void
    {
        if (!$this->configuration->isElasticsearchActive()) {
            throw new RuntimeException('Elasticsearch is not configured');
        }

        $faq = new Faq($this->configuration);
        $faq->getFaq($message->faqId);

        if (
            $faq->faqRecord['id'] === $message->faqId
            && $faq->faqRecord['active'] === 'yes'
            && $faq->faqRecord['content'] !== ''
        ) {
            $category = new Category($this->configuration);
            $categoryId = $category->getCategoryIdFromFaq($message->faqId);

            $elasticsearch = new Elasticsearch($this->configuration);
            $elasticsearch->index([
                'id' => $faq->faqRecord['id'],
                'lang' => $message->language !== '' ? $message->language : $faq->faqRecord['lang'],
                'solution_id' => $faq->faqRecord['solution_id'],
                'question' => $faq->faqRecord['title'],
                'answer' => $faq->faqRecord['content'],
                'keywords' => $faq->faqRecord['keywords'],
                'category_id' => $categoryId,
            ]);
        }
    }
}
