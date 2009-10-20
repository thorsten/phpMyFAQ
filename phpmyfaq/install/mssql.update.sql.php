<?php
/**
 * $Id: mssql.update.sql.php,v 1.36 2007-03-29 19:31:53 thorstenr Exp $
 *
 * CREATE TABLE instruction for MS SQL Server database
 *
 * @author       Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author       Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since        2006-08-12
 * @copyright    (c) 2006-2007 phpMyFAQ Team
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

//
// TABLES
//

//faqcategory_group
$query[] = "CREATE TABLE ".SQLPREFIX."faqcategory_group (
category_id integer NOT NULL,
group_id integer NOT NULL,
PRIMARY KEY (category_id, group_id))";

//faqcategory_user
$query[] = "CREATE TABLE ".SQLPREFIX."faqcategory_user (
category_id integer NOT NULL,
user_id integer NOT NULL,
PRIMARY KEY (category_id, user_id))";

//faqconfig
$query[] = "CREATE TABLE ".SQLPREFIX."faqconfig (
config_name varchar(255) NOT NULL default '',
config_value varchar(255) NOT NULL default '',
PRIMARY KEY (config_name))";

//faqdata_group
$query[] = "CREATE TABLE ".SQLPREFIX."faqdata_group (
record_id integer NOT NULL,
group_id integer NOT NULL,
PRIMARY KEY (record_id, group_id))";

//faqdata_tags
$query[] = "CREATE TABLE ".SQLPREFIX."faqdata_tags (
record_id INTEGER NOT NULL,
tagging_id INTEGER NOT NULL,
PRIMARY KEY (record_id, tagging_id)
)";

//faqdata_user
$query[] = "CREATE TABLE ".SQLPREFIX."faqdata_user (
record_id integer NOT NULL,
user_id integer NOT NULL,
PRIMARY KEY (record_id, user_id))";

//faqglossary
$query[] = "CREATE TABLE ".SQLPREFIX."faqglossary (
id integer NOT NULL ,
lang varchar(2) NOT NULL ,
item varchar(255) NOT NULL ,
definition text NOT NULL,
PRIMARY KEY (id, lang))";

//faqgroup
$query[] = "CREATE TABLE ".SQLPREFIX."faqgroup (
group_id integer NOT NULL,
name varchar(25) NULL,
description text NULL,
auto_join integer NULL,
PRIMARY KEY(group_id)
)
CREATE UNIQUE INDEX ".SQLPREFIX."idxGroupName ON ".SQLPREFIX."faqgroup (name)";

//faqgroup_right
$query[] = "CREATE TABLE ".SQLPREFIX."faqgroup_right (
group_id integer NOT NULL,
right_id integer NOT NULL,
PRIMARY KEY(group_id, right_id)
)";

//faqlinkverifyrules
$query[] = "CREATE TABLE ".SQLPREFIX."faqlinkverifyrules (
id integer NOT NULL default '0',
type varchar(6) NOT NULL default '',
url varchar(255) NOT NULL default '',
reason varchar(255) NOT NULL default '',
enabled char(1) NOT NULL default 'y',
locked char(1) NOT NULL default 'n',
owner varchar(255) NOT NULL default '',
dtInsertDate varchar(15) NOT NULL default '',
dtUpdateDate varchar(15) NOT NULL default '',
PRIMARY KEY (id)
)";

//faqright
$query[] = "CREATE TABLE ".SQLPREFIX."faqright (
right_id integer NOT NULL,
name varchar(50) NULL,
description text NULL,
for_users integer NULL DEFAULT 1,
for_groups integer NULL DEFAULT 1,
PRIMARY KEY (right_id)
)";

//faqtags
$query[] = "CREATE TABLE ".SQLPREFIX."faqtags (
tagging_id INTEGER NOT NULL,
tagging_name VARCHAR(255) NOT NULL ,
PRIMARY KEY (tagging_id, tagging_name)
)";

//faquser
$query[] = "CREATE TABLE ".SQLPREFIX."faquser (
user_id integer NOT NULL,
login varchar(25) NOT NULL,
session_id varchar(150) NULL,
session_timestamp integer NULL,
ip varchar(15) NULL,
account_status varchar(50) NULL,
last_login varchar(14) NULL,
auth_source varchar(100) NULL,
member_since varchar(14) NULL,
PRIMARY KEY (user_id)
)";

//faquserdata
$query[] = "CREATE TABLE ".SQLPREFIX."faquserdata (
user_id integer NOT NULL,
last_modified varchar(14) NULL,
display_name varchar(50) NULL,
email varchar(100) NULL
)";

//faquserlogin
$query[] = "CREATE TABLE ".SQLPREFIX."faquserlogin (
login varchar(25) NOT NULL,
pass varchar(150) NULL,
PRIMARY KEY (login)
)";

//faquser_group
$query[] = "CREATE TABLE ".SQLPREFIX."faquser_group (
user_id integer NOT NULL,
group_id integer NOT NULL,
PRIMARY KEY (user_id, group_id)
)";

//faquser_right
$query[] = "CREATE TABLE ".SQLPREFIX."faquser_right (
user_id integer NOT NULL,
right_id integer NOT NULL,
PRIMARY KEY (user_id, right_id)
)";


//
// DATA
//

$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.administrationMail', 'webmaster@example.org')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.maxAttachmentSize', '100000')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.bannedIPs', 'false')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.languageDetection', 'true')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.disableAttachments', 'true')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.enableAdminLog', 'true')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('records.enableVisibilityQuestions', 'false')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.ipCheck', 'false')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.language', 'language_en.php')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.ldapSupport', 'false')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.metaDescription', 'phpMyFAQ should be the answer for all questions in life')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.metaKeywords', '')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.metaPublisher', 'John Doe')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.enableRewriteRules', 'false')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.contactInformations', '')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.numberOfShownNewsEntries', '3')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('numRecordsPage', '10')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.permLevel', 'basic')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.referenceURL', '')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.send2friendText', '')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('spam.checkBannedWords', 'true')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('spam.enableCaptchaCode', 'true')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('spam.enableSafeEmail', 'true')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.titleFAQ', 'phpMyFAQ Codename \"Prometheus\"')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.enableUserTracking', 'true')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.urlValidateInterval', '86400')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.currentVersion', '".NEWVERSION."')";
$query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('records.numberOfRelatedArticles', '5')";

$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (1, 'adduser', 'Right to add user accounts', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (2, 'edituser', 'Right to edit user accounts', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (3, 'deluser', 'Right to delete user accounts', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (4, 'addbt', 'Right to add faq entries', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (5, 'editbt', 'Right to edit faq entries', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (6, 'delbt', 'Right to delete faq entries', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (7, 'viewlog', 'Right to view logfiles', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (8, 'adminlog', 'Right to view admin log', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (9, 'delcomment', 'Right to delete comments', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (10, 'addnews', 'Right to add news', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (11, 'editnews', 'Right to edit news', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (12, 'delnews', 'Right to delete news', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (13, 'addcateg', 'Right to add categories', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (14, 'editcateg', 'Right to edit categories', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (15, 'delcateg', 'Right to delete categories', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (16, 'passwd', 'Right to change passwords', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (17, 'editconfig', 'Right to edit configuration', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (18, 'addatt', 'Right to add attachments', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (19, 'delatt', 'Right to delete attachments', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (20, 'backup', 'Right to save backups', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (21, 'restore', 'Right to load backups', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (22, 'delquestion', 'Right to delete questions', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (23, 'addglossary', 'Right to add glossary entries', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (24, 'editglossary', 'Right to edit glossary entries', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (25, 'delglossary', 'Right to delete glossary entries', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (26, 'changebtrevs', 'Edit revisions', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (27, 'addgroup', 'Right to add group accounts', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (28, 'editgroup', 'Right to edit group accounts', 1, 1)";
$query[] = "INSERT INTO ".SQLPREFIX."faqright VALUES (29, 'delgroup', 'Right to delete group accounts', 1, 1)";
