<?php

/**
 * Helper class for phpMyFAQ search.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use Exception;
use League\CommonMark\Exception\CommonMarkException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Link;
use phpMyFAQ\Link\Util\TitleSlugifier;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Strings;
use phpMyFAQ\Utils;
use stdClass;

/**
 * Class SearchHelper
 *
 * @package phpMyFAQ\Helper
 */
class SearchHelper extends AbstractHelper
{
    /**
     * Search term.
     */
    private string $searchTerm = '';

    /**
     * Constructor.
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Search term setter.
     *
     * @param string $searchTerm Search term
     */
    public function setSearchTerm(string $searchTerm): void
    {
        $this->searchTerm = $searchTerm;
    }

    /**
     * Renders the results for Typehead.
     *
     * @param SearchResultSet $searchResultSet Result set object
     */
    public function createAutoCompleteResult(SearchResultSet $searchResultSet): array
    {
        $results = [];
        $maxResults = (int) $this->configuration->get(item: 'records.numberOfRecordsPerPage');
        $numOfResults = $searchResultSet->getNumberOfResults();

        if (0 < $numOfResults) {
            $i = 0;
            foreach ($searchResultSet->getResultSet() as $result) {
                if ($i > $maxResults) {
                    continue;
                }

                $resultQuestion = (string) $result->question;
                $question = html_entity_decode($resultQuestion, ENT_QUOTES | ENT_XML1 | ENT_HTML5, encoding: 'UTF-8');

                // Check if this is a custom page result
                $isCustomPage = ($result->content_type ?? null) === 'page';

                if ($isCustomPage) {
                    // Build the link to the custom page
                    $currentUrl = sprintf(
                        '%spage/%s.html',
                        $this->configuration->getDefaultUrl(),
                        (string) $result->slug,
                    );

                    $link = new Link($currentUrl, $this->configuration);
                    $link->setTitle($resultQuestion);
                    $faq = new stdClass();
                    $faq->category = ''; // Custom pages don't have categories
                    $faq->question = Utils::chopString($question, 15);
                    $faq->url = $link->toString();

                    $results[] = $faq;
                    ++$i;
                    continue;
                }

                // Build the link to the faq record
                $currentUrl = sprintf(
                    '%scontent/%d/%d/%s/%s.html?highlight=%s',
                    $this->configuration->getDefaultUrl(),
                    (int) $result->category_id,
                    (int) $result->id,
                    (string) $result->lang,
                    TitleSlugifier::slug($resultQuestion),
                    urlencode($this->searchTerm),
                );

                $link = new Link($currentUrl, $this->configuration);
                $link->setTitle($resultQuestion);
                $faq = new stdClass();
                $faq->category = $this->category()->getPath((int) $result->category_id);
                $faq->question = Utils::chopString($question, 15);
                $faq->url = $link->toString();

                $results[] = $faq;

                ++$i;
            }
        }

        return $results;
    }

    /**
     * Renders the result page for Instant Response.
     *
     * @param SearchResultSet $searchResultSet SearchResultSet object
     */
    public function renderAdminSuggestionResult(SearchResultSet $searchResultSet): array
    {
        $confPerPage = (int) $this->configuration->get(item: 'records.numberOfRecordsPerPage');
        $numOfResults = $searchResultSet->getNumberOfResults();
        $results = [];

        if (0 < $numOfResults) {
            $i = 0;
            foreach ($searchResultSet->getResultSet() as $result) {
                if ($i > $confPerPage) {
                    continue;
                }

                $faqId = (int) $result->id;
                $faqLanguage = (string) $result->lang;

                $solutionId = (int) ($result->solution_id ?? 0);
                if ($solutionId === 0) {
                    $faq = new Faq($this->configuration);
                    $solutionId = $faq->getSolutionIdFromId($faqId, $faqLanguage);
                }

                // Build the link to the faq record
                $currentUrl = sprintf('%ssolution_id_%d.html', $this->configuration->getDefaultUrl(), $solutionId);
                $adminUrl = sprintf(
                    '%sadmin/faq/edit/%d/%s',
                    $this->configuration->getDefaultUrl(),
                    $faqId,
                    $faqLanguage,
                );

                $results[] = ['url' => $currentUrl, 'question' => (string) $result->question, 'adminUrl' => $adminUrl];
                ++$i;
            }
        }

        return $results;
    }

    /**
     * Returns the result page for the main search page
     *
     * @return stdClass[]
     * @throws Exception|CommonMarkException
     */
    public function getSearchResult(SearchResultSet $searchResultSet, int $currentPage): array
    {
        $results = [];
        $confPerPage = (int) $this->configuration->get(item: 'records.numberOfRecordsPerPage');
        $numOfResults = $searchResultSet->getNumberOfResults();

        $lastPage = $currentPage * $confPerPage;
        $firstPage = $lastPage - $confPerPage;

        if (0 < $numOfResults) {
            $counter = 0;
            $displayedCounter = 0;
            $faqHelper = new FaqHelper($this->configuration);

            foreach ($searchResultSet->getResultSet() as $resultSet) {
                $result = new stdClass();

                if ($displayedCounter >= $confPerPage) {
                    break;
                }

                ++$counter;
                if ($counter <= $firstPage) {
                    continue;
                }

                ++$displayedCounter;

                // Check if this is a custom page result
                $isCustomPage = ($resultSet->content_type ?? null) === 'page';

                if ($isCustomPage) {
                    // Handle custom page results
                    $question = Utils::chopString(Strings::htmlentities((string) $resultSet->question), 15);
                    $answerPreview = $faqHelper->renderAnswerPreview((string) $resultSet->answer, 20);

                    $searchTerm = str_replace(
                        search: ['^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']', '"'],
                        replace: '',
                        subject: $this->searchTerm,
                    );
                    $searchTerm = preg_quote($searchTerm, delimiter: '/');
                    $searchItems = explode(' ', $searchTerm);

                    if (
                        true === $this->configuration->get(item: 'search.enableHighlighting')
                        && Strings::strlen($searchItems[0]) > 1
                    ) {
                        foreach ($searchItems as $searchItem) {
                            if (Strings::strlen($searchItem) <= 2) {
                                continue;
                            }

                            $question = Utils::setHighlightedString($question, $searchItem);
                            $answerPreview = Utils::setHighlightedString($answerPreview, $searchItem);
                        }
                    }

                    // Build the link to the custom page
                    $currentUrl = sprintf(
                        '%spage/%s.html',
                        $this->configuration->getDefaultUrl(),
                        (string) $resultSet->slug,
                    );

                    $oLink = new Link($currentUrl, $this->configuration);
                    $oLink->setTitle((string) $resultSet->question);

                    $result->renderedScore = $this->renderScore(
                        (is_numeric($resultSet->score ?? null) ? (float) $resultSet->score : 0.0) * 33,
                    );
                    $result->question = $question;
                    $result->path = ''; // Custom pages don't have category paths
                    $result->url = $oLink->toString();
                    $result->answerPreview = $answerPreview;
                    $result->isCustomPage = true;
                    $results[] = $result;
                    continue;
                }

                // Handle FAQ results
                // Set language for the current category to fetch the correct category name
                $this->category()->setLanguage((string) $resultSet->lang);

                $categoryInfo = $this->category()->getCategoriesFromFaq((int) $resultSet->id);
                $categoryInfo = array_values($categoryInfo); //Reset the array keys
                $question = Utils::chopString(Strings::htmlentities((string) $resultSet->question), 15);
                $answerPreview = Strings::htmlentities($faqHelper->renderAnswerPreview(
                    (string) $resultSet->answer,
                    20,
                ));

                $searchTerm = str_replace(
                    search: ['^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']', '"'],
                    replace: '',
                    subject: $this->searchTerm,
                );
                $searchTerm = preg_quote($searchTerm, delimiter: '/');
                $searchItems = explode(' ', $searchTerm);

                if (
                    true === $this->configuration->get(item: 'search.enableHighlighting')
                    && Strings::strlen($searchItems[0]) > 1
                ) {
                    foreach ($searchItems as $searchItem) {
                        if (Strings::strlen($searchItem) <= 2) {
                            continue;
                        }

                        $question = Utils::setHighlightedString($question, $searchItem);
                        $answerPreview = Utils::setHighlightedString($answerPreview, $searchItem);
                    }
                }

                // Build the link to the faq record
                $currentUrl = sprintf(
                    '%scontent/%d/%d/%s/%s.html?highlight=%s',
                    $this->configuration->getDefaultUrl(),
                    (int) $resultSet->category_id,
                    (int) $resultSet->id,
                    (string) $resultSet->lang,
                    TitleSlugifier::slug((string) $resultSet->question),
                    urlencode($searchTerm),
                );

                $oLink = new Link($currentUrl, $this->configuration);
                $oLink->setTitle((string) $resultSet->question);

                $path = ($categoryInfo[0]['id'] ?? null) !== null
                    ? $this->category()->getPath((int) $categoryInfo[0]['id'])
                    : '';

                $result->renderedScore = $this->renderScore(
                    (is_numeric($resultSet->score ?? null) ? (float) $resultSet->score : 0.0) * 33,
                );
                $result->question = $question;
                $result->path = $path;
                $result->url = $oLink->toString();
                $result->answerPreview = $answerPreview;

                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Renders the scoring stars
     */
    private function renderScore(float $relevance = 0): string
    {
        $html = sprintf('<span title="%01.2f%%">', $relevance);
        $emptyStar = '<i aria-hidden="true" class="bi bi-star-o"></i>';
        $fullStar = '<i aria-hidden="true" class="bi bi-star"></i>';

        $html .= match (true) {
            0 === (int) $relevance => $emptyStar . $emptyStar . $emptyStar,
            $relevance < 33 => $fullStar . $emptyStar . $emptyStar,
            $relevance < 66 => $fullStar . $fullStar . $emptyStar,
            default => $fullStar . $fullStar . $fullStar,
        };

        return $html . '</span> ';
    }

    public function renderRelatedFaqs(SearchResultSet $searchResultSet, int $recordId): string
    {
        $html = '';
        $numOfResults = $searchResultSet->getNumberOfResults();

        if ($numOfResults > 0) {
            $html .= '<ul class="list-unstyled">';
            $counter = 0;
            foreach ($searchResultSet->getResultSet() as $result) {
                if ($counter >= 5) {
                    continue;
                }

                if ($recordId === (int) $result->id) {
                    continue;
                }

                ++$counter;

                $relatedQuestion = Strings::htmlentities((string) $result->question);
                $url = sprintf(
                    '%scontent/%d/%d/%s/%s.html',
                    $this->configuration->getDefaultUrl(),
                    (int) $result->category_id,
                    (int) $result->id,
                    (string) $result->lang,
                    TitleSlugifier::slug((string) $result->question),
                );
                $link = new Link($url, $this->configuration);
                $link->setTitle($relatedQuestion);
                $link->text = $relatedQuestion;
                $link->tooltip = $relatedQuestion;
                $link->class = 'text-decoration-none';
                $html .= '<li><i class="bi bi-question-circle"></i> ' . $link->toHtmlAnchor() . '</li>';
            }

            $html .= '</ul>';
        }

        return $html;
    }
}
