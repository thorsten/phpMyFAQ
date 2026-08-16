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

use Closure;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\FaqStatus;
use phpMyFAQ\Faq;
use phpMyFAQ\Instance\Search\Elasticsearch;
use phpMyFAQ\Queue\Message\IndexFaqMessage;
use RuntimeException;

final readonly class IndexFaqHandler
{
    public function __construct(
        private Configuration $configuration,
        private ?Closure $faqFactory = null,
        private ?Closure $categoryFactory = null,
        private ?Closure $elasticsearchFactory = null,
    ) {
    }

    /**
     * The solution ID Faq::getFaq() stamps on its placeholder for records that do not
     * exist or that the requester has no permission for (see RecordVisibility).
     */
    private const int ACCESS_DENIED_SOLUTION_ID = 42;

    public function __invoke(IndexFaqMessage $message): void
    {
        if (!$this->configuration->isElasticsearchActive()) {
            throw new RuntimeException('Elasticsearch is not configured');
        }

        $faq = null;
        if ($this->faqFactory instanceof Closure) {
            $createdFaq = ($this->faqFactory)();
            if ($createdFaq instanceof Faq) {
                $faq = $createdFaq;
            }
        }

        $faq ??= new Faq($this->configuration);
        $faq->getFaq($message->faqId);

        // A FAQ that left the published state must also leave the index, mirroring the
        // synchronous admin API behaviour — re-indexing only on publish would strand
        // stale documents for unpublished content. Missing records are excluded: getFaq()
        // stamps its not-found placeholder with the access-denied sentinel solution ID,
        // and deleting that document would hit an unrelated index entry.
        if (
            $faq->faqRecord['id'] === $message->faqId
            && (int) $faq->faqRecord['solution_id'] !== self::ACCESS_DENIED_SOLUTION_ID
            && $faq->faqRecord['status'] !== FaqStatus::Published->value
        ) {
            $this->createElasticsearch()->delete((int) $faq->faqRecord['solution_id']);
            return;
        }

        if (
            $faq->faqRecord['id'] === $message->faqId
            && $faq->faqRecord['status'] === FaqStatus::Published->value
            && $faq->faqRecord['content'] !== ''
        ) {
            $category = null;
            if ($this->categoryFactory instanceof Closure) {
                $createdCategory = ($this->categoryFactory)();
                if ($createdCategory instanceof Category) {
                    $category = $createdCategory;
                }
            }

            $category ??= new Category($this->configuration);
            $categoryId = $category->getCategoryIdFromFaq($message->faqId);

            $this->createElasticsearch()->index([
                'id' => (int) $faq->faqRecord['id'],
                'lang' => $message->language !== '' ? $message->language : (string) $faq->faqRecord['lang'],
                'solution_id' => (int) $faq->faqRecord['solution_id'],
                'question' => (string) $faq->faqRecord['title'],
                'answer' => (string) $faq->faqRecord['content'],
                'keywords' => (string) $faq->faqRecord['keywords'],
                'category_id' => $categoryId,
            ]);
        }
    }

    private function createElasticsearch(): Elasticsearch
    {
        if ($this->elasticsearchFactory instanceof Closure) {
            $createdElasticsearch = ($this->elasticsearchFactory)();
            if ($createdElasticsearch instanceof Elasticsearch) {
                return $createdElasticsearch;
            }
        }

        return new Elasticsearch($this->configuration);
    }
}
