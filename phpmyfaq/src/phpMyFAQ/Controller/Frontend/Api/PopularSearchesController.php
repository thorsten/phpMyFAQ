<?php

/**
 * The Popular Searches Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-06-20
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Search;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PopularSearchesController extends AbstractController
{
    public function __construct(
        private readonly Search $faqSearch,
    ) {
        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'searches/popular', name: 'api.private.searches.popular', methods: ['GET'])]
    public function popular(): JsonResponse
    {
        $numberOfResults = (int) $this->configuration->get('search.numberSearchTerms');
        if ($numberOfResults <= 0) {
            $numberOfResults = 7;
        }

        // Aggregate across all languages (no withLang), consistent with the existing
        // frontend "most popular searches" display in SearchController/search.twig.
        $result = $this->faqSearch->getMostPopularSearches($numberOfResults);

        if ($result === []) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
    }
}
