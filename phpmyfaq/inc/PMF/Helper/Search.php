<?php

/**
 * Helper class for phpMyFAQ search.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Helper.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */
class PMF_Helper_Search extends PMF_Helper
{
    /**
     * Language.
     *
     * @var PMF_Language
     */
    private $language = null;

    /**
     * PMF_Pagination object.
     *
     * @var PMF_Pagination
     */
    private $pagination = null;

    /**
     * Search term.
     *
     * @var string
     */
    private $searchterm = '';

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Helper_Search
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
        $this->pmfLang = $this->getTranslations();
    }

    /**
     * Language setter.
     *
     * @param PMF_Language $language Language
     */
    public function setLanguage(PMF_Language $language)
    {
        $this->language = $language;
    }

    /**
     * PMF_Pagination setter.
     *
     * @param PMF_Pagination $pagination PMF_Pagination
     */
    public function setPagination(PMF_Pagination $pagination)
    {
        $this->pagination = $pagination;
    }

    /**
     * Searchterm setter.
     *
     * @param string $searchterm Searchterm
     */
    public function setSearchterm($searchterm)
    {
        $this->searchterm = $searchterm;
    }

    /**
     * Renders the results for Typehead.
     *
     * @param PMF_Search_Resultset $resultSet PMF_Search_Resultset object
     *
     * @return string
     */
    public function renderInstantResponseResult(PMF_Search_Resultset $resultSet)
    {
        $results = [];
        $maxResults = $this->_config->get('records.numberOfRecordsPerPage');
        $numOfResults = $resultSet->getNumberOfResults();

        if (0 < $numOfResults) {
            $i = 0;
            foreach ($resultSet->getResultset() as $result) {
                if ($i > $maxResults) {
                    continue;
                }

                // Build the link to the faq record
                $currentUrl = sprintf('%s?%saction=artikel&cat=%d&id=%d&artlang=%s&highlight=%s',
                    PMF_Link::getSystemRelativeUri('ajaxresponse.php').'index.php',
                    $this->sessionId,
                    $result->category_id,
                    $result->id,
                    $result->lang,
                    urlencode($this->searchterm)
                );

                $question = html_entity_decode($result->question, ENT_QUOTES | ENT_XML1 | ENT_HTML5, 'UTF-8');
                $link = new PMF_Link($currentUrl, $this->_config);
                $link->itemTitle = $result->question;
                $faq = new stdClass();
                $faq->categoryName = $this->Category->getPath($result->category_id);
                $faq->faqQuestion = PMF_Utils::chopString($question, 15);
                $faq->faqLink = $link->toString();

                $results['results'][] = $faq;
            }
        } else {
            $results[] = $this->translation['err_noArticles'];
        }

        return json_encode($results);
    }

    /**
     * Renders the result page for Instant Response.
     *
     * @param PMF_Search_Resultset $resultSet PMF_Search_Resultset object
     *
     * @return string
     */
    public function renderAdminSuggestionResult(PMF_Search_Resultset $resultSet)
    {
        $html = '';
        $confPerPage = $this->_config->get('records.numberOfRecordsPerPage');
        $numOfResults = $resultSet->getNumberOfResults();

        if (0 < $numOfResults) {
            $i = 0;
            foreach ($resultSet->getResultset() as $result) {
                if ($i > $confPerPage) {
                    continue;
                }

                if (!isset($result->solution_id)) {
                    $faq = new PMF_Faq($this->_config);
                    $solutionId = $faq->getSolutionIdFromId($result->id, $result->lang);
                } else {
                    $solutionId = $result->solution_id;
                }

                // Build the link to the faq record
                $currentUrl = sprintf('index.php?solution_id=%d', $solutionId);

                $html .= sprintf(
                    '<label for="%d"><input id="%d" type="radio" name="faqURL" value="%s"> %s</label><br>',
                    $result->id,
                    $result->id,
                    $currentUrl,
                    $result->question
                );
                ++$i;
            }
        } else {
            $html = $this->translation['err_noArticles'];
        }

        return $html;
    }

    /**
     * Renders the result page for the main search page.
     *
     * @param PMF_Search_Resultset $resultSet   PMF_Search_Resultset object
     * @param int                  $currentPage Current page number
     *
     * @return string
     */
    public function renderSearchResult(PMF_Search_Resultset $resultSet, $currentPage)
    {
        $html = '';
        $confPerPage = $this->_config->get('records.numberOfRecordsPerPage');
        $numOfResults = $resultSet->getNumberOfResults();

        $totalPages = ceil($numOfResults / $confPerPage);
        $lastPage = $currentPage * $confPerPage;
        $firstPage = $lastPage - $confPerPage;
        if ($lastPage > $numOfResults) {
            $lastPage = $numOfResults;
        }

        if (0 < $numOfResults) {
            $html .= sprintf(
                "<p role=\"heading\" aria-level=\"1\">%s</p>\n",
                $this->plurals->GetMsg('plmsgSearchAmount', $numOfResults)
            );

            if (1 < $totalPages) {
                $html .= sprintf(
                    "<p><strong>%s%d %s %s</strong></p>\n",
                    $this->translation['msgPage'],
                    $currentPage,
                    $this->translation['msgVoteFrom'],
                    $this->plurals->GetMsg('plmsgPagesTotal', $totalPages)
                );
            }

            $html .= "<ul class=\"phpmyfaq-search-results list-unstyled\">\n";

            $counter = $displayedCounter = 0;
            $faqHelper = new PMF_Helper_Faq($this->_config);
            foreach ($resultSet->getResultset() as $result) {
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

                $categoryInfo = $this->Category->getCategoriesFromArticle($result->id);
                $categoryInfo = array_values($categoryInfo); //Reset the array keys
                $question = PMF_Utils::chopString($result->question, 15);
                $answerPreview = $faqHelper->renderAnswerPreview($result->answer, 25);

                $searchterm = str_replace(
                    ['^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']', '"'],
                    '',
                    $this->searchterm
                );
                $searchterm = preg_quote($searchterm, '/');
                $searchItems = explode(' ', $searchterm);

                if ($this->_config->get('search.enableHighlighting') && PMF_String::strlen($searchItems[0]) > 1) {
                    foreach ($searchItems as $item) {
                        if (PMF_String::strlen($item) > 2) {
                            $question = PMF_Utils::setHighlightedString($question, $item);
                            $answerPreview = PMF_Utils::setHighlightedString($answerPreview, $item);
                        }
                    }
                }

                // Build the link to the faq record
                $currentUrl = sprintf(
                    '%s?%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s&amp;highlight=%s',
                    PMF_Link::getSystemRelativeUri(),
                    $this->sessionId,
                    $result->category_id,
                    $result->id,
                    $result->lang,
                    urlencode($searchterm)
                );

                $oLink = new PMF_Link($currentUrl, $this->_config);
                $oLink->text = $question;
                $oLink->itemTitle = $oLink->tooltip = $result->question;

                $html .= '<li>';
                $html .= $this->renderScore($result->score * 33);
                $html .= sprintf('<strong>%s</strong>: %s<br />',
                    $categoryInfo[0]['name'],
                    $oLink->toHtmlAnchor()
                );
                $html .= sprintf(
                    "<small class=\"searchpreview\"><strong>%s</strong> %s...</small>\n",
                    $this->translation['msgSearchContent'],
                    $answerPreview
                );
                $html .= '</li>';
            }

            $html .= "</ul>\n";

            if (1 < $totalPages) {
                $html .= $this->pagination->render();
            }
        } else {
            $html = $this->translation['err_noArticles'];
        }

        return $html;
    }

    /**
     * @param PMF_Search_Resultset $resultSet
     * @param int                  $recordId
     *
     * @return string
     */
    public function renderRelatedFaqs(PMF_Search_Resultset $resultSet, $recordId)
    {
        $html = '';
        $numOfResults = $resultSet->getNumberOfResults();

        if ($numOfResults > 0) {
            $html   .= '<ul>';
            $counter = 0;
            foreach ($resultSet->getResultset() as $result) {
                if ($counter >= 5) {
                    continue;
                }
                if ($recordId == $result->id) {
                    continue;
                }
                ++$counter;

                $url = sprintf(
                    '%s?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    PMF_Link::getSystemRelativeUri(),
                    $result->category_id,
                    $result->id,
                    $result->lang
                );
                $oLink = new PMF_Link($url, $this->_config);
                $oLink->itemTitle = $result->question;
                $oLink->text = $result->question;
                $oLink->tooltip = $result->question;
                $html .= '<li>'.$oLink->toHtmlAnchor().'</li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * Renders the list of the most popular search terms.
     *
     * @param array $mostPopularSearches Array with popular search terms
     *
     * @return string
     */
    public function renderMostPopularSearches(Array $mostPopularSearches)
    {
        $html = '';

        foreach ($mostPopularSearches as $searchItem) {
            if (PMF_String::strlen($searchItem['searchterm']) > 0) {

                $html .= sprintf(
                    '<li><a class="pmf tag" href="?search=%s&submit=Search&action=search">%s <span class="badge">%dx</span> </a></li>',
                    urlencode($searchItem['searchterm']),
                    $searchItem['searchterm'],
                    $searchItem['number']
                );
            }
        }

        return $html;
    }

    /**
     * @param int $relevance
     *
     * @return string
     */
    private function renderScore($relevance = 0)
    {
        $html = sprintf('<span title="%01.2f%%">', $relevance);

        if (0 === (int)$relevance) {
            $html .= '<i aria-hidden="true" class="fa fa-star-o"></i><i aria-hidden="true" class="fa fa-star-o"></i><i aria-hidden="true" class="fa fa-star-o"></i>';
        } elseif ($relevance < 33) {
            $html .= '<i aria-hidden="true" class="fa fa-star"></i><i aria-hidden="true" class="fa fa-star-o"></i><i aria-hidden="true" class="fa fa-star-o"></i>';
        } elseif ($relevance < 66) {
            $html .= '<i aria-hidden="true" class="fa fa-star"></i><i aria-hidden="true" class="fa fa-star"></i><i aria-hidden="true" class="fa fa-star-o"></i>';
        } else {
            $html .= '<i aria-hidden="true" class="fa fa-star"></i><i aria-hidden="true" class="fa fa-star"></i><i aria-hidden="true" class="fa fa-star"></i>';
        }

        return $html.'</span>';
    }
}
