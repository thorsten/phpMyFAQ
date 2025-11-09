<?php

/**
 * The Ratings Controller
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
 * @since     2024-11-24
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Category;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class RatingController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/statistics/rating', name: 'admin.statistics.rating', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, [], false);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);

        $ratingData = $this->container->get('phpmyfaq.admin.rating-data');

        $data = $ratingData->getAll();
        $numberOfRatings = is_countable($data) ? count($data) : 0;
        $currentCategory = 0;

        return $this->render('@admin/statistics/ratings.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'csrfToken' => Token::getInstance($this->container->get('session'))->getTokenString('clear-statistics'),
            'currentCategory' => $currentCategory,
            'ratingData' => $data,
            'numberOfRatings' => $numberOfRatings,
            'categoryNames' => $category->categoryNames,
            'adminHeaderRatings' => Translation::get(languageKey: 'ad_rs'),
            'buttonDeleteAllVotings' => Translation::get(languageKey: 'ad_delete_all_votings'),
            'green' => Translation::get(languageKey: 'ad_rs_green'),
            'greenNote' => Translation::get(languageKey: 'ad_rs_ahtf'),
            'red' => Translation::get(languageKey: 'ad_rs_red'),
            'redNote' => Translation::get(languageKey: 'ad_rs_altt'),
            'msgNoRatings' => Translation::get(languageKey: 'ad_rs_no'),
        ]);
    }
}
