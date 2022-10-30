<?php

/**
 * Constants for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2022 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-12-10
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

/**
 * Sets the current session save path if needed, by default not used
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
 * This is the flag for choosing the encryption type for passwords. Currently
 * you can choose
 *
 * - hash (default)
 * - bcrypt
 * - crypt (not recommended)
 *
 * WARNING: DO NOT CHANGE THIS VALUE AFTER YOUR INITIAL INSTALLATION!
 * OTHERWISE ALL YOUR REGISTERED USERS HAVE TO REQUEST A NEW PASSWORD.
 *
 * @var string
 */
const PMF_ENCRYPTION_TYPE = 'hash';

/**
 * List of denied extensions when uploading a file.
 *
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
 * @var array
 */
$denyUploadExts = [];

/****************************************************************************
 *                  DO NOT CHANGE ANYTHING BELOW THIS LINE!                 *
 ****************************************************************************/

/*
 * Allowed 'action' variables for GET. DO NOT CHANGE!
 *
 * @var array
 * @deprecated will be removed in v3.2
 */
$allowedVariables = [
    'add' => 1,
    'faq' => 1,
    'artikel' => 1,
    'ask' => 1,
    'attachment' => 1,
    'contact' => 1,
    'glossary' => 1,
    'help' => 1,
    'login' => 1,
    'mailsend2friend' => 1,
    'news' => 1,
    'open-questions' => 1,
    'overview' => 1,
    'password' => 1,
    'register' => 1,
    'request-removal' => 1,
    'save' => 1,
    'savecomment' => 1,
    'savequestion' => 1,
    'savevoting' => 1,
    'search' => 1,
    'send2friend' => 1,
    'sendmail' => 1,
    'show' => 1,
    'sitemap' => 1,
    'thankyou' => 1,
    'translate' => 1,
    'ucp' => 1,
    'writecomment' => 1,
    '404' => 1
];

//
// Define some internal constants
//

// HTTP GET parameters
const PMF_GET_KEY_NAME_SESSIONID = 'sid';
// Misc parameters
const PMF_LANGUAGE_EXPIRED_TIME = 3600; // 30 minutes
const PMF_SESSION_EXPIRED_TIME = 3600; // 30 minutes
const PMF_REMEMBER_ME_EXPIRED_TIME = 1209600; // 2 weeks

//
// Set the default timezone used by all date/time functions
//
date_default_timezone_set(DATETIME_TIMEZONE_DEFAULT);
