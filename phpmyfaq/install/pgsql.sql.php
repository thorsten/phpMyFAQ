<?php
/**
 * CREATE TABLE instruction for PostgreSQL database
 *
 * PHP Version 5.2
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
 *
 * @category  phpMyFAQ
 * @package   Setup
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2004-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2004-09-18
 */

$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqadminlog CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqattachment CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqattachment_file CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqcaptcha CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqcategories CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqcategoryrelations CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqcategory_group CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqcategory_user CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqchanges CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqcomments CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqconfig CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqdata CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqdata_revisions CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqdata_group CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqdata_tags CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqdata_user CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqglossary CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqgroup CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqgroup_right CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqlinkverifyrules CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqnews CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqquestions CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqright CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqsearches CASCADE";
$uninst[] = "DROP SEQUENCE IF EXISTS ".$sqltblpre."faqsearch_id_seq";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqsessions CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqstopwords CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqtags CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faquser CASCADE";
$uninst[] = "DROP SEQUENCE IF EXISTS ".$sqltblpre."faquser_user_id_seq";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faquserdata CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faquserlogin CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faquser_group CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faquser_right CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqvisits CASCADE";
$uninst[] = "DROP TABLE IF EXISTS ".$sqltblpre."faqvoting CASCADE";

//faquser
$query[] = "CREATE SEQUENCE ".$sqltblpre."faquser_user_id_seq START WITH 2";

$query[] = "CREATE TABLE ".$sqltblpre."faquser (
user_id SERIAL NOT NULL,
login varchar(25) NOT NULL,
session_id varchar(150) NULL,
session_timestamp int4 NULL,
ip varchar(15) NULL,
account_status varchar(50) NULL,
last_login varchar(14) NULL,
auth_source varchar(100) NULL,
member_since varchar(14) NULL,
PRIMARY KEY (user_id)
)";

//faqgroup
$query[] = "CREATE TABLE ".$sqltblpre."faqgroup (
group_id SERIAL NOT NULL,
name VARCHAR(25) NULL,
description TEXT NULL,
auto_join int4 NULL,
PRIMARY KEY (group_id)
)";

//faqadminlog
$query[] = "CREATE TABLE ".$sqltblpre."faqadminlog (
id SERIAL NOT NULL,
time int4 NOT NULL,
usr int4 NOT NULL,
text text NOT NULL,
ip text NOT NULL,
PRIMARY KEY (id))";

//faqcaptcha
$query[] = "CREATE TABLE ".$sqltblpre."faqcaptcha (
id varchar(6) NOT NULL,
useragent varchar(255) NOT NULL,
language varchar(5) NOT NULL,
ip varchar(64) NOT NULL,
captcha_time int4 NOT NULL,
PRIMARY KEY (id))";

//faqcategories
$query[] = "CREATE TABLE ".$sqltblpre."faqcategories (
id SERIAL NOT NULL,
lang varchar(5) NOT NULL,
parent_id int4 NOT NULL,
name varchar(255) NOT NULL,
description varchar(255) DEFAULT NULL,
user_id int4 NOT NULL,
PRIMARY KEY (id, lang))";

//faqcategoryrelations
$query[] = "CREATE TABLE ".$sqltblpre."faqcategoryrelations (
category_id int4 NOT NULL,
category_lang VARCHAR(5) NOT NULL,
record_id int4 NOT NULL,
record_lang VARCHAR(5) NOT NULL,
PRIMARY KEY  (category_id, category_lang, record_id, record_lang)
)";
$query[] = "CREATE INDEX ".$sqltblpre."idx_records ON ".$sqltblpre."faqcategoryrelations
(record_id, record_lang)";

//faqcategory_group
$query[] = "CREATE TABLE ".$sqltblpre."faqcategory_group (
category_id int4 NOT NULL,
group_id int4 NOT NULL,
PRIMARY KEY (category_id, group_id))";

//faqcategory_user
$query[] = "CREATE TABLE ".$sqltblpre."faqcategory_user (
category_id int4 NOT NULL,
user_id int4 NOT NULL,
PRIMARY KEY (category_id, user_id))";

//faqchanges
$query[] = "CREATE TABLE ".$sqltblpre."faqchanges (
id SERIAL NOT NULL,
beitrag int4 NOT NULL,
lang varchar(5) NOT NULL,
revision_id int4 NOT NULL DEFAULT 0,
usr int4 NOT NULL,
datum int4 NOT NULL,
what text DEFAULT NULL,
PRIMARY KEY (id, lang))";

//faqcomments
$query[] = "CREATE TABLE ".$sqltblpre."faqcomments (
id_comment SERIAL NOT NULL,
id int4 NOT NULL,
type varchar(10) NOT NULL,
usr varchar(255) NOT NULL,
email varchar(255) NOT NULL,
comment text NOT NULL,
datum int4 NOT NULL,
helped text DEFAULT NULL,
PRIMARY KEY (id_comment))";

//faqconfig
$query[] = "CREATE TABLE ".$sqltblpre."faqconfig (
config_name varchar(255) NOT NULL default '',
config_value varchar(255) DEFAULT NULL,
PRIMARY KEY (config_name))";

//faqdata
$query[] = "CREATE TABLE ".$sqltblpre."faqdata (
id SERIAL NOT NULL,
lang varchar(5) NOT NULL,
solution_id int4 NOT NULL,
revision_id int4 NOT NULL DEFAULT 0,
active char(3) NOT NULL,
sticky INTEGER NOT NULL,
keywords text DEFAULT NULL,
thema text NOT NULL,
content text DEFAULT NULL,
author varchar(255) NOT NULL,
email varchar(255) NOT NULL,
comment char(1) NOT NULL default 'y',
datum varchar(15) NOT NULL,
links_state varchar(7) DEFAULT NULL,
links_check_date int4 DEFAULT 0 NOT NULL,
date_start varchar(14) NOT NULL DEFAULT '00000000000000',
date_end varchar(14) NOT NULL DEFAULT '99991231235959',
PRIMARY KEY (id, lang))";

//faqdata_revisions
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_revisions (
id SERIAL NOT NULL,
lang varchar(5) NOT NULL,
solution_id int4 NOT NULL,
revision_id int4 NOT NULL DEFAULT 0,
active char(3) NOT NULL,
sticky INTEGER NOT NULL,
keywords text DEFAULT NULL,
thema text NOT NULL,
content text DEFAULT NULL,
author varchar(255) NOT NULL,
email varchar(255) NOT NULL,
comment char(1) default 'y',
datum varchar(15) NOT NULL,
links_state varchar(7) DEFAULT NULL,
links_check_date int4 DEFAULT 0 NOT NULL,
date_start varchar(14) NOT NULL DEFAULT '00000000000000',
date_end varchar(14) NOT NULL DEFAULT '99991231235959',
PRIMARY KEY (id, lang, solution_id, revision_id))";

//faqdata_group
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_group (
record_id int4 NOT NULL,
group_id int4 NOT NULL,
PRIMARY KEY (record_id, group_id))";

//faqdata_tags
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_tags (
record_id INT4 NOT NULL,
tagging_id INT4 NOT NULL,
PRIMARY KEY (record_id, tagging_id)
)";

//faqdata_user
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_user (
record_id int4 NOT NULL,
user_id int4 NOT NULL,
PRIMARY KEY (record_id, user_id))";

//faqglossary
$query[] = "CREATE TABLE ".$sqltblpre."faqglossary (
id SERIAL NOT NULL,
lang VARCHAR(5) NOT NULL,
item VARCHAR(255) NOT NULL,
definition TEXT NOT NULL,
PRIMARY KEY (id, lang))";

//faqgroup_right
$query[] = "CREATE TABLE ".$sqltblpre."faqgroup_right (
group_id int4 NOT NULL,
right_id int4 NOT NULL,
PRIMARY KEY (group_id, right_id)
)";

//faqlinkverifyrules
$query[] = "CREATE TABLE ".$sqltblpre."faqlinkverifyrules (
id SERIAL NOT NULL,
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

//faqnews
$query[] = "CREATE TABLE ".$sqltblpre."faqnews (
id SERIAL NOT NULL,
lang varchar(5) NOT NULL,
header varchar(255) NOT NULL,
artikel text NOT NULL,
datum varchar(14) NOT NULL,
author_name  varchar(255) NULL,
author_email varchar(255) NULL,
active char(1) default 'y',
comment char(1) default 'n',
date_start varchar(14) NOT NULL DEFAULT '00000000000000',
date_end varchar(14) NOT NULL DEFAULT '99991231235959',
link varchar(255) DEFAULT NULL,
linktitel varchar(255) DEFAULT NULL,
target varchar(255) NOT NULL,
PRIMARY KEY (id))";

//faqquestions
$query[] = "CREATE TABLE ".$sqltblpre."faqquestions (
id SERIAL NOT NULL,
username varchar(100) NOT NULL,
email varchar(100) NOT NULL,
category_id int4 NOT NULL,
question text NOT NULL,
created varchar(20) NOT NULL,
is_visible char(1) default 'Y',
PRIMARY KEY (id))";

//faqright
$query[] = "CREATE TABLE ".$sqltblpre."faqright (
right_id SERIAL NOT NULL,
name VARCHAR(50) NULL,
description TEXT NULL,
for_users int4 NULL DEFAULT 1,
for_groups int4 NULL DEFAULT 1,
PRIMARY KEY (right_id)
)";

//faqsearches
$query[] = "CREATE TABLE ".$sqltblpre."faqsearches (
id SERIAL NOT NULL ,
lang VARCHAR(5) NOT NULL ,
searchterm VARCHAR(255) NOT NULL ,
searchdate TIMESTAMP,
PRIMARY KEY (id, lang)
)";

//faqsessions
$query[] = "CREATE TABLE ".$sqltblpre."faqsessions (
sid SERIAL NOT NULL,
user_id int4 NOT NULL,
ip text NOT NULL,
time int4 NOT NULL,
PRIMARY KEY (sid)
)";

//faqstopwords
$query[] = "CREATE TABLE ".$sqltblpre."faqstopwords (
id INTEGER NOT NULL,
lang VARCHAR(5) NOT NULL,
stopword VARCHAR(64) NOT NULL,
PRIMARY KEY (id, lang))";

//faqtags
$query[] = "CREATE TABLE ".$sqltblpre."faqtags (
tagging_id SERIAL NOT NULL,
tagging_name VARCHAR(255) NOT NULL,
PRIMARY KEY (tagging_id, tagging_name)
)";

//faquserdata
$query[] = "CREATE TABLE ".$sqltblpre."faquserdata (
user_id SERIAL NOT NULL,
last_modified varchar(14) NULL,
display_name VARCHAR(50) NULL,
email VARCHAR(100) NULL
)";

//faquserlogin
$query[] = "CREATE TABLE ".$sqltblpre."faquserlogin (
login VARCHAR(25) NOT NULL,
pass VARCHAR(150) NULL,
PRIMARY KEY (login)
)";

//faquser_group
$query[] = "CREATE TABLE ".$sqltblpre."faquser_group (
user_id int4 NOT NULL,
group_id int4 NOT NULL,
PRIMARY KEY (user_id, group_id)
)";

//faquser_right
$query[] = "CREATE TABLE ".$sqltblpre."faquser_right (
user_id int4 NOT NULL,
right_id int4 NOT NULL,
PRIMARY KEY (user_id, right_id)
)";

//faqvisits
$query[] = "CREATE TABLE ".$sqltblpre."faqvisits (
id SERIAL NOT NULL,
lang varchar(5) NOT NULL,
visits int4 NOT NULL,
last_visit int4 NOT NULL,
PRIMARY KEY (id, lang))";

//faqvoting
$query[] = "CREATE TABLE ".$sqltblpre."faqvoting (
id SERIAL NOT NULL,
artikel int4 NOT NULL,
vote int4 NOT NULL,
usr int4 NOT NULL,
datum varchar(20) NOT NULL default '',
ip varchar(15) NOT NULL default '',
PRIMARY KEY (id))";

//faqattachment
$query[] = "CREATE TABLE " . $sqltblpre . "faqattachment (
id SERIAL NOT NULL,
record_id int4 NOT NULL,
record_lang varchar(5) NOT NULL,
real_hash char(32) NOT NULL,
virtual_hash char(32) NOT NULL,
password_hash char(40) NULL,
filename varchar(255) NOT NULL,
filesize int NOT NULL,
encrypted int4 NOT NULL DEFAULT 0,
mime_type varchar(255) NULL,
PRIMARY KEY (id))";

//faqattachment file
$query[] = "CREATE TABLE " . $sqltblpre . "faqattachment_file (
virtual_hash char(32) NOT NULL,
contents bytea,
PRIMARY KEY (virtual_hash))";
