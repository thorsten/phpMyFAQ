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
 * @copyright 2002-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-20
 */

use Symfony\Component\HttpFoundation\Request;
use phpMyFAQ\Translation;
use phpMyFAQ\Bookmark;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();

$bookmark = new Bookmark($faqConfig, $user);

$template->parse(
        'mainPageContent',
        [
            'msgMyBookmarks' => Translation::get('msgMyBookmarks'),
            'bookmarkContent' => $bookmark->renderBookmarkTree(),
        ]
);
