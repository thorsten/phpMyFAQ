<?php
/**
 * Constants for phpMyFAQ
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * @category  phpMyFAQ
 * @package   Setup
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @since     2003-12-10
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @copyright 2003-2011 phpMyFAQ Team
 */

/**
 * Set the string below according to your users (main) timezone.
 * For your convenience find below the list of the available timezones:
 * 
 * http://www.php.net/manual/en/timezones.php   
 * 
 * Note: a wrong timezone setting could affect on-line users tracking as well as
 *       any filter criterion built using a date.
 * Note: timezone is a global setting i.e. no per-user setting. 
 *
 * @var string
 */
define('PMF_DATETIME_TIMEZONE_DEFAULT', 'Europe/Berlin');

/**
 * Timeout for the admin section, in minutes
 *
 * @var integer
 */
define('PMF_AUTH_TIMEOUT', 30);

/**
 * Timeout for the warning about session timeout for the admin section, in minutes
 *
 * @var integer
 */
define('PMF_AUTH_TIMEOUT_WARNING', 5);

/**
 * Start value for the solution IDs
 *
 * @var const
 */
define('PMF_SOLUTION_ID_START_VALUE', 1000);

/**
 * Incremental value for the solution IDs
 *
 * @var const
 */
define('PMF_SOLUTION_ID_INCREMENT_VALUE', 1);

/**
 * Number of records for the Top 10
 *
 * @var const   10
 */
define('PMF_NUMBER_RECORDS_TOPTEN', 10);

/**
 * Number of records for the latest entries
 *
 * @var const   5
 */
define('PMF_NUMBER_RECORDS_LATEST', 5);

/**
 * This is the max number of open questions returned by the feed
 *
 * @var const   50
 */
define("PMF_RSS_OPENQUESTIONS_MAX", 50);

/**
 * This is the flag with which Latest and Topten RSS feeds will be forced to use the current PMF SEO URL schema
 *
 * @var const   true
 */
define("PMF_RSS_USE_SEO", true);

/**
 * This is the flag with which Google sitemap will be forced to use the current PMF SEO URL schema
 *
 * @var const   true
 */
define("PMF_SITEMAP_GOOGLE_USE_SEO", true);

/**
 * This is the flag with which Yahoo sitemap will be forced to use the current PMF SEO URL schema
 *
 * @var const   true
 */
define("PMF_SITEMAP_YAHOO_USE_SEO", true);

/**
 * This is the flag with which the Tags Cloud list is limited to
 *
 * @var const   50
 */
define("PMF_TAGS_CLOUD_RESULT_SET_SIZE", 50);

/**
 * This is the flag with which the Autocomplete list is limited to
 *
 * @var const   20
 */
define("PMF_TAGS_AUTOCOMPLETE_RESULT_SET_SIZE", 20);

/**
 * This is the flag for choosing the encryption type for passwords. Currently
 * you can choose
 *
 * - hash (default)
 * - sha1
 * - crypt
 * - md5 (default before phpMyFAQ 2.8)
 *
 * @var const
 */
define('PMF_ENCRYPTION_TYPE', 'hash');

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
$denyUploadExts = array();

/**
 * Bot Blacklist
 *
 * @var array
 */
$botBlacklist = array(
    'nustcrape',
    'webpost',
    'GoogleBot',
    'msnbot',
    'crawler',
    'scooter',
    'bravobrian',
    'archiver',
    'w3c',
    'control',
    'wget',
    'bot',
    'spider',
    'Yahoo! Slurp',
    'htdig'
);

/****************************************************************************
 *                  DO NOT CHANGE ANYTHING BELOW THIS LINE!                 *
 ****************************************************************************/

/**
 * Supported databases for phpMyFAQ.
 *
 * @var  array
 */
$supported_databases = array(
    'mysql'   => array('5.2.0', 'MySQL 5.x (ext/mysql)'),
    'pgsql'   => array('5.2.0', 'PostgreSQL 8.x'),
    'sybase'  => array('5.2.0', 'Sybase'),
    'mssql'   => array('5.2.0', 'MS SQL Server 2005 / 2008'),
    'mysqli'  => array('5.2.0', 'MySQL 5.x (ext/mysqli)'),
    'sqlite'  => array('5.2.0', 'SQLite'),
    'ibm_db2' => array('5.2.0', 'IBM DB2 Universal Database 8.2 / 9.1'),
    'sqlsrv'  => array('5.2.0', 'SQL Server Driver for PHP (experimental)'),
    'ibase'   => array('5.2.0', 'Interbase/Firebird (experimental)'),
    'mysqli'  => array('5.2.0', 'MariaDB 5.x (experimental)')
);

/**
 * Allowed 'action' variables for GET. DO NOT CHANGE!
 *
 * @var array
 */
$allowedVariables = array(
    'add'             => 1,
    'artikel'         => 1,
    'ask'             => 1,
    'attachment'      => 1,
    'contact'         => 1,
    'help'            => 1,
    'instantresponse' => 1,
    'login'           => 1,
    'mailsend2friend' => 1,
    'news'            => 1,
    'open'            => 1,
    'register'        => 1,
    'save'            => 1,
    'savecomment'     => 1,
    'savequestion'    => 1,
    'savevoting'      => 1,
    'search'          => 1,
    'send2friend'     => 1,
    'sendmail'        => 1,
    'show'            => 1,
    'sitemap'         => 1,
    'thankyou'        => 1,
    'translate'       => 1,
    'ucp'             => 1,
    'writecomment'    => 1
);

/**
 * ISO 639 language code list - DO NOT CHANGE!
 *
 * @var  array
 */
$languageCodes = array(
    'AF' =>     'Afghanistan',
    'AA' =>     'Afar',
    'AB' =>     'Abkhazian',
    'AF' =>     'Afrikaans',
    'AM' =>     'Amharic',
    'AR' =>     'Arabic',
    'AS' =>     'Assamese',
    'AY' =>     'Aymara',
    'AZ' =>     'Azerbaijani',
    'BA' =>     'Bashkir',
    'BE' =>     'Byelorussian',
    'BG' =>     'Bulgarian',
    'BH' =>     'Bihari',
    'BI' =>     'Bislama',
    'BN' =>     'Bengali',
    'BO' =>     'Tibetan',
    'BR' =>     'Breton',
    'CA' =>     'Catalan',
    'CO' =>     'Corsican',
    'CS' =>     'Czech',
    'CY' =>     'Welsh',
    'DA' =>     'Danish',
    'DE' =>     'German',
    'DZ' =>     'Bhutani',
    'EL' =>     'Greek',
    'EN' =>     'English',
    'EO' =>     'Esperanto',
    'ES' =>     'Spanish',
    'ET' =>     'Estonian',
    'EU' =>     'Basque',
    'FA' =>     'Persian',
    'FI' =>     'Finnish',
    'FJ' =>     'Fiji',
    'FO' =>     'Faeroese',
    'FR' =>     'French',
    'FY' =>     'Frisian',
    'GA' =>     'Irish',
    'GD' =>     'Gaelic',
    'GL' =>     'Galician',
    'GN' =>     'Guarani',
    'GU' =>     'Gujarati',
    'HA' =>     'Hausa',
    'HE' =>     'Hebrew',
    'HI' =>     'Hindi',
    'HR' =>     'Croatian',
    'HU' =>     'Hungarian',
    'HY' =>     'Armenian',
    'IA' =>     'Interlingua',
    'IE' =>     'Interlingue',
    'IK' =>     'Inupiak',
    'ID' =>     'Indonesian',
    'IS' =>     'Icelandic',
    'IT' =>     'Italian',
    'IW' =>     'Hebrew',
    'JA' =>     'Japanese',
    'JI' =>     'Yiddish',
    'JW' =>     'Javanese',
    'KA' =>     'Georgian',
    'KK' =>     'Kazakh',
    'KL' =>     'Greenlandic',
    'KM' =>     'Cambodian',
    'KN' =>     'Kannada',
    'KO' =>     'Korean',
    'KS' =>     'Kashmiri',
    'KU' =>     'Kurdish',
    'KY' =>     'Kirghiz',
    'LA' =>     'Latin',
    'LN' =>     'Lingala',
    'LO' =>     'Laothian',
    'LT' =>     'Lithuanian',
    'LV' =>     'Latvian',
    'MG' =>     'Malagasy',
    'MI' =>     'Maori',
    'MK' =>     'Macedonian',
    'ML' =>     'Malayalam',
    'MN' =>     'Mongolian',
    'MO' =>     'Moldavian',
    'MR' =>     'Marathi',
    'MS' =>     'Malay',
    'MT' =>     'Maltese',
    'MY' =>     'Burmese',
    'NA' =>     'Nauru',
    'NE' =>     'Nepali',
    'NL' =>     'Dutch',
    'NB' =>     'Norwegian Bokm&aring;l',
    'NN' =>     'Norwegian Nynorsk',
    'OC' =>     'Occitan',
    'OM' =>     'Oromo',
    'OR' =>     'Oriya',
    'PA' =>     'Punjabi',
    'PL' =>     'Polish',
    'PS' =>     'Pashto',
    'PT' =>     'Portuguese',
    'PT-BR' =>  'Brazilian Portuguese',
    'QU' =>     'Quechua',
    'RN' =>     'Kirundi',
    'RO' =>     'Romanian',
    'RU' =>     'Russian',
    'RW' =>     'Kinyarwanda',
    'SA' =>     'Sanskrit',
    'SD' =>     'Sindhi',
    'SG' =>     'Sangro',
    'SH' =>     'Serbo-Croatian',
    'SI' =>     'Singhalese',
    'SK' =>     'Slovak',
    'SL' =>     'Slovenian',
    'SM' =>     'Samoan',
    'SN' =>     'Shona',
    'SO' =>     'Somali',
    'SQ' =>     'Albanian',
    'SR' =>     'Serbian',
    'SS' =>     'Siswati',
    'ST' =>     'Sesotho',
    'SU' =>     'Sudanese',
    'SV' =>     'Swedish',
    'SW' =>     'Swahili',
    'TA' =>     'Tamil',
    'TE' =>     'Tegulu',
    'TG' =>     'Tajik',
    'TH' =>     'Thai',
    'TI' =>     'Tigrinya',
    'TK' =>     'Turkmen',
    'TL' =>     'Tagalog',
    'TN' =>     'Setswana',
    'TO' =>     'Tonga',
    'TR' =>     'Turkish',
    'TS' =>     'Tsonga',
    'TT' =>     'Tatar',
    'TW' =>     'Chinese (Traditional)',
    'UK' =>     'Ukrainian',
    'UR' =>     'Urdu',
    'UZ' =>     'Uzbek',
    'VI' =>     'Vietnamese',
    'VO' =>     'Volapuk',
    'WO' =>     'Wolof',
    'XH' =>     'Xhosa',
    'YO' =>     'Yoruba',
    'ZH' =>     'Chinese (Simplified)',
    'ZU' =>     'Zulu'
);

//
// Define some internal constants
//
// Sessions
define('PMF_COOKIE_NAME_AUTH', 'pmf_auth');
define('PMF_COOKIE_NAME_LANGUAGE', 'pmf_lang');
define('PMF_COOKIE_NAME_SESSIONID', 'pmf_sid');
// HTTP GET parameters
define('PMF_GET_KEY_NAME_LANGUAGE', 'lang');
define('PMF_GET_KEY_NAME_SESSIONID', 'sid');
// Misc parameters
define('PMF_LANGUAGE_EXPIRED_TIME', 3600);
define('PMF_SESSION_EXPIRED_TIME', 3600);

//
// Set the default timezone used by all date/time functions
//
date_default_timezone_set(PMF_DATETIME_TIMEZONE_DEFAULT);
