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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-30
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
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
    #[Route('/statistics/search', name: 'admin.statistics.search', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $perPage = 10;

        $search = $this->container->get(id: 'phpmyfaq.search');
        $session = $this->container->get(id: 'session');

        $searchesCount = $search->getSearchesCount();
        $searchesList = $search->getMostPopularSearches($searchesCount + 1, true);

        // Pagination options
        $options = [
            'baseUrl' => $request->getUri(),
            'total' => is_countable($searchesList) ? count($searchesList) : 0,
            'perPage' => $perPage,
            'pageParamName' => 'page',
        ];
        $pagination = new Pagination($options);

        $this->addExtension(new AttributeExtension(LanguageCodeTwigExtension::class));
        return $this->render('@admin/statistics/search.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'msgAdminElasticsearchStats' => Translation::get(languageKey: 'msgAdminElasticsearchStats'),
            'csrfToken' => Token::getInstance($session)->getTokenString('truncate-search-terms'),
            'ad_searchterm_del' => Translation::get(languageKey: 'ad_searchterm_del'),
            'ad_searchstats_search_term' => Translation::get(languageKey: 'ad_searchstats_search_term'),
            'ad_searchstats_search_term_count' => Translation::get(languageKey: 'ad_searchstats_search_term_count'),
            'ad_searchstats_search_term_lang' => Translation::get(languageKey: 'ad_searchstats_search_term_lang'),
            'ad_searchstats_search_term_percentage' => Translation::get(
                languageKey: 'ad_searchstats_search_term_percentage',
            ),
            'pagination' => $pagination->render(),
            'searchesCount' => $searchesCount,
            'searchesList' => $searchesList,
            'csrfTokenDelete' => Token::getInstance($session)->getTokenString('delete-searchterm'),
            'ad_news_delete' => Translation::get(languageKey: 'ad_news_delete'),
        ]);
    }
}
