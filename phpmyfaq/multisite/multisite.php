<?php
/**
 * Multisite support for phpMyFAQ.
 *
 * HowTo:
 *  - Rename this file to <DOCROOT>/multisite/multisite.php
 *      i.e. /srv/www/faq.example.org/multisite/multisite.php
 *  - create a folder that's called like the SERVER_NAME inside <DOCROOT>/multi/
 *      i.e. /srv/www/faq.example.org/multisite/otherfaq.example.org
 *  - that is your config folder with the usual contents like database.php
 *
 * If you don't plan to use multisite support, just delete the multisite directory.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Multisite
 * @author    Florian Anderiasch <florian@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-14
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

$protocol = 'http';
if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
    $protocol = 'https';
}
$parsed = parse_url($protocol . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME']);
if (isset($parsed['host']) && strlen($parsed['host']) > 0 && is_dir(__DIR__ . '/' . $parsed['host'])) {
    define('PMF_MULTI_INSTANCE_CONFIG_DIR', __DIR__ . '/' . $parsed['host']);
    unset($parsed);
}