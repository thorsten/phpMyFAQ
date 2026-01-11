<?php

/**
 * The Search statistics Administration Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-30
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Pagination;
use phpMyFAQ\Pagination\UrlConfig;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\LanguageCodeTwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;

final class StatisticsSearchController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/statistics/search', name: 'admin.statistics.search', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $perPage = 10;

        $search = $this->container->get(id: 'phpmyfaq.search');

        $searchesCount = $search->getSearchesCount();
        $searchesList = $search->getMostPopularSearches($searchesCount + 1, true);

        $pagination = new Pagination(
            baseUrl: $request->getUri(),
            total: is_countable($searchesList) ? count($searchesList) : 0,
            perPage: $perPage,
            urlConfig: new UrlConfig(pageParamName: 'page'),
        );

        $this->addExtension(new AttributeExtension(LanguageCodeTwigExtension::class));
        return $this->render('@admin/statistics/search.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'msgAdminElasticsearchStats' => Translation::get(key: 'msgAdminElasticsearchStats'),
            'csrfToken' => Token::getInstance($this->session)->getTokenString('truncate-search-terms'),
            'ad_searchterm_del' => Translation::get(key: 'ad_searchterm_del'),
            'ad_searchstats_search_term' => Translation::get(key: 'ad_searchstats_search_term'),
            'ad_searchstats_search_term_count' => Translation::get(key: 'ad_searchstats_search_term_count'),
            'ad_searchstats_search_term_lang' => Translation::get(key: 'ad_searchstats_search_term_lang'),
            'ad_searchstats_search_term_percentage' => Translation::get(key: 'ad_searchstats_search_term_percentage'),
            'pagination' => $pagination->render(),
            'searchesCount' => $searchesCount,
            'searchesList' => $searchesList,
            'csrfTokenDelete' => Token::getInstance($this->session)->getTokenString('delete-searchterm'),
            'ad_news_delete' => Translation::get(key: 'ad_news_delete'),
        ]);
    }
}
