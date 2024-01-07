<?php

/**
 * The page with the ratings.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Filter;
use phpMyFAQ\Rating;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Twig\Extension\DebugExtension;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

[ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($user);

if ($user->perm->hasPermission($user->getUserId(), 'viewlog')) {
    $csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('./admin/statistics/ratings.twig');

    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $ratings = new Rating($faqConfig);

    if ($csrfToken && !Token::getInstance()->verifyToken('clear-statistics', $csrfToken)) {
        $clearStatistics = false;
    } else {
        $clearStatistics = true;
    }

    if ('clear-statistics' === $action && $clearStatistics) {
        if ($ratings->deleteAll()) {
            $deletedStatistics = true;
        } else {
            $deletedStatistics = false;
        }
    }

    $ratingData = $ratings->getAllRatings();
    $numberOfRatings = is_countable($ratingData) ? count($ratingData) : 0;
    $currentCategory = 0;

    $templateVars = [
        'adminHeaderRatings' => Translation::get('ad_rs'),
        'csrfToken' => Token::getInstance()->getTokenString('clear-statistics'),
        'buttonDeleteAllVotings' => Translation::get('ad_delete_all_votings'),
        'isDeleteAllVotings' => 'clear-statistics' === $action && $clearStatistics,
        'isDeletedStatistics' => $deletedStatistics ?? false,
        'msgDeleteAllVotings' => 'Statistics successfully deleted.',
        'msgDeleteAllVotingsError' => 'Statistics not deleted.',
        'currentCategory' => $currentCategory,
        'ratingData' => $ratingData,
        'numberOfRatings' => $numberOfRatings,
        'categoryNames' => $category->categoryName,
        'green' => Translation::get('ad_rs_green'),
        'greenNote' => Translation::get('ad_rs_ahtf'),
        'red' => Translation::get('ad_rs_red'),
        'redNote' => Translation::get('ad_rs_altt'),
        'msgNoRatings' => Translation::get('ad_rs_no')
    ];

    echo $template->render($templateVars);
} else {
    require 'no-permission.php';
}
