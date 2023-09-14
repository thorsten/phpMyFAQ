<?php

/**
 * The error page if a user has no permission to view the requested resource.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-06-11
 */

use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;

$twig = new TwigWrapper('./assets/templates');
$template = $twig->loadTemplate('./no-permission.twig');

echo $template->render(
    [
        'adminHeaderNoPermission' => Translation::get('ad_entryins_fail'),
        'msgNoPermission' => Translation::get('err_NotAuth')
    ]
);
