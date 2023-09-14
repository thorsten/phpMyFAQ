<?php

/**
 * Constants for phpMyFAQ, you can change them for your needs. Please back up this file
 * before updating phpMyFAQ as it will be overwritten.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-12-10
 * @codingStandardsIgnoreFile
 */

/**
 * Set the string below according to your users (main) timezone.
 * For your convenience find below the list of the available timezones:.
 *
 * https://www.php.net/manual/en/timezones.php
 *
 * Note: a wrong timezone setting could affect on-line users tracking as well as
 *       any filter criterion built using a date.
 * Note: timezone is a global setting i.e. no per-user setting.
 *
 * @var string
 */
const DATETIME_TIMEZONE_DEFAULT = 'Europe/Berlin';

//
// Set the default timezone used by all date/time functions
//
date_default_timezone_set(DATETIME_TIMEZONE_DEFAULT);

/**
 * Sets the current session save path if needed, by default, not used
 *
 * @var string
 */
const PMF_SESSION_SAVE_PATH = '';

/**
 * Timeout for the admin section, in minutes
 *
 * @var int
 */
const PMF_AUTH_TIMEOUT = 300;

/**
 * Timeout for the warning about session timeout for the admin section, in minutes
 *
 * @var int
 */
const PMF_AUTH_TIMEOUT_WARNING = 5;

/**
 * Start value for the solution IDs
 *
 * @var int
 */
const PMF_SOLUTION_ID_START_VALUE = 1000;

/**
 * Incremental value for the solution IDs
 *
 * @var int
 */
const PMF_SOLUTION_ID_INCREMENT_VALUE = 1;

/**
 * Number of records for the Top 10
 *
 * @var int
 */
const PMF_NUMBER_RECORDS_TOPTEN = 10;

/**
 * Number of records for the latest entries
 *
 * @var int
 */
const PMF_NUMBER_RECORDS_LATEST = 5;

/**
 * This is the flag with which Google sitemap will be forced to use the current PMF SEO URL schema
 *
 * @var bool
 */
const PMF_SITEMAP_GOOGLE_USE_SEO = true;

/**
 * This is the flag with which the Tags Cloud list is limited to
 *
 * @var int
 */
const PMF_TAGS_CLOUD_RESULT_SET_SIZE = 50;

/**
 * This is the flag with which the auto complete list is limited to
 *
 * @var int
 */
const PMF_TAGS_AUTOCOMPLETE_RESULT_SET_SIZE = 20;

/**
 * This is the flag for choosing the encryption type for passwords. Currently,
 * you can choose
 *
 * - hash (default)
 * - bcrypt
 * - crypt (not recommended, marked as deprecated, will be removed with v3.3)
 *
 * WARNING: DO NOT CHANGE THIS VALUE AFTER YOUR INITIAL INSTALLATION!
 * OTHERWISE, ALL YOUR REGISTERED USERS HAVE TO REQUEST A NEW PASSWORD.
 *
 * @var string
 */
const PMF_ENCRYPTION_TYPE = 'hash';

/**
 * List of denied extensions when uploading a file.
 * Here is an example:
 * <code>
 * $denyUploadExts = array(
 *     '',         // Files with no extension not allowed
 *     'shtml',    // SSI files not allowed
 *     'php',      // PHP files not allowed
 *     'php3',
 *     'php4',
 *     'cgi',      // CGI not allowed
 *     'pl',
 *     'exe'       // Win executables not allowed
 * );
 * </code>
 *
 * @var string[]
 */
$denyUploadExts = [];
