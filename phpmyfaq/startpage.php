<?php

/**
 * The main start page with the start page categories, the Top 10 and the latest messages.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-08-23
 */

use phpMyFAQ\Category\Startpage;
use phpMyFAQ\Faq\Statistics;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\News;
use phpMyFAQ\Strings;
use phpMyFAQ\Template\Extensions\TagNameTwigExtension;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');

$news = new News($faqConfig);
$categoryHelper = new CategoryHelper();
$plr = new Plurals();
$faqStatistics = new Statistics($faqConfig);

$request = Request::createFromGlobals();

$writeNewsHeader = Translation::get('newsArchive');

$startPageCategory = new Startpage($faqConfig);
$startPageCategory
    ->setLanguage($faqLangCode)
    ->setUser($currentUser)
    ->setGroups($currentGroups);

$startPageCategories = $startPageCategory->getCategories();

// generates a top ten list
$param = $faqConfig->get('records.orderingPopularFaqs') == 'visits' ? 'visits' : 'voted';

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/' . TwigWrapper::getTemplateSetName());
$twig->addExtension(new TagNameTwigExtension());
$twigTemplate = $twig->loadTemplate('./startpage.twig');

// Twig template variables
$templateVars = [
    ... $templateVars,
    'baseHref' => $faqSystem->getSystemUri($faqConfig),
    'title' => $faqConfig->getTitle(),
    'pageHeader' => $faqConfig->getTitle(),
    'startPageCategories' => (is_countable($startPageCategories) ? count($startPageCategories) : 0) > 0,
    'startPageCategoryDecks' => $startPageCategories,
    'stickyRecordsHeader' => Translation::get('stickyRecordsHeader'),
    'stickyRecordsList' => $faq->getStickyFaqsData(),
    'writeTopTenHeader' => Translation::get('msgTopTen'),
    'topRecordsList' => $faqStatistics->getTopTen($param),
    'errorMsgTopTen' => Translation::get('err_noTopTen'),
    'writeNewestHeader' => Translation::get('msgLatestArticles'),
    'latestRecordsList' => $faqStatistics->getLatest(),
    'errorMsgLatest' => Translation::get('msgErrorNoRecords'),
    'msgTrendingFAQs' => Translation::get('msgTrendingFAQs'),
    'trendingRecordsList' => $faqStatistics->getTrending(),
    'errorMsgTrendingFaqs' => Translation::get('msgErrorNoRecords'),
    'msgNewsHeader' => $writeNewsHeader,
    'newsList' => $news->getAll(),
    'writeNumberOfArticles' => $plr->getMsg('plmsgHomeArticlesOnline', $faqStatistics->totalFaqs($faqLangCode)),
    'msgTags' => Translation::get('msgPopularTags'),
    'tagsList' => $oTag->getPopularTags(12),
    'formActionUrl' => '?' . $sids . 'action=search',
    'searchBox' => Translation::get('msgSearch'),
    'categoryId' => ($cat === 0) ? '%' : (int)$cat,
    'msgSearch' => sprintf(
        '<a class="help" href="%sindex.php?action=search">%s</a>',
        Strings::htmlentities($faqSystem->getSystemUri($faqConfig)),
        Translation::get('msgAdvancedSearch')
    )
];

return $templateVars;
