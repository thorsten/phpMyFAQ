<?php

/**
 * Frontend for search log statistics.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-03-30
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Search;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\Extensions\LanguageCodeTwigExtension;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

$request = Request::createFromGlobals();

if ($user->perm->hasPermission($user->getUserId(), PermissionType::STATISTICS_VIEWLOGS->value)) {
    $perPage = 10;
    $pages = Filter::filterVar($request->query->get('pages'), FILTER_VALIDATE_INT);
    $page = Filter::filterVar($request->query->get('page'), FILTER_VALIDATE_INT, 1);

    $search = new Search($faqConfig);

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $twig->addExtension(new LanguageCodeTwigExtension());
    $template = $twig->loadTemplate('@admin/statistics/search.twig');

    $searchesCount = $search->getSearchesCount();
    $searchesList = $search->getMostPopularSearches($searchesCount + 1, true);

    if (is_null($pages)) {
        $pages = round(((is_countable($searchesList) ? count($searchesList) : 0) + ($perPage / 3)) / $perPage, 0);
    }

    $start = ($page - 1) * $perPage;
    $end = $start + $perPage;

    $baseUrl = sprintf(
        '%sadmin/?action=searchstats&amp;page=%d',
        $faqConfig->getDefaultUrl(),
        $page
    );

    // Pagination options
    $options = [
        'baseUrl' => $request->getUri(),
        'total' => is_countable($searchesList) ? count($searchesList) : 0,
        'perPage' => $perPage,
        'pageParamName' => 'page',
    ];
    $pagination = new Pagination($options);

    $templateVars = [
        'ad_menu_searchstats' => Translation::get('ad_menu_searchstats'),
        'csrfToken' => Token::getInstance()->getTokenString('truncate-search-terms'),
        'ad_searchterm_del' => Translation::get('ad_searchterm_del'),
        'ad_searchstats_search_term' => Translation::get('ad_searchstats_search_term'),
        'ad_searchstats_search_term_count' => Translation::get('ad_searchstats_search_term_count'),
        'ad_searchstats_search_term_lang' => Translation::get('ad_searchstats_search_term_lang'),
        'ad_searchstats_search_term_percentage' => Translation::get('ad_searchstats_search_term_percentage'),
        'pagination' => $pagination->render(),
        'searchesCount' => $searchesCount,
        'searchesList' => $searchesList,
        'csrfTokenDelete' => Token::getInstance()->getTokenString('delete-searchterm'),
        'ad_news_delete' => Translation::get('ad_news_delete'),
    ];

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
