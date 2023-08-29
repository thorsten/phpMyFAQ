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
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */

namespace phpMyFAQ\Helper;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Helper;
use phpMyFAQ\Link;
use phpMyFAQ\Pagination;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Utils;
use stdClass;

/**
 * Class SearchHelper
 *
 * @package phpMyFAQ\Helper
 */
class SearchHelper extends Helper
{
    /**
     * Pagination object.
     */
    private ?Pagination $pagination = null;

    /**
     * Search term.
     */
    private string $searchTerm = '';

    /**
     * Constructor.
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * PMF_Pagination setter.
     *
     * @param Pagination $pagination Pagination
     */
    public function setPagination(Pagination $pagination): void
    {
        $this->pagination = $pagination;
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
     * @param SearchResultSet $resultSet Result set object
     */
    public function createAutoCompleteResult(SearchResultSet $resultSet): array
    {
        $results = [];
        $maxResults = $this->config->get('records.numberOfRecordsPerPage');
        $numOfResults = $resultSet->getNumberOfResults();

        if (0 < $numOfResults) {
            $i = 0;
            foreach ($resultSet->getResultSet() as $result) {
                if ($i > $maxResults) {
                    continue;
                }

                // Build the link to the faq record
                $currentUrl = sprintf(
                    '%s?%saction=faq&cat=%d&id=%d&artlang=%s&highlight=%s',
                    $this->config->getDefaultUrl() . 'index.php',
                    $this->sessionId,
                    $result->category_id,
                    $result->id,
                    $result->lang,
                    urlencode($this->searchTerm)
                );

                $question = html_entity_decode((string) $result->question, ENT_QUOTES | ENT_XML1 | ENT_HTML5, 'UTF-8');
                $link = new Link($currentUrl, $this->config);
                $link->itemTitle = $result->question;
                $faq = new stdClass();
                $faq->category = $this->Category->getPath($result->category_id);
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
     * @param SearchResultSet $resultSet SearchResultSet object
     */
    public function renderAdminSuggestionResult(SearchResultSet $resultSet): array
    {
        $confPerPage = $this->config->get('records.numberOfRecordsPerPage');
        $numOfResults = $resultSet->getNumberOfResults();
        $results = [];

        if (0 < $numOfResults) {
            $i = 0;
            foreach ($resultSet->getResultSet() as $result) {
                if ($i > $confPerPage) {
                    continue;
                }

                if (!isset($result->solution_id)) {
                    $faq = new Faq($this->config);
                    $solutionId = $faq->getSolutionIdFromId($result->id, $result->lang);
                } else {
                    $solutionId = $result->solution_id;
                }

                // Build the link to the faq record
                $currentUrl = $this->config->getDefaultUrl() . sprintf('index.php?solution_id=%d', $solutionId);

                $results[] = [ 'url' => $currentUrl, 'question' => $result->question ];
                ++$i;
            }
        }

        return $results;
    }

    /**
     * Renders the result page for the main search page.
     *
     *
     * @throws Exception
     */
    public function renderSearchResult(SearchResultSet $resultSet, int $currentPage): string
    {
        $html = '';
        $confPerPage = $this->config->get('records.numberOfRecordsPerPage');
        $numOfResults = $resultSet->getNumberOfResults();

        $totalPages = (int)ceil($numOfResults / $confPerPage);
        $lastPage = $currentPage * $confPerPage;
        $firstPage = $lastPage - $confPerPage;

        if (0 < $numOfResults) {
            $html .= sprintf(
                '<h4 class="mt-3">%s</h4>',
                $this->plurals->GetMsg('plmsgSearchAmount', $numOfResults)
            );

            if (1 < $totalPages) {
                $html .= sprintf(
                    "<p><strong>%s%d %s %s</strong></p>\n",
                    Translation::get('msgPage'),
                    $currentPage,
                    Translation::get('msgVoteFrom'),
                    $this->plurals->GetMsg('plmsgPagesTotal', $totalPages)
                );
            }

            $html .= "<ul class=\"phpmyfaq-search-results list-unstyled\">\n";

            $counter = $displayedCounter = 0;
            $faqHelper = new FaqHelper($this->config);
            foreach ($resultSet->getResultSet() as $result) {
                if ($displayedCounter >= $confPerPage) {
                    break;
                }

                ++$counter;
                if ($counter <= $firstPage) {
                    continue;
                }

                ++$displayedCounter;

                // Set language for current category to fetch the correct category name
                $this->Category->setLanguage($result->lang);

                $categoryInfo = $this->Category->getCategoriesFromFaq($result->id);
                $categoryInfo = array_values($categoryInfo); //Reset the array keys
                $question = Utils::chopString(Strings::htmlentities($result->question), 15);
                $answerPreview = $faqHelper->renderAnswerPreview($result->answer, 20);

                $searchTerm = str_replace(
                    ['^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']', '"'],
                    '',
                    $this->searchTerm
                );
                $searchTerm = preg_quote($searchTerm, '/');
                $searchItems = explode(' ', $searchTerm);

                if ($this->config->get('search.enableHighlighting') && Strings::strlen($searchItems[0]) > 1) {
                    foreach ($searchItems as $item) {
                        if (Strings::strlen($item) > 2) {
                            $question = Utils::setHighlightedString($question, $item);
                            $answerPreview = Utils::setHighlightedString($answerPreview, $item);
                        }
                    }
                }

                // Build the link to the faq record
                $currentUrl = sprintf(
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s&amp;highlight=%s',
                    $this->config->getDefaultUrl(),
                    $this->sessionId,
                    $result->category_id,
                    $result->id,
                    $result->lang,
                    urlencode($searchTerm)
                );

                $oLink = new Link($currentUrl, $this->config);
                $oLink->text = $question;
                $oLink->itemTitle = $oLink->tooltip = $result->question;

                $html .= '<li class="mb-2">';
                $html .= $this->renderScore($result->score * 33);
                $html .= sprintf(
                    '<strong>%s</strong><br><i class="fa fa-question-circle-o"></i> %s<br>',
                    Strings::htmlentities($this->Category->getPath($categoryInfo[0]['id'])),
                    $oLink->toHtmlAnchor()
                );
                $html .= sprintf(
                    "<small class=\"small\"><strong>%s</strong> %s...</small>\n",
                    Translation::get('msgSearchContent'),
                    $answerPreview
                );
                $html .= '</li>';
            }
            $html .= "</ul>\n";
            if (1 < $totalPages) {
                $html .= $this->pagination->render();
            }
        } else {
            $html = Translation::get('err_noArticles');
        }

        return $html;
    }

    /**
     * Renders the scoring stars
     */
    private function renderScore(float $relevance = 0): string
    {
        $html = sprintf('<span title="%01.2f%%">', $relevance);
        $emptyStar = '<i aria-hidden="true" class="fa fa-star-o"></i>';
        $fullStar = '<i aria-hidden="true" class="fa fa-star"></i>';

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

    public function renderRelatedFaqs(SearchResultSet $resultSet, int $recordId): string
    {
        $html = '';
        $numOfResults = $resultSet->getNumberOfResults();

        if ($numOfResults > 0) {
            $html .= '<ul class="list-unstyled">';
            $counter = 0;
            foreach ($resultSet->getResultSet() as $result) {
                if ($counter >= 5) {
                    continue;
                }
                if ($recordId == $result->id) {
                    continue;
                }
                ++$counter;

                $url = sprintf(
                    '%sindex.php?action=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->config->getDefaultUrl(),
                    $result->category_id,
                    $result->id,
                    $result->lang
                );
                $link = new Link($url, $this->config);
                $link->itemTitle = Strings::htmlentities($result->question);
                $link->text = Strings::htmlentities($result->question);
                $link->tooltip = Strings::htmlentities($result->question);
                $link->class = 'text-decoration-none';
                $html .= '<li><i class="fa fa-question-circle"></i> ' . $link->toHtmlAnchor() . '</li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * Renders the list of the most popular search terms.
     *
     * @param array $mostPopularSearches Array with popular search terms
     */
    public function renderMostPopularSearches(array $mostPopularSearches): string
    {
        $html = '';

        foreach ($mostPopularSearches as $searchItem) {
            if (Strings::strlen($searchItem['searchterm']) > 0) {
                $html .= sprintf(
                    '<a class="btn btn-primary m-1" href="?search=%s&submit=Search&action=search">%s ' .
                    '<span class="badge bg-secondary">%dx</span> </a>',
                    urlencode((string) $searchItem['searchterm']),
                    $searchItem['searchterm'],
                    $searchItem['number']
                );
            }
        }

        return $html;
    }
}
