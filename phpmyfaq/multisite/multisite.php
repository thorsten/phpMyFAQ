<?php

/*************************************************
 * Multisite support for phpMyFAQ.
 * HowTo:
 *  - Rename this file to <DOCROOT>/multisite/multisite.php
 *      i.e. /srv/www/faq.example.org/multisite/multisite.php
 *  - create a folder that's called like the SERVER_NAME inside <DOCROOT>/multi/
 *      i.e. /srv/www/faq.example.org/multisite/otherfaq.example.org
 *  - that is your config folder with the usual contents like database.php
 *
 * If you don't plan to use multisite support, just delete the multisite directory.
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

$parsed = parse_url('http://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME']);
if (isset($parsed['host']) && strlen($parsed['host']) > 0 && is_dir(__DIR__ . '/' . $parsed['host'])) {
    define('PMF_MULTI_INSTANCE_CONFIG_DIR', __DIR__ . '/' . $parsed['host']);
    unset($parsed);
}


