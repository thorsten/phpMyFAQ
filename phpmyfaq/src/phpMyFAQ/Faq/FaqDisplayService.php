<?php

/**
 * FAQ Display Service
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

namespace phpMyFAQ\Faq;

use Exception;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Category;
use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Faq;
use phpMyFAQ\Glossary;
use phpMyFAQ\Helper\AttachmentHelper;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Rating;
use phpMyFAQ\Relation;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;

/**
 * Service class for FAQ display business logic.
 */
final class FaqDisplayService
{
    private Glossary $glossary;

    private Tags $tags;

    private Relation $relation;

    private Rating $rating;

    private Comments $comments;

    private FaqHelper $faqHelper;

    private Permission $faqPermission;

    private AttachmentHelper $attachmentHelper;

    private MarkdownConverter $markdownConverter;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly CurrentUser $currentUser,
        private readonly array $currentGroups,
        private readonly Faq $faq,
        private readonly Category $category,
    ) {
        $this->glossary = new Glossary($this->configuration);
        $this->tags = new Tags($this->configuration);
        $this->tags->setUser($this->currentUser->getUserId())->setGroups($this->currentGroups);
        $this->relation = new Relation($this->configuration);
        $this->rating = new Rating($this->configuration);
        $this->comments = new Comments($this->configuration);
        $this->faqHelper = new FaqHelper($this->configuration);
        $this->faqPermission = new Permission($this->configuration);
        $this->attachmentHelper = new AttachmentHelper();

        // Setup Markdown converter
        $config = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ];
        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());

        $this->markdownConverter = new MarkdownConverter($environment);
    }

    /**
     * Load FAQ data by ID or solution ID
     */
    public function loadFaq(int $faqId, ?int $solutionId): int
    {
        if ($solutionId === null || $solutionId === 0) {
            $this->faq->getFaq($faqId);
        } else {
            $this->faq->getFaqBySolutionId($solutionId);
        }

        return (int) ($this->faq->faqRecord['id'] ?? $faqId);
    }

    /**
     * Process answer content (Markdown, cleanup, rewrite, glossary)
     */
    public function processAnswer(string $currentUrl, ?string $highlight): string
    {
        $question = $this->faq->getQuestion((int) $this->faq->faqRecord['id']);

        // Convert Markdown if enabled
        if ((bool) $this->configuration->get('main.enableMarkdownEditor')) {
            $answer = $this->markdownConverter->convert($this->faq->faqRecord['content'])->getContent();
        } else {
            $answer = $this->faq->faqRecord['content'];
        }

        // Cleanup and rewrite
        $answer = $this->faqHelper->cleanUpContent($answer);
        $answer = $this->faqHelper->rewriteUrlFragments($answer, $currentUrl);
        $answer = $this->faqHelper->convertOldInternalLinks($question, $answer);
        $answer = $this->glossary->insertItemsIntoContent($answer);

        // Apply highlighting if needed
        if ($this->shouldApplyHighlighting($highlight)) {
            $processedHighlight = $this->processHighlight($highlight);
            $searchItems = explode(' ', $processedHighlight);

            foreach ($searchItems as $searchItem) {
                if (Strings::strlen($searchItem) > 2) {
                    $answer = Utils::setHighlightedString($answer, $searchItem);
                }
            }
        }

        return $answer;
    }

    /**
     * Process question with highlighting
     */
    public function processQuestion(?string $highlight): string
    {
        $question = $this->faq->getQuestion((int) $this->faq->faqRecord['id']);

        if ($this->shouldApplyHighlighting($highlight)) {
            $processedHighlight = $this->processHighlight($highlight);
            $searchItems = explode(' ', $processedHighlight);

            foreach ($searchItems as $searchItem) {
                if (Strings::strlen($searchItem) > 2) {
                    $question = Utils::setHighlightedString($question, $searchItem);
                }
            }
        }

        return $question;
    }

    /**
     * Get attachment list for FAQ
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAttachmentList(int $faqId): array
    {
        if (
            !(bool) $this->configuration->get('records.disableAttachments')
            || $this->faq->faqRecord['active'] !== 'yes'
        ) {
            return [];
        }

        try {
            $attList = AttachmentFactory::fetchByRecordId($this->configuration, $faqId);
            return $this->attachmentHelper->getAttachmentList($attList);
        } catch (AttachmentException) {
            return [];
        }
    }

    /**
     * Get rendered category path for multi-category FAQs
     */
    public function getRenderedCategoryPath(int $faqId): string
    {
        $renderedCategoryPath = '';
        $multiCategories = $this->category->getCategoriesFromFaq($faqId);

        if ((is_countable($multiCategories) ? count($multiCategories) : 0) > 1) {
            foreach ($multiCategories as $multiCategory) {
                $path = $this->category->getPath((int) $multiCategory['id'], ' &raquo; ', true, 'list-unstyled');
                if ('' !== trim($path)) {
                    $renderedCategoryPath .= $path;
                }
            }
        }

        return $renderedCategoryPath;
    }

    /**
     * Get related FAQs HTML
     */
    public function getRelatedFaqs(int $faqId): string
    {
        $searchResultSet = new SearchResultSet($this->currentUser, $this->faqPermission, $this->configuration);

        try {
            $searchResultSet->reviewResultSet($this->relation->getAllRelatedByQuestion(
                $this->faq->faqRecord['title'],
                $this->faq->faqRecord['keywords'],
            ));
        } catch (Exception) {
            return '';
        }

        $searchHelper = new SearchHelper($this->configuration);
        return $searchHelper->renderRelatedFaqs($searchResultSet, $faqId);
    }

    /**
     * Check if FAQ is expired
     */
    public function isExpired(): bool
    {
        return date(format: 'YmdHis') > $this->faq->faqRecord['dateEnd'];
    }

    /**
     * Get number of comments for FAQ
     *
     * @return array<int, int>
     */
    public function getNumberOfComments(): array
    {
        return $this->comments->getNumberOfComments();
    }

    /**
     * Get comments data for FAQ
     *
     * @return Comment[]
     */
    public function getCommentsData(int $faqId): array
    {
        return $this->comments->getCommentsData($faqId, CommentType::FAQ);
    }

    /**
     * Get available languages for FAQ
     *
     * @return array<int, string>
     */
    public function getAvailableLanguages(int $faqId): array
    {
        return $this->configuration->getLanguage()->isLanguageAvailable($faqId);
    }

    /**
     * Get tags HTML for FAQ
     */
    public function getTagsHtml(int $faqId): string
    {
        return $this->tags->getAllLinkTagsById($faqId);
    }

    /**
     * Get rating for FAQ
     */
    public function getRating(int $faqId): string
    {
        return $this->rating->get($faqId);
    }

    /**
     * Get FAQ helper for additional functionality
     */
    public function getFaqHelper(): FaqHelper
    {
        return $this->faqHelper;
    }

    /**
     * Check if highlighting should be applied
     */
    private function shouldApplyHighlighting(?string $highlight): bool
    {
        return !in_array($highlight, [null, '/', '<', '>'], true) && Strings::strlen($highlight) > 3;
    }

    /**
     * Process highlight string for safe use in search
     */
    private function processHighlight(string $highlight): string
    {
        $highlight = str_replace("'", '´', $highlight);
        $highlight = str_replace(['^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']'], '', $highlight);
        return preg_quote($highlight, '/');
    }
}
