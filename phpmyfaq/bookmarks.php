<?php

/**
 * Shows the page with the user's bookmarks.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2002-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-20
 */

use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Bookmark;
use phpMyFAQ\Translation;
use phpMyFAQ\Session\Token;
use Symfony\Component\HttpFoundation\RedirectResponse;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$user = $container->get('phpmyfaq.user.current_user');

if ($user->isLoggedIn()) {
    $bookmark = new Bookmark($faqConfig, $user);

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
    $twigTemplate = $twig->loadTemplate('./bookmarks.twig');

    // Twig template variables
    $templateVars = [
        ... $templateVars,
        'title' => sprintf('%s - %s', Translation::get('msgBookmarks'), $faqConfig->getTitle()),
        'bookmarksList' => $bookmark->getBookmarkList(),
        'csrfTokenDeleteBookmark' => Token::getInstance()->getTokenString('delete-bookmark'),
        'csrfTokenDeleteAllBookmarks' => Token::getInstance()->getTokenString('delete-all-bookmarks')
    ];

    return $templateVars;
} else {
    // Redirect to log in
    $response = new RedirectResponse($faqConfig->getDefaultUrl());
    $response->send();
}
