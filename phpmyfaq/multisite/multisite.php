<?php

/**
 * Multisite support for phpMyFAQ.
 *
 * HowTo:
 *  - Rename this file to <DOCROOT>/multisite/multisite.php
 *      e.g. /srv/www/faq.example.org/multisite/multisite.php
 *  - create a folder that's called like the SERVER_NAME inside <DOCROOT>/multi/
 *      e.g. /srv/www/faq.example.org/multisite/otherfaq.example.org
 *  - that is your config folder with the usual contents like database.php
 *
 * If you don't plan to use multisite support, just delete the multisite directory.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v.2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Florian Anderiasch <florian@phpmyfaq.de>
 * @copyright 2012-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-14
 */

use phpMyFAQ\Configuration\MultisiteConfigurationLocator;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();
$multiSiteDirectory = __DIR__;
$configurationDirectory = MultisiteConfigurationLocator::locateConfigurationDirectory($request, $multiSiteDirectory);

if ($configurationDirectory !== null) {
    define('PMF_MULTI_INSTANCE_CONFIG_DIR', $configurationDirectory);
}
