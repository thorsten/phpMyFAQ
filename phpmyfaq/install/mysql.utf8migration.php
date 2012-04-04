<?php
/**
 * UTF-8 Migration SQL queries for MySQL (ext/mysql)
 *
 * Example solution:
 * ALTER TABLE `name` MODIFY COLUMN `title` VARCHAR(255) CHARACTER SET utf8;
 *
 * PHP Version 5.3.0
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Installation
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-05-14
 */

// Table faqadminlog
$query[] = "ALTER TABLE ".SQLPREFIX."faqadminlog MODIFY COLUMN text VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqadminlog MODIFY COLUMN ip VARCHAR(64) CHARACTER SET utf8";

// Table faqcaptcha
$query[] = "ALTER TABLE ".SQLPREFIX."faqcaptcha MODIFY COLUMN id VARCHAR(6) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqcaptcha MODIFY COLUMN useragent VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqcaptcha MODIFY COLUMN language VARCHAR(5) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqcaptcha MODIFY COLUMN ip VARCHAR(64) CHARACTER SET utf8";

// Table faqcategories
$query[] = "ALTER TABLE ".SQLPREFIX."faqcategories MODIFY COLUMN lang VARCHAR(5) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqcategories MODIFY COLUMN name VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqcategories MODIFY COLUMN description VARCHAR(255) CHARACTER SET utf8";

// Table faqcategoryrelations
$query[] = "ALTER TABLE ".SQLPREFIX."faqcategoryrelations MODIFY COLUMN category_lang VARCHAR(5) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqcategoryrelations MODIFY COLUMN record_lang VARCHAR(5) CHARACTER SET utf8";

// Table faqchanges
$query[] = "ALTER TABLE ".SQLPREFIX."faqchanges MODIFY COLUMN lang VARCHAR(5) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqchanges MODIFY COLUMN what TEXT CHARACTER SET utf8";

// Table faqcomments
$query[] = "ALTER TABLE ".SQLPREFIX."faqcomments MODIFY COLUMN type VARCHAR(10) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqcomments MODIFY COLUMN usr VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqcomments MODIFY COLUMN email VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqcomments MODIFY COLUMN comment TEXT CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqcomments MODIFY COLUMN helped TEXT CHARACTER SET utf8";

// Table faqconfig
$query[] = "ALTER TABLE ".SQLPREFIX."faqconfig MODIFY COLUMN config_name VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqconfig MODIFY COLUMN config_value VARCHAR(255) CHARACTER SET utf8";

// Table faqdata
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata DROP INDEX keywords";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata MODIFY COLUMN lang VARCHAR(5) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata MODIFY COLUMN active VARCHAR(3) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata MODIFY COLUMN keywords TEXT CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata MODIFY COLUMN thema TEXT CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata MODIFY COLUMN content LONGTEXT CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata MODIFY COLUMN author VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata MODIFY COLUMN email VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata MODIFY COLUMN datum VARCHAR(15) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata MODIFY COLUMN links_state VARCHAR(7) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata MODIFY COLUMN date_start VARCHAR(14) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata MODIFY COLUMN date_end VARCHAR(14) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata ADD FULLTEXT (keywords, thema, content)";

// Table faqdata_revisions
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions MODIFY COLUMN lang VARCHAR(5) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions MODIFY COLUMN active VARCHAR(3) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions MODIFY COLUMN keywords TEXT CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions MODIFY COLUMN thema TEXT CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions MODIFY COLUMN content LONGTEXT CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions MODIFY COLUMN author VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions MODIFY COLUMN email VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions MODIFY COLUMN datum VARCHAR(15) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions MODIFY COLUMN links_state VARCHAR(7) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions MODIFY COLUMN date_start VARCHAR(14) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions MODIFY COLUMN date_end VARCHAR(14) CHARACTER SET utf8";

// Table faqglossary
$query[] = "ALTER TABLE ".SQLPREFIX."faqglossary MODIFY COLUMN lang VARCHAR(5) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqglossary MODIFY COLUMN item VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqglossary MODIFY COLUMN definition TEXT CHARACTER SET utf8";

// Table faqgroup
$query[] = "ALTER TABLE ".SQLPREFIX."faqgroup MODIFY COLUMN name VARCHAR(25) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqgroup MODIFY COLUMN description TEXT  CHARACTER SET utf8";

// Table faqlinkverifyrules
$query[] = "ALTER TABLE ".SQLPREFIX."faqlinkverifyrules MODIFY COLUMN type VARCHAR(6) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqlinkverifyrules MODIFY COLUMN url VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqlinkverifyrules MODIFY COLUMN reason VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqlinkverifyrules MODIFY COLUMN owner VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqlinkverifyrules MODIFY COLUMN dtInsertDate VARCHAR(15) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqlinkverifyrules MODIFY COLUMN dtUpdateDate VARCHAR(15) CHARACTER SET utf8";

// Table faqnews
$query[] = "ALTER TABLE ".SQLPREFIX."faqnews MODIFY COLUMN lang VARCHAR(5) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqnews MODIFY COLUMN header VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqnews MODIFY COLUMN artikel TEXT CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqnews MODIFY COLUMN datum VARCHAR(14) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqnews MODIFY COLUMN author_name VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqnews MODIFY COLUMN author_email VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqnews MODIFY COLUMN active VARCHAR(1) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqnews MODIFY COLUMN comment VARCHAR(1) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqnews MODIFY COLUMN date_start VARCHAR(14) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqnews MODIFY COLUMN date_end VARCHAR(14) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqnews MODIFY COLUMN link VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqnews MODIFY COLUMN linktitel VARCHAR(255) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqnews MODIFY COLUMN target VARCHAR(255) CHARACTER SET utf8";

// Table faqquestions
$query[] = "ALTER TABLE ".SQLPREFIX."faqquestions MODIFY COLUMN ask_username VARCHAR(100) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqquestions MODIFY COLUMN ask_usermail VARCHAR(100) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqquestions MODIFY COLUMN ask_rubrik VARCHAR(100) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqquestions MODIFY COLUMN ask_content TEXT CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqquestions MODIFY COLUMN ask_date VARCHAR(20) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqquestions MODIFY COLUMN is_visible VARCHAR(1) CHARACTER SET utf8";

// Table faqright
$query[] = "ALTER TABLE ".SQLPREFIX."faqright MODIFY COLUMN name VARCHAR(50) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqright MODIFY COLUMN description TEXT  CHARACTER SET utf8";

// Table faqsearches
$query[] = "ALTER TABLE ".SQLPREFIX."faqsearches MODIFY COLUMN lang VARCHAR(5) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqsearches MODIFY COLUMN searchterm VARCHAR(255) CHARACTER SET utf8";

// Table faqsessions
$query[] = "ALTER TABLE ".SQLPREFIX."faqsessions MODIFY COLUMN ip VARCHAR(64) CHARACTER SET utf8";

// Table faqstopwords
$query[] = "ALTER TABLE ".SQLPREFIX."faqstopwords MODIFY COLUMN lang VARCHAR(5) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqstopwords MODIFY COLUMN stopword VARCHAR(564) CHARACTER SET utf8";

// Table faqtags
$query[] = "ALTER TABLE ".SQLPREFIX."faqtags MODIFY COLUMN tagging_name VARCHAR(255) CHARACTER SET utf8";

// Table faquser
$query[] = "ALTER TABLE ".SQLPREFIX."faquser MODIFY COLUMN login VARCHAR(25) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faquser MODIFY COLUMN session_id VARCHAR(150) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faquser MODIFY COLUMN ip VARCHAR(64) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faquser MODIFY COLUMN account_status VARCHAR(50) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faquser MODIFY COLUMN last_login VARCHAR(14) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faquser MODIFY COLUMN auth_source VARCHAR(100) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faquser MODIFY COLUMN member_since VARCHAR(14) CHARACTER SET utf8";

// Table faquserdata
$query[] = "ALTER TABLE ".SQLPREFIX."faquserdata MODIFY COLUMN display_name VARCHAR(50) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faquserdata MODIFY COLUMN email VARCHAR(100) CHARACTER SET utf8";

// Table faquserlogin
$query[] = "ALTER TABLE ".SQLPREFIX."faquserlogin MODIFY COLUMN login VARCHAR(25) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faquserlogin MODIFY COLUMN pass VARCHAR(150) CHARACTER SET utf8";

// Table faqvisits
$query[] = "ALTER TABLE ".SQLPREFIX."faqvisits MODIFY COLUMN lang VARCHAR(5) CHARACTER SET utf8";

// Table faqvoting
$query[] = "ALTER TABLE ".SQLPREFIX."faqvoting MODIFY COLUMN datum VARCHAR(15) CHARACTER SET utf8";
$query[] = "ALTER TABLE ".SQLPREFIX."faqvoting MODIFY COLUMN ip VARCHAR(64) CHARACTER SET utf8";