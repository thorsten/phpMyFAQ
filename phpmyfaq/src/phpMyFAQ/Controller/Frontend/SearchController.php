<?php

/**
 * Search Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use Exception;
use League\CommonMark\Exception\CommonMarkException;
use phpMyFAQ\Filter;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Search\SearchService;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\TagNameTwigExtension;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Extension\AttributeExtension;
use Twig\TwigFilter;

final class SearchController extends AbstractFrontController
{
    /**
     * Redirects tag URLs with pagination to search
     *
     * @throws Exception
     */
    #[Route(path: '/tags/{tagId}/{page}/{slug}.html', name: 'public.tags.paginated', methods: ['GET'])]
    public function tagsPaginated(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $tagId = Filter::filterVar($request->attributes->get('tagId'), FILTER_VALIDATE_INT, 0);
        $page = Filter::filterVar($request->attributes->get('page'), FILTER_VALIDATE_INT, 1);

        return new RedirectResponse(sprintf('/search.html?tagging_id=%d&seite=%d', $tagId, $page));
    }

    /**
     * Redirects tag URLs to search
     *
     * @throws Exception
     */
    #[Route(path: '/tags/{tagId}/{slug}.html', name: 'public.tags', methods: ['GET'])]
    public function tags(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $tagId = Filter::filterVar($request->attributes->get('tagId'), FILTER_VALIDATE_INT, 0);

        return new RedirectResponse(sprintf('/search.html?tagging_id=%d', $tagId));
    }

    /**
     * Displays search results for fulltext or tag-based search.
     *
     * @throws Exception|CommonMarkException
     */
    #[Route(path: '/search.html', name: 'public.search', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Get user input
        $inputLanguage = Filter::filterVar($request->query->get('pmf-all-languages'), FILTER_SANITIZE_SPECIAL_CHARS);
        $inputCategory = Filter::filterVar($request->query->get('pmf-search-category'), FILTER_VALIDATE_INT, '%');
        $inputSearchTerm = Filter::filterVar($request->query->get('search'), FILTER_SANITIZE_SPECIAL_CHARS);
        $inputSearchTerm = Strings::substr($inputSearchTerm, 0, 255);

        $inputTag = Filter::filterVar($request->query->get('tagging_id'), FILTER_SANITIZE_SPECIAL_CHARS);

        if (!is_null($inputTag)) {
            $inputTag = str_replace(' ', '', (string) $inputTag);
            $inputTag = str_replace(',,', ',', $inputTag);
        }

        $searchTerm = Filter::filterVar($request->attributes->get('search'), FILTER_SANITIZE_SPECIAL_CHARS);
        $searchTerm = Strings::substr($searchTerm, 0, 255);

        $page = Filter::filterVar($request->query->get('seite'), FILTER_VALIDATE_INT, 1);

        // Determine search language scope
        $allLanguages = $inputLanguage !== '';

        // Merge search terms
        if ($searchTerm !== '') {
            $inputSearchTerm = $searchTerm;
        }

        // Track user session
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('fulltext_search', 0);
        $faqSession->userTracking('fulltext_search', $inputSearchTerm);

        // Get current groups
        $currentGroups = $this->currentUser->perm->getUserGroups($this->currentUser->getUserId());

        // Initialize search service
        $searchService = new SearchService($this->configuration, $this->currentUser, $currentGroups);

        // Process search
        $searchData = $searchService->processSearch(
            $inputSearchTerm,
            $inputTag ?? '',
            $inputCategory,
            $allLanguages,
            $page,
        );

        // Check for solution ID redirect
        if ($searchService->shouldRedirectToSolutionId($inputSearchTerm, $searchData['numberOfSearchResults'])) {
            $redirectResponse = new RedirectResponse($searchService->getSolutionIdRedirectUrl($inputSearchTerm));
            $redirectResponse->send();
            exit();
        }

        // Set up Twig extensions
        $this->addExtension(new AttributeExtension(TagNameTwigExtension::class));
        $this->addFilter(new TwigFilter('repeat', static fn($string, $times): string => str_repeat(
            (string) $string,
            $times,
        )));

        // Determine page header
        $pageHeader = $searchData['tagSearch']
            ? Translation::get(key: 'msgTagSearch')
            : Translation::get(key: 'msgAdvancedSearch');

        // Get plurals helper
        $plurals = new Plurals();

        // Render template
        return $this->render('search.twig', [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', $pageHeader, $this->configuration->getTitle()),
            'pageHeader' => $pageHeader,
            'isTagSearch' => $searchData['tagSearch'],
            'selectedCategory' => $searchData['selectedCategory'],
            'categories' => $searchData['categories'],
            'msgSearch' => Translation::get(key: 'msgSearch'),
            'msgAdvancedSearch' => $searchData['tagSearch']
                ? Translation::get(key: 'msgTagSearch')
                : Translation::get(key: 'msgAdvancedSearch'),
            'msgCurrentTags' => Translation::get(key: 'msg_tags'),
            'numberOfSearchResults' => $searchData['numberOfSearchResults'],
            'totalPages' => $searchData['totalPages'],
            'msgPage' => Translation::get(key: 'msgPage'),
            'currentPage' => $searchData['currentPage'],
            'from' => Translation::get(key: 'msgVoteFrom'),
            'msgSearchResults' => $plurals->GetMsg('plmsgSearchAmount', $searchData['numberOfSearchResults'] ?? 0),
            'msgSearchResultsPagination' => $plurals->GetMsg('plmsgPagesTotal', $searchData['totalPages'] ?? 0),
            'searchTerm' => $searchData['searchTerm'],
            'searchTags' => $searchData['searchTags'],
            'msgSearchWord' => Translation::get(key: 'msgSearchWord'),
            'searchResults' => $searchData['searchResults'],
            'formActionUrl' => './search.html',
            'searchString' => $inputSearchTerm,
            'searchOnAllLanguages' => Translation::get(key: 'msgSearchOnAllLanguages'),
            'checkedAllLanguages' => $searchData['allLanguages'] ? ' checked' : '',
            'selectCategories' => Translation::get(key: 'msgSelectCategories'),
            'allCategories' => Translation::get(key: 'msgAllCategories'),
            'noSearchResults' => Translation::get(key: 'msgErrorNoRecords'),
            'pagination' => $searchData['pagination'],
            'msgMostPopularSearches' => Translation::get(key: 'msgMostPopularSearches'),
            'mostPopularSearches' => $searchData['mostPopularSearches'],
            'relatedTagsHeader' => Translation::get(key: 'msgRelatedTags'),
            'relatedTags' => $searchData['relatedTags'],
            'msgTags' => Translation::get(key: 'msgPopularTags'),
            'tagList' => $searchData['tagList'],
        ]);
    }
}
