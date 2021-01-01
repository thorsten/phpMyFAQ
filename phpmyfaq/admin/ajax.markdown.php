<?php

/**
 * Markdown Ajax Parser.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Jerry van Kooten <jerry@jvkooten.info>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2015-03-30
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$answer = Filter::filterInput(INPUT_POST, 'text', FILTER_SANITIZE_STRING);
$http = new HttpHelper();
$http->addHeader();

$parsedown = new ParsedownExtra();

$http->sendWithHeaders($parsedown->text($answer));
