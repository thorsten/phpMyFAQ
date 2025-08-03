#!/usr/bin/php
<?php

/**
 * This deletes all tables from the database with a given prefix.
 *
 * Example usage for prefix "foo":
 * $ php scripts/drop-tables.php foo
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-02-23
 */

use phpMyFAQ\Database;

define('PMF_ROOT_DIR', dirname(__DIR__) . '/phpmyfaq');

require PMF_ROOT_DIR . '/src/constants.php';
require PMF_ROOT_DIR . '/src/autoload.php';
require PMF_ROOT_DIR . '/content/core/config/database.php';

try {
    Database::setTablePrefix($DB['prefix']);
    $database = Database::factory($DB['type']);
    $database->connect('127.0.0.1', $DB['user'], $DB['password'], $DB['db'], isset($DB['port']) ? $DB['port'] : null);
} catch (Exception $e) {
    Database::errorPage($e->getMessage());
    exit(-1);
}

$prefix = $argv[1];

try {
    $database->query(
        'DROP TABLE ' .
        $prefix . 'faqadminlog, ' . $prefix . 'faqattachment, ' . $prefix . 'faqattachment_file, ' .
        $prefix . 'faqcaptcha, ' . $prefix . 'faqcategories, ' . $prefix . 'faqcategoryrelations, ' .
        $prefix . 'faqcategory_group, ' . $prefix . 'faqcategory_news, ' . $prefix . 'faqcategory_order, ' .
        $prefix . 'faqcategory_user, ' . $prefix . 'faqchanges, ' . $prefix . 'faqcomments, ' .
        $prefix . 'faqconfig, ' . $prefix . 'faqdata, ' . $prefix . 'faqdata_group, ' .
        $prefix . 'faqdata_revisions, ' . $prefix . 'faqdata_tags, ' . $prefix . 'faqdata_user, ' .
        $prefix . 'faqglossary, ' . $prefix . 'faqgroup, ' . $prefix . 'faqgroup_right, ' .
        $prefix . 'faqinstances, ' . $prefix . 'faqinstances_config, ' . $prefix . 'faqmeta, ' . $prefix . 'faqnews, ' .
        $prefix . 'faqquestions, ' . $prefix . 'faqright, ' . $prefix . 'faqsearches, ' . $prefix . 'faqsessions, ' .
        $prefix . 'faqstopwords, ' . $prefix . 'faqtags, ' . $prefix . 'faquser, ' . $prefix . 'faquserdata, ' .
        $prefix . 'faquserlogin, ' . $prefix . 'faquser_group, ' . $prefix . 'faquser_right, ' .
        $prefix . 'faqvisits, ' . $prefix . 'faqvoting'
    );
} catch (Exception $e) {
    echo $e->getMessage();
    exit(-1);
}
