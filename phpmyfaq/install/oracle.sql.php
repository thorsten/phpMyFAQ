<?php
/**
 * $Id: oracle.sql.php,v 1.5 2008-05-24 16:35:43 thorstenr Exp $
 *
 * CREATE TABLE instruction for Oracle databases
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2007-02-24
 * @copyright   (c) 2007 phpMyFAQ Team
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

$uninst[] = "DROP TABLE ".$sqltblpre."faqadminlog";
$uninst[] = "DROP TABLE ".$sqltblpre."faqcaptcha";
$uninst[] = "DROP TABLE ".$sqltblpre."faqcategories";
$uninst[] = "DROP TABLE ".$sqltblpre."faqcategoryrelations";
$uninst[] = "DROP TABLE ".$sqltblpre."faqcategory_group";
$uninst[] = "DROP TABLE ".$sqltblpre."faqcategory_user";
$uninst[] = "DROP TABLE ".$sqltblpre."faqchanges";
$uninst[] = "DROP TABLE ".$sqltblpre."faqcomments";
$uninst[] = "DROP TABLE ".$sqltblpre."faqconfig";
$uninst[] = "DROP TABLE ".$sqltblpre."faqdata";
$uninst[] = "DROP TABLE ".$sqltblpre."faqdata_revisions";
$uninst[] = "DROP TABLE ".$sqltblpre."faqdata_group";
$uninst[] = "DROP TABLE ".$sqltblpre."faqdata_tags";
$uninst[] = "DROP TABLE ".$sqltblpre."faqdata_user";
$uninst[] = "DROP TABLE ".$sqltblpre."faqglossary";
$uninst[] = "DROP TABLE ".$sqltblpre."faqgroup";
$uninst[] = "DROP TABLE ".$sqltblpre."faqgroup_right";
$uninst[] = "DROP TABLE ".$sqltblpre."faqlinkverifyrules";
$uninst[] = "DROP TABLE ".$sqltblpre."faqnews";
$uninst[] = "DROP TABLE ".$sqltblpre."faqquestions";
$uninst[] = "DROP TABLE ".$sqltblpre."faqright";
$uninst[] = "DROP TABLE ".$sqltblpre."faqsearches";
$uninst[] = "DROP TABLE ".$sqltblpre."faqsessions";
$uninst[] = "DROP TABLE ".$sqltblpre."faqstopwords";
$uninst[] = "DROP TABLE ".$sqltblpre."faqtags";
$uninst[] = "DROP TABLE ".$sqltblpre."faquser";
$uninst[] = "DROP TABLE ".$sqltblpre."faquserdata";
$uninst[] = "DROP TABLE ".$sqltblpre."faquserlogin";
$uninst[] = "DROP TABLE ".$sqltblpre."faquser_group";
$uninst[] = "DROP TABLE ".$sqltblpre."faquser_right";
$uninst[] = "DROP TABLE ".$sqltblpre."faqvisits";
$uninst[] = "DROP TABLE ".$sqltblpre."faqvoting";

//faqadminlog
$query[] = "CREATE TABLE ".$sqltblpre."faqadminlog (
id INTEGER NOT NULL,
time INTEGER NOT NULL,
usr VARCHAR(255) NOT NULL,
text VARCHAR(512) NOT NULL,
ip VARCHAR(64) NOT NULL,
PRIMARY KEY (id))";

//faqcaptcha
$query[] = "CREATE TABLE ".$sqltblpre."faqcaptcha (
id VARCHAR(6) NOT NULL,
useragent VARCHAR(255) NOT NULL,
language VARCHAR(2) NOT NULL,
ip VARCHAR(64) NOT NULL,
captcha_time INTEGER NOT NULL,
PRIMARY KEY (id))";

//faqcategories
$query[] = "CREATE TABLE ".$sqltblpre."faqcategories (
id INTEGER NOT NULL,
lang VARCHAR(5) NOT NULL,
parent_id INTEGER NOT NULL,
name VARCHAR(255) NOT NULL,
description VARCHAR(255) DEFAULT NULL,
user_id INTEGER NOT NULL,
PRIMARY KEY (id, lang))";

//faqcategoryrelations
$query[] = "CREATE TABLE ".$sqltblpre."faqcategoryrelations (
category_id INTEGER NOT NULL,
category_lang VARCHAR(5) NOT NULL default '',
record_id INTEGER NOT NULL,
record_lang VARCHAR(5) NOT NULL default '',
PRIMARY KEY  (category_id, category_lang, record_id, record_lang)
)";
$query[] = "CREATE INDEX ".$sqltblpre."idx_records ON ".$sqltblpre."faqcategoryrelations
(record_id, record_lang)";

//faqcategory_group
$query[] = "CREATE TABLE ".$sqltblpre."faqcategory_group (
category_id INTEGER NOT NULL,
group_id INTEGER NOT NULL,
PRIMARY KEY (category_id, group_id))";

//faqcategory_user
$query[] = "CREATE TABLE ".$sqltblpre."faqcategory_user (
category_id INTEGER NOT NULL,
user_id INTEGER NOT NULL,
PRIMARY KEY (category_id, user_id))";

//faqchanges
$query[] = "CREATE TABLE ".$sqltblpre."faqchanges (
id INTEGER NOT NULL,
beitrag INTEGER NOT NULL,
lang VARCHAR(5) NOT NULL,
revision_id INTEGER NOT NULL DEFAULT 0,
usr VARCHAR(255) NOT NULL,
datum INTEGER NOT NULL,
what VARCHAR(512) DEFAULT NULL,
PRIMARY KEY (id, lang))";

//faqcomments
$query[] = "CREATE TABLE  ".$sqltblpre."faqcomments (
id_comment INTEGER NOT NULL,
id INTEGER NOT NULL,
type VARCHAR(10) NOT NULL,
usr VARCHAR(255) NOT NULL,
email VARCHAR(255) NOT NULL,
comment CLOB NOT NULL,
datum VARCHAR(64) NOT NULL,
helped VARCHAR(255) DEFAULT NULL,
PRIMARY KEY (id_comment))";

//faqconfig
$query[] = "CREATE TABLE ".$sqltblpre."faqconfig (
config_name VARCHAR(255) NOT NULL default '',
config_value VARCHAR(255) DEFAULT NULL,
PRIMARY KEY (config_name))";

//faqdata
$query[] = "CREATE TABLE ".$sqltblpre."faqdata (
id INTEGER NOT NULL,
lang VARCHAR(5) NOT NULL,
solution_id INTEGER NOT NULL,
revision_id INTEGER NOT NULL DEFAULT 0,
active CHAR(3) NOT NULL,
sticky INTEGER NOT NULL,
keywords VARCHAR(255) DEFAULT NULL,
thema VARCHAR(255) NOT NULL,
content CLOB DEFAULT NULL,
author VARCHAR(255) NOT NULL,
email VARCHAR(255) NOT NULL,
comment CHAR(1) default 'y',
datum VARCHAR(15) NOT NULL,
links_state VARCHAR(7) DEFAULT NULL,
links_check_date INTEGER DEFAULT 0,
date_start VARCHAR(14) NOT NULL DEFAULT '00000000000000',
date_end VARCHAR(14) NOT NULL DEFAULT '99991231235959',
PRIMARY KEY (id, lang))";

//faqdata_revisions
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_revisions (
id INTEGER NOT NULL,
lang VARCHAR(5) NOT NULL,
solution_id INTEGER NOT NULL,
revision_id INTEGER NOT NULL DEFAULT 0,
active CHAR(3) NOT NULL,
sticky INTEGER NOT NULL,
keywords VARCHAR(255) DEFAULT NULL,
thema VARCHAR(255) NOT NULL,
content CLOB DEFAULT NULL,
author VARCHAR(255) NOT NULL,
email VARCHAR(255) NOT NULL,
comment CHAR(1) default 'y',
datum VARCHAR(15) NOT NULL,
links_state VARCHAR(7) DEFAULT NULL,
links_check_date INTEGER DEFAULT 0,
date_start VARCHAR(14) NOT NULL DEFAULT '00000000000000',
date_end VARCHAR(14) NOT NULL DEFAULT '99991231235959',
PRIMARY KEY (id, lang, solution_id, revision_id))";

//faqdata_group
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_group (
record_id INTEGER NOT NULL,
group_id INTEGER NOT NULL,
PRIMARY KEY (record_id, group_id))";

//faqdata_tags
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_tags (
record_id INTEGER NOT NULL,
tagging_id INTEGER NOT NULL,
PRIMARY KEY (record_id, tagging_id)
)";

//faqdata_user
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_user (
record_id INTEGER NOT NULL,
user_id INTEGER NOT NULL,
PRIMARY KEY (record_id, user_id))";

//faqglossary
$query[] = "CREATE TABLE ".$sqltblpre."faqglossary (
id INTEGER NOT NULL ,
lang VARCHAR(2) NOT NULL ,
item VARCHAR(255) NOT NULL ,
definition CLOB NOT NULL,
PRIMARY KEY (id, lang))";

//faqgroup
$query[] = "CREATE TABLE ".$sqltblpre."faqgroup (
group_id INTEGER NOT NULL,
name VARCHAR(25) NULL,
description CLOB NULL,
auto_join INTEGER NULL,
PRIMARY KEY(group_id),
UNIQUE INDEX name(name)
)";

//faqgroup_right
$query[] = "CREATE TABLE ".$sqltblpre."faqgroup_right (
group_id INT(11) NOT NULL,
right_id INT(11) NOT NULL,
PRIMARY KEY(group_id, right_id)
)";

//faqlinkverifyrules
$query[] = "CREATE TABLE ".$sqltblpre."faqlinkverifyrules (
id int(11) NOT NULL default '0',
type VARCHAR(6) NOT NULL default '',
url VARCHAR(255) NOT NULL default '',
reason VARCHAR(255) NOT NULL default '',
enabled enum('y','n') NOT NULL default 'y',
locked enum('y','n') NOT NULL default 'n',
owner VARCHAR(255) NOT NULL default '',
dtInsertDate VARCHAR(15) NOT NULL default '',
dtUpdateDate VARCHAR(15) NOT NULL default '',
PRIMARY KEY (id)
)";

//faqnews
$query[] = "CREATE TABLE ".$sqltblpre."faqnews (
id INTEGER NOT NULL,
lang VARCHAR(5) NOT NULL,
header VARCHAR(255) NOT NULL,
artikel CLOB NOT NULL,
datum VARCHAR(14) NOT NULL,
author_name  VARCHAR(255) NULL,
author_email VARCHAR(255) NULL,
active CHAR(1) default 'y',
comment CHAR(1) default 'n',
date_start VARCHAR(14) NOT NULL DEFAULT '00000000000000',
date_end VARCHAR(14) NOT NULL DEFAULT '99991231235959',
link VARCHAR(255) DEFAULT NULL,
linktitel VARCHAR(255) DEFAULT NULL,
target VARCHAR(255) NOT NULL,
PRIMARY KEY (id))";

//faqquestions
$query[] = "CREATE TABLE ".$sqltblpre."faqquestions (
id INTEGER NOT NULL,
ask_username VARCHAR(100) NOT NULL,
ask_usermail VARCHAR(100) NOT NULL,
ask_rubrik INTEGER NOT NULL,
ask_content CLOB NOT NULL,
ask_date VARCHAR(20) NOT NULL,
is_visible CHAR(1) default 'Y',
PRIMARY KEY (id))";

//faqright
$query[] = "CREATE TABLE ".$sqltblpre."faqright (
right_id INTEGER UNSIGNED NOT NULL,
name VARCHAR(50) NULL,
description CLOB NULL,
for_users INTEGER NULL DEFAULT 1,
for_groups INTEGER NULL DEFAULT 1,
PRIMARY KEY (right_id)
)";

//faqsearches
$query[] = "CREATE TABLE ".$sqltblpre."faqsearches (
id INTEGER NOT NULL ,
lang VARCHAR(5) NOT NULL ,
searchterm VARCHAR(255) NOT NULL ,
searchdate TIMESTAMP,
PRIMARY KEY (id, lang)
)";

//faqsessions
$query[] = "CREATE TABLE ".$sqltblpre."faqsessions (
sid INTEGER NOT NULL,
user_id INTEGER NOT NULL,
ip VARCHAR(64) NOT NULL,
time INTEGER NOT NULL,
PRIMARY KEY (sid)
)";

//faqstopwords
$query[] = "CREATE TABLE ".$sqltblpre."faqstopwords (
id INTEGER NOT NULL,
lang VARCHAR(5) NOT NULL,
stopword VARCHAR(64) NOT NULL,
PRIMARY KEY (id, lang)";

//faqtags
$query[] = "CREATE TABLE ".$sqltblpre."faqtags (
tagging_id INTEGER NOT NULL,
tagging_name VARCHAR(255) NOT NULL ,
PRIMARY KEY (tagging_id, tagging_name)
)";

//faquser
$query[] = "CREATE TABLE ".$sqltblpre."faquser (
user_id INTEGER NOT NULL,
login VARCHAR(25) NOT NULL,
session_id VARCHAR(150) NULL,
session_timestamp INTEGER NULL,
ip VARCHAR(15) NULL,
account_status VARCHAR(50) NULL,
last_login VARCHAR(14) NULL,
auth_source VARCHAR(100) NULL,
member_since VARCHAR(14) NULL,
PRIMARY KEY (user_id)
)";

//faquserdata
$query[] = "CREATE TABLE ".$sqltblpre."faquserdata (
user_id INTEGER NOT NULL,
last_modified VARCHAR(14)(14) NULL,
display_name VARCHAR(50) NULL,
email VARCHAR(100) NULL
)";

//faquserlogin
$query[] = "CREATE TABLE ".$sqltblpre."faquserlogin (
login VARCHAR(25) NOT NULL,
pass VARCHAR(25) NULL,
PRIMARY KEY (login)
)";

//faquser_group
$query[] = "CREATE TABLE ".$sqltblpre."faquser_group (
user_id INTEGER NOT NULL,
group_id INTEGER NOT NULL,
PRIMARY KEY (user_id, group_id)
)";

//faquser_right
$query[] = "CREATE TABLE ".$sqltblpre."faquser_right (
user_id INTEGER NOT NULL,
right_id INTEGER NOT NULL,
PRIMARY KEY (user_id, right_id)
)";

//faqvisits
$query[] = "CREATE TABLE ".$sqltblpre."faqvisits (
id INTEGER NOT NULL,
lang VARCHAR(5) NOT NULL,
visits INTEGER NOT NULL,
last_visit INTEGER NOT NULL,
PRIMARY KEY (id, lang))";

//faqvoting
$query[] = "CREATE TABLE ".$sqltblpre."faqvoting (
id INTEGER NOT NULL,
artikel INTEGER NOT NULL,
vote INTEGER NOT NULL,
usr INTEGER NOT NULL,
datum VARCHAR(20) DEFAULT '',
ip VARCHAR(15) DEFAULT '',
PRIMARY KEY (id))";
