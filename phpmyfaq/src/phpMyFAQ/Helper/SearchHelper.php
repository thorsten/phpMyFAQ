<?php

declare(strict_types=1);

/**
 * Helper class for phpMyFAQ search.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */

namespace phpMyFAQ\Helper;

use Exception;
use League\CommonMark\Exception\CommonMarkException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Link;
use phpMyFAQ\Pagination;
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
        $maxResults = $this->configuration->get('records.numberOfRecordsPerPage');
        $numOfResults = $searchResultSet->getNumberOfResults();

        if (0 < $numOfResults) {
            $i = 0;
            foreach ($searchResultSet->getResultSet() as $result) {
                if ($i > $maxResults) {
                    continue;
                }

                // Build the link to the faq record
                $currentUrl = sprintf(
                    '%s?%saction=faq&cat=%d&id=%d&artlang=%s&highlight=%s',
                    $this->configuration->getDefaultUrl() . 'index.php',
                    $this->sessionId,
                    $result->category_id,
                    $result->id,
                    $result->lang,
                    urlencode($this->searchTerm),
                );

                $question = html_entity_decode((string) $result->question, ENT_QUOTES | ENT_XML1 | ENT_HTML5, 'UTF-8');
                $link = new Link($currentUrl, $this->configuration);
                $link->itemTitle = $result->question;
                $faq = new stdClass();
                $faq->category = $this->Category->getPath($result->category_id ?? 0);
                $faq->question = Utils::chopString($question, 15);
                $faq->url = $link->toString();

                $results[] = $faq;
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
        $confPerPage = $this->configuration->get('records.numberOfRecordsPerPage');
        $numOfResults = $searchResultSet->getNumberOfResults();
        $results = [];

        if (0 < $numOfResults) {
            $i = 0;
            foreach ($searchResultSet->getResultSet() as $result) {
                if ($i > $confPerPage) {
                    continue;
                }

                if (!isset($result->solution_id)) {
                    $faq = new Faq($this->configuration);
                    $solutionId = $faq->getSolutionIdFromId($result->id, $result->lang);
                } else {
                    $solutionId = $result->solution_id;
                }

                // Build the link to the faq record
                $currentUrl = $this->configuration->getDefaultUrl() . sprintf('index.php?solution_id=%d', $solutionId);
                $adminUrl =
                    $this->configuration->getDefaultUrl() . sprintf('admin/faq/edit/%d/%s', $result->id, $result->lang);

                $results[] = ['url' => $currentUrl, 'question' => $result->question, 'adminUrl' => $adminUrl];
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
        $confPerPage = $this->configuration->get('records.numberOfRecordsPerPage');
        $numOfResults = $searchResultSet->getNumberOfResults();

        $lastPage = $currentPage * $confPerPage;
        $firstPage = $lastPage - $confPerPage;

        if (0 < $numOfResults) {
            $counter = 0;
            $displayedCounter = 0;
            $faqHelper = new FaqHelper($this->configuration);

            foreach ($searchResultSet->getResultSet() as $resultSet) {
                $result = new stdClass();

                if ($displayedCounter >= (int) $confPerPage) {
                    break;
                }

                ++$counter;
                if ($counter <= $firstPage) {
                    continue;
                }

                ++$displayedCounter;

                // Set language for current category to fetch the correct category name
                $this->Category->setLanguage($resultSet->lang);

                $categoryInfo = $this->Category->getCategoriesFromFaq($resultSet->id);
                $categoryInfo = array_values($categoryInfo); //Reset the array keys
                $question = Utils::chopString(Strings::htmlentities($resultSet->question), 15);
                $answerPreview = $faqHelper->renderAnswerPreview($resultSet->answer, 20);

                $searchTerm = str_replace(
                    ['^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']', '"'],
                    '',
                    $this->searchTerm,
                );
                $searchTerm = preg_quote($searchTerm, '/');
                $searchItems = explode(' ', $searchTerm);

                if ($this->configuration->get('search.enableHighlighting') && Strings::strlen($searchItems[0]) > 1) {
                    foreach ($searchItems as $searchItem) {
                        if (Strings::strlen($searchItem) > 2) {
                            $question = Utils::setHighlightedString($question, $searchItem);
                            $answerPreview = Utils::setHighlightedString($answerPreview, $searchItem);
                        }
                    }
                }

                // Build the link to the faq record
                $currentUrl = sprintf(
                    '%sindex.php?action=faq&cat=%d&id=%d&artlang=%s&highlight=%s',
                    $this->configuration->getDefaultUrl(),
                    $resultSet->category_id,
                    $resultSet->id,
                    $resultSet->lang,
                    urlencode($searchTerm),
                );

                $oLink = new Link($currentUrl, $this->configuration);
                $oLink->itemTitle = $resultSet->question;

                $path = isset($categoryInfo[0]['id']) ? $this->Category->getPath($categoryInfo[0]['id']) : '';

                $result->renderedScore = $this->renderScore($resultSet->score * 33);
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

        if (0 === (int) $relevance) {
            $html .= $emptyStar . $emptyStar . $emptyStar;
        } elseif ($relevance < 33) {
            $html .= $fullStar . $emptyStar . $emptyStar;
        } elseif ($relevance < 66) {
            $html .= $fullStar . $fullStar . $emptyStar;
        } else {
            $html .= $fullStar . $fullStar . $fullStar;
        }

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

                if ($recordId == $result->id) {
                    continue;
                }

                ++$counter;

                $url = sprintf(
                    '%sindex.php?action=faq&cat=%d&id=%d&artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $result->category_id,
                    $result->id,
                    $result->lang,
                );
                $link = new Link($url, $this->configuration);
                $link->itemTitle = Strings::htmlentities($result->question);
                $link->text = Strings::htmlentities($result->question);
                $link->tooltip = Strings::htmlentities($result->question);
                $link->class = 'text-decoration-none';
                $html .= '<li><i class="bi bi-question-circle"></i> ' . $link->toHtmlAnchor() . '</li>';
            }

            $html .= '</ul>';
        }

        return $html;
    }
}
