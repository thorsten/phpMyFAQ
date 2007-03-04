<?php
/**
* $Id: constants.php,v 1.29 2007-03-04 10:03:15 thorstenr Exp $
*
* Constants for phpMyFAQ
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Matteo Scaramuccia <matteo@scaramuccia.com>
* @since        2003-12-10
* @copyright    (c) 2003-2007 phpMyFAQ Team
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
*/

/**
 * PHP 6 script encoding
 *
 */
declare(encoding='latin1');

/**
 * Use this constant if you want to change your timezone
 *
 * @var integer
 */
define('PMF_DATETIME_TIMEZONE', '0'); // default: "0", example: "-0400" for 4 hours before

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
 * List of denied extensions when uploading a file.
 * Here is an example:
 * <code>
 * $denyUploadExts = array(
 *                 '',         // Files with no extension not allowed
 *                 'shtml',    // SSI files not allowed
 *                 'php',      // PHP files not allowed
 *                 'php3',
 *                 'php4',
 *                 'cgi',      // CGI not allowed
 *                 'pl',
 *                 'exe'       // Win executables not allowed
 *                 );
 * </code>
 *
 * @var array
 */
$denyUploadExts = array();


/****************************************************************************
 *                  DO NOT CHANGE ANYTHING BELOW THIS LINE!                 *
 ****************************************************************************/

/**
 * Supported databases for phpMyFAQ
 *
 * @var  array
 */
$supported_databases = array(
    'mysql'     => array('4.3.0', 'MySQL 4.x / 5.x (PHP4 / PHP5)'),
    'pgsql'     => array('4.3.0', 'PostgreSQL 7.x / 8.x'),
    'sybase'    => array('4.3.0', 'Sybase'),
    'mssql'     => array('4.3.0', 'MS SQL Server 2000 / 2005'),
    'mysqli'    => array('5.0.0', 'MySQL 4.1 / 5.x (PHP5)'),
    'sqlite'    => array('5.0.0', 'SQLite'),
    'ibm_db2'   => array('4.3.0', 'IBM DB2 Universal Database 8.2 / 9.1'),
    'oracle'    => array('5.0.0', 'Oracle 8i and later (experimental)'),
    'ibase'     => array('4.3.0', 'Interbase/Firebird (experimental)'),
    'maxdb'     => array('4.3.0', 'MaxDB 7.5 and later (experimental)')
    );

/* allowed 'action' varibales for GET - DO NOT CHANGE! */
$allowedVariables = array(
        'add'               => 1,
        'artikel'           => 1,
        'ask'               => 1,
        'contact'           => 1,
        'help'              => 1,
        'mailsend2friend'   => 1,
        'news'              => 1,
        'open'              => 1,
        'save'              => 1,
        'savecomment'       => 1,
        'savequestion'      => 1,
        'savevoting'        => 1,
        'search'            => 1,
        'send2friend'       => 1,
        'sendmail'          => 1,
        'show'              => 1,
        'sitemap'           => 1,
        'translate'         => 1,
        'writecomment'      => 1
        );

/* ISO 639 language code list - DO NOT CHANGE! */
$languageCodes = array (
        'AF' => 'Afghanistan',
        'AA' => 'Afar',
        'AB' => 'Abkhazian',
        'AF' => 'Afrikaans',
        'AM' => 'Amharic',
        'AR' => 'Arabic',
        'AS' => 'Assamese',
        'AY' => 'Aymara',
        'AZ' => 'Azerbaijani',
        'BA' => 'Bashkir',
        'BE' => 'Byelorussian',
        'BG' => 'Bulgarian',
        'BH' => 'Bihari',
        'BI' => 'Bislama',
        'BN' => 'Bengali',
        'BO' => 'Tibetan',
        'BR' => 'Breton',
        'CA' => 'Catalan',
        'CO' => 'Corsican',
        'CS' => 'Czech',
        'CY' => 'Welsh',
        'DA' => 'Danish',
        'DE' => 'German',
        'DZ' => 'Bhutani',
        'EL' => 'Greek',
        'EN' => 'English',
        'EO' => 'Esperanto',
        'ES' => 'Spanish',
        'ET' => 'Estonian',
        'EU' => 'Basque',
        'FA' => 'Persian',
        'FI' => 'Finnish',
        'FJ' => 'Fiji',
        'FO' => 'Faeroese',
        'FR' => 'French',
        'FY' => 'Frisian',
        'GA' => 'Irish',
        'GD' => 'Gaelic',
        'GL' => 'Galician',
        'GN' => 'Guarani',
        'GU' => 'Gujarati',
        'HA' => 'Hausa',
        'HE' => 'Hebrew',
        'HI' => 'Hindi',
        'HR' => 'Croatian',
        'HU' => 'Hungarian',
        'HY' => 'Armenian',
        'IA' => 'Interlingua',
        'IE' => 'Interlingue',
        'IK' => 'Inupiak',
        'ID' => 'Indonesian',
        'IS' => 'Icelandic',
        'IT' => 'Italian',
        'IW' => 'Hebrew',
        'JA' => 'Japanese',
        'JI' => 'Yiddish',
        'JW' => 'Javanese',
        'KA' => 'Georgian',
        'KK' => 'Kazakh',
        'KL' => 'Greenlandic',
        'KM' => 'Cambodian',
        'KN' => 'Kannada',
        'KO' => 'Korean',
        'KS' => 'Kashmiri',
        'KU' => 'Kurdish',
        'KY' => 'Kirghiz',
        'LA' => 'Latin',
        'LN' => 'Lingala',
        'LO' => 'Laothian',
        'LT' => 'Lithuanian',
        'LV' => 'Latvian',
        'MG' => 'Malagasy',
        'MI' => 'Maori',
        'MK' => 'Macedonian',
        'ML' => 'Malayalam',
        'MN' => 'Mongolian',
        'MO' => 'Moldavian',
        'MR' => 'Marathi',
        'MS' => 'Malay',
        'MT' => 'Maltese',
        'MY' => 'Burmese',
        'NA' => 'Nauru',
        'NE' => 'Nepali',
        'NL' => 'Dutch',
        'NB' => 'Norwegian Bokmål',
        'NN' => 'Norwegian Nynorsk',
        'OC' => 'Occitan',
        'OM' => 'Oromo',
        'OR' => 'Oriya',
        'PA' => 'Punjabi',
        'PL' => 'Polish',
        'PS' => 'Pashto',
        'PT' => 'Portuguese',
        'PT-BR' => 'Brazilian Portuguese',
        'QU' => 'Quechua',
        'RN' => 'Kirundi',
        'RO' => 'Romanian',
        'RU' => 'Russian',
        'RW' => 'Kinyarwanda',
        'SA' => 'Sanskrit',
        'SD' => 'Sindhi',
        'SG' => 'Sangro',
        'SH' => 'Serbo-Croatian',
        'SI' => 'Singhalese',
        'SK' => 'Slovak',
        'SL' => 'Slovenian',
        'SM' => 'Samoan',
        'SN' => 'Shona',
        'SO' => 'Somali',
        'SQ' => 'Albanian',
        'SR' => 'Serbian',
        'SS' => 'Siswati',
        'ST' => 'Sesotho',
        'SU' => 'Sudanese',
        'SV' => 'Swedish',
        'SW' => 'Swahili',
        'TA' => 'Tamil',
        'TE' => 'Tegulu',
        'TG' => 'Tajik',
        'TH' => 'Thai',
        'TI' => 'Tigrinya',
        'TK' => 'Turkmen',
        'TL' => 'Tagalog',
        'TN' => 'Setswana',
        'TO' => 'Tonga',
        'TR' => 'Turkish',
        'TS' => 'Tsonga',
        'TT' => 'Tatar',
        'TW' => 'Chinese (Traditional)',
        'UK' => 'Ukrainian',
        'UR' => 'Urdu',
        'UZ' => 'Uzbek',
        'VI' => 'Vietnamese',
        'VO' => 'Volapuk',
        'WO' => 'Wolof',
        'XH' => 'Xhosa',
        'YO' => 'Yoruba',
        'ZH' => 'Chinese (Simplified)',
        'ZU' => 'Zulu'
        );
?>
