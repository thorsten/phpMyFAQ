<?php
/**
* $Id: constants.php,v 1.7 2005-12-06 16:16:31 b33blebr0x Exp $
*
* Constants for phpMyFAQ
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-12-10
* @copyright    (c) 2003-2005 phpMyFAQ Team
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

/* Use this constant if you want to change your timezone */
$PMF_CONST["timezone"] = "0"; // default: "0", example: "-0400" for 4 hours before

/* Timeout for the admin section in minutes */
$PMF_CONST["timeout"] = "30";

/****************************************************************************
 *                  DO NOT CHANGE ANYTHING BELOW THIS LINE!                 *
 ****************************************************************************/

/**
* Supported databases for phpMyFAQ
*
* @var  array
*/
$supported_databases = array(
    'mysql'     => array('4.1.0', 'MySQL 3.23 / 4.0'),
    'pgsql'     => array('4.2.0', 'PostgreSQL 7.2 or later'),
    'sybase'    => array('4.1.0', 'Sybase'),
    'mssql'     => array('4.1.0', 'MS SQL Server 2000'),
    'mysqli'    => array('5.0.0', 'MySQL 4.1 / 5.0'),
    'sqlite'    => array('5.0.0', 'SQLite'),
    'ibm_db2'   => array('4.1.0', 'IBM DB2 Universal Database'),
    'maxdb'     => array('4.1.0', 'MaxDB (experimental)')
    );

/* This array sets the rights for an user - DO NOT CHANGE! */
$faqrights = array (
        1 => "adduser",
        2 => "edituser",
        3 => "deluser",
        4 => "addbt",
        5 => "editbt",
        6 => "delbt",
        7 => "viewlog",
        8 => "adminlog",
        9 => "delcomment",
        10 => "addnews",
        11 => "editnews",
        12 => "delnews",
        13 => "addcateg",
        14 => "editcateg",
        15 => "delcateg",
        16 => "passwd",
        17 => "editconfig",
        18 => "addatt",
        19 => "delatt",
        20 => "backup",
        21 => "restore",
        22 => "delquestion",
        23 => 'addglossary',
        24 => 'editglossary',
        25 => 'delglossary'
        );

/* allowed "action" varibales for GET - DO NOT CHANGE! */
$allowedVariables = array(
        "add" => 1,
        "contact" => 1,
        "mailsend2friend" => 1,
        "save" => 1,
        "savevoting" => 1,
        "sendmail" => 1,
        "writecomment" => 1,
        "artikel" => 1,
        "help" => 1,
        "savecomment" => 1,
        "search" => 1,
        "show" => 1,
        "xml" => 1,
        "ask" => 1,
        "open" => 1,
        "savequestion" => 1,
        "send2friend" => 1,
        'sitemap.php' => 1
        );

/* ISO 639 language code list - DO NOT CHANGE! */
$languageCodes = array (
        "AF" => "Afghanistan",
        "AA" => "Afar",
        "AB" => "Abkhazian",
        "AF" => "Afrikaans",
        "AM" => "Amharic",
        "AR" => "Arabic",
        "AS" => "Assamese",
        "AY" => "Aymara",
        "AZ" => "Azerbaijani",
        "BA" => "Bashkir",
        "BE" => "Byelorussian",
        "BG" => "Bulgarian",
        "BH" => "Bihari",
        "BI" => "Bislama",
        "BN" => "Bengali",
        "BO" => "Tibetan",
        "BR" => "Breton",
        "CA" => "Catalan",
        "CO" => "Corsican",
        "CS" => "Czech",
        "CY" => "Welsh",
        "DA" => "Danish",
        "DE" => "German",
        "DZ" => "Bhutani",
        "EL" => "Greek",
        "EN" => "English",
        "EO" => "Esperanto",
        "ES" => "Spanish",
        "ET" => "Estonian",
        "EU" => "Basque",
        "FA" => "Persian",
        "FI" => "Finnish",
        "FJ" => "Fiji",
        "FO" => "Faeroese",
        "FR" => "French",
        "FY" => "Frisian",
        "GA" => "Irish",
        "GD" => "Gaelic",
        "GL" => "Galician",
        "GN" => "Guarani",
        "GU" => "Gujarati",
        "HA" => "Hausa",
        "HE" => "Hebrew",
        "HI" => "Hindi",
        "HR" => "Croatian",
        "HU" => "Hungarian",
        "HY" => "Armenian",
        "IA" => "Interlingua",
        "IE" => "Interlingue",
        "IK" => "Inupiak",
        "ID" => "Indonesian",
        "IS" => "Icelandic",
        "IT" => "Italian",
        "IW" => "Hebrew",
        "JA" => "Japanese",
        "JI" => "Yiddish",
        "JW" => "Javanese",
        "KA" => "Georgian",
        "KK" => "Kazakh",
        "KL" => "Greenlandic",
        "KM" => "Cambodian",
        "KN" => "Kannada",
        "KO" => "Korean",
        "KS" => "Kashmiri",
        "KU" => "Kurdish",
        "KY" => "Kirghiz",
        "LA" => "Latin",
        "LN" => "Lingala",
        "LO" => "Laothian",
        "LT" => "Lithuanian",
        "LV" => "Latvian",
        "MG" => "Malagasy",
        "MI" => "Maori",
        "MK" => "Macedonian",
        "ML" => "Malayalam",
        "MN" => "Mongolian",
        "MO" => "Moldavian",
        "MR" => "Marathi",
        "MS" => "Malay",
        "MT" => "Maltese",
        "MY" => "Burmese",
        "NA" => "Nauru",
        "NE" => "Nepali",
        "NL" => "Dutch",
        "NB" => "Norwegian Bokmål",
        "NN" => "Norwegian Nynorsk",
        "OC" => "Occitan",
        "OM" => "Oromo",
        "OR" => "Oriya",
        "PA" => "Punjabi",
        "PL" => "Polish",
        "PS" => "Pashto",
        "PT" => "Portuguese",
        "QU" => "Quechua",
        "RN" => "Kirundi",
        "RO" => "Romanian",
        "RU" => "Russian",
        "RW" => "Kinyarwanda",
        "SA" => "Sanskrit",
        "SD" => "Sindhi",
        "SG" => "Sangro",
        "SH" => "Serbo-Croatian",
        "SI" => "Singhalese",
        "SK" => "Slovak",
        "SL" => "Slovenian",
        "SM" => "Samoan",
        "SN" => "Shona",
        "SO" => "Somali",
        "SQ" => "Albanian",
        "SR" => "Serbian",
        "SS" => "Siswati",
        "ST" => "Sesotho",
        "SU" => "Sudanese",
        "SV" => "Swedish",
        "SW" => "Swahili",
        "TA" => "Tamil",
        "TE" => "Tegulu",
        "TG" => "Tajik",
        "TH" => "Thai",
        "TI" => "Tigrinya",
        "TK" => "Turkmen",
        "TL" => "Tagalog",
        "TN" => "Setswana",
        "TO" => "Tonga",
        "TR" => "Turkish",
        "TS" => "Tsonga",
        "TT" => "Tatar",
        "TW" => "Chinese (Traditional)",
        "UK" => "Ukrainian",
        "UR" => "Urdu",
        "UZ" => "Uzbek",
        "VI" => "Vietnamese",
        "VO" => "Volapuk",
        "WO" => "Wolof",
        "XH" => "Xhosa",
        "YO" => "Yoruba",
        "ZH" => "Chinese (Simplified)",
        "ZU" => "Zulu"
        );
?>
