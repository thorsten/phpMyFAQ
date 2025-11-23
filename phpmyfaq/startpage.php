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
 * @copyright 2002-2025 phpMyFAQ Team
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
use phpMyFAQ\Twig\Extensions\TagNameTwigExtension;
use phpMyFAQ\Twig\TwigWrapper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AttributeExtension;

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

$writeNewsHeader = Translation::get(key: 'newsArchive');

$startPageCategory = new Startpage($faqConfig);
$startPageCategory
    ->setLanguage($faqLangCode)
    ->setUser($currentUser)
    ->setGroups($currentGroups);

$startPageCategories = $startPageCategory->getCategories();

// generates a top ten list
$param = $faqConfig->get('records.orderingPopularFaqs') == 'visits' ? 'visits' : 'voted';

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
$twig->addExtension(new AttributeExtension(TagNameTwigExtension::class));
$twigTemplate = $twig->loadTemplate('./startpage.twig');

// Twig template variables
$templateVars = [
    ... $templateVars,
    'baseHref' => $faqSystem->getSystemUri($faqConfig),
    'title' => $faqConfig->getTitle(),
    'pageHeader' => $faqConfig->getTitle(),
    'startPageCategories' => (is_countable($startPageCategories) ? count($startPageCategories) : 0) > 0,
    'startPageCategoryDecks' => $startPageCategories,
    'stickyRecordsHeader' => Translation::get(key: 'stickyRecordsHeader'),
    'stickyRecordsList' => $faq->getStickyFaqsData(),
    'writeTopTenHeader' => Translation::get(key: 'msgTopTen'),
    'topRecordsList' => $faqStatistics->getTopTen($param),
    'errorMsgTopTen' => Translation::get(key: 'err_noTopTen'),
    'writeNewestHeader' => Translation::get(key: 'msgLatestArticles'),
    'latestRecordsList' => $faqStatistics->getLatest(),
    'errorMsgLatest' => Translation::get(key: 'msgErrorNoRecords'),
    'msgTrendingFAQs' => Translation::get(key: 'msgTrendingFAQs'),
    'trendingRecordsList' => $faqStatistics->getTrending(),
    'errorMsgTrendingFaqs' => Translation::get(key: 'msgErrorNoRecords'),
    'msgNewsHeader' => $writeNewsHeader,
    'newsList' => $news->getAll(),
    'writeNumberOfArticles' => $plr->getMsg('plmsgHomeArticlesOnline', $faqStatistics->totalFaqs($faqLangCode)),
    'msgTags' => Translation::get(key: 'msgPopularTags'),
    'tagsList' => $oTag->getPopularTags(12),
    'formActionUrl' => '?' . $sids . 'action=search',
    'searchBox' => Translation::get(key: 'msgSearch'),
    'categoryId' => ($cat === 0) ? '%' : (int)$cat,
    'msgSearch' => sprintf(
        '<a class="help" href="%sindex.php?action=search">%s</a>',
        Strings::htmlentities($faqSystem->getSystemUri($faqConfig)),
        Translation::get(key: 'msgAdvancedSearch')
    )
];

return $templateVars;
