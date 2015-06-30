<?php
/**
 * CREATE TABLE instruction for PostgreSQL database
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Setup
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2004-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2004-09-18
 */

$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqadminlog CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqattachment CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqattachment_file CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqcaptcha CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqcategories CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqcategoryrelations CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqcategory_group CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqcategory_user CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqchanges CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqcomments CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqconfig CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqdata CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqdata_revisions CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqdata_group CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqdata_tags CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqdata_user CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqglossary CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqgroup CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqgroup_right CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqinstances CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqinstances_config CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqnews CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqquestions CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqright CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqsearches CASCADE";
$uninst[] = "DROP SEQUENCE IF EXISTS " . PMF_Db::getTablePrefix() . "faqsearch_id_seq";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqsessions CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqstopwords CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqtags CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faquser CASCADE";
$uninst[] = "DROP SEQUENCE IF EXISTS " . PMF_Db::getTablePrefix() . "faquser_user_id_seq";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faquserdata CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faquserlogin CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faquser_group CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faquser_right CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqvisits CASCADE";
$uninst[] = "DROP TABLE IF EXISTS " . PMF_Db::getTablePrefix() . "faqvoting CASCADE";

//faquser
$query[] = "CREATE SEQUENCE " . PMF_Db::getTablePrefix() . "faquser_user_id_seq START WITH 2";

$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faquser (
user_id SERIAL NOT NULL,
login varchar(128) NOT NULL,
session_id varchar(150) NULL,
session_timestamp int4 NULL,
ip varchar(15) NULL,
account_status varchar(50) NULL,
last_login varchar(14) NULL,
auth_source varchar(100) NULL,
member_since varchar(14) NULL,
remember_me VARCHAR(150) NULL,
success INTEGER NULL DEFAULT 1,
PRIMARY KEY (user_id))";

//faqgroup
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqgroup (
group_id SERIAL NOT NULL,
name VARCHAR(25) NULL,
description TEXT NULL,
auto_join int4 NULL,
PRIMARY KEY (group_id)
)";

//faqadminlog
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqadminlog (
id SERIAL NOT NULL,
time int4 NOT NULL,
usr int4 NOT NULL,
text text NOT NULL,
ip text NOT NULL,
PRIMARY KEY (id))";

//faqcaptcha
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqcaptcha (
id varchar(6) NOT NULL,
useragent varchar(255) NOT NULL,
language varchar(5) NOT NULL,
ip varchar(64) NOT NULL,
captcha_time int4 NOT NULL,
PRIMARY KEY (id))";

//faqcategories
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqcategories (
id SERIAL NOT NULL,
lang varchar(5) NOT NULL,
parent_id int4 NOT NULL,
name varchar(255) NOT NULL,
description varchar(255) DEFAULT NULL,
user_id int4 NOT NULL,
group_id int4 NOT NULL DEFAULT -1,
active INT4 NULL DEFAULT 1,
PRIMARY KEY (id, lang))";

//faqcategoryrelations
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqcategoryrelations (
category_id int4 NOT NULL,
category_lang VARCHAR(5) NOT NULL,
record_id int4 NOT NULL,
record_lang VARCHAR(5) NOT NULL,
PRIMARY KEY  (category_id, category_lang, record_id, record_lang)
)";
$query[] = "CREATE INDEX " . PMF_Db::getTablePrefix() . "idx_records ON " . PMF_Db::getTablePrefix() . "faqcategoryrelations
(record_id, record_lang)";

//faqcategory_group
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqcategory_group (
category_id int4 NOT NULL,
group_id int4 NOT NULL,
PRIMARY KEY (category_id, group_id))";

//faqcategory_user
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqcategory_user (
category_id int4 NOT NULL,
user_id int4 NOT NULL,
PRIMARY KEY (category_id, user_id))";

//faqchanges
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqchanges (
id SERIAL NOT NULL,
beitrag int4 NOT NULL,
lang varchar(5) NOT NULL,
revision_id int4 NOT NULL DEFAULT 0,
usr int4 NOT NULL,
datum int4 NOT NULL,
what text DEFAULT NULL,
PRIMARY KEY (id, lang))";

//faqcomments
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqcomments (
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
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqconfig (
config_name varchar(255) NOT NULL default '',
config_value varchar(255) DEFAULT NULL,
PRIMARY KEY (config_name))";

//faqdata
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqdata (
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
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqdata_revisions (
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
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqdata_group (
record_id int4 NOT NULL,
group_id int4 NOT NULL,
PRIMARY KEY (record_id, group_id))";

//faqdata_tags
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqdata_tags (
record_id INT4 NOT NULL,
tagging_id INT4 NOT NULL,
PRIMARY KEY (record_id, tagging_id)
)";

//faqdata_user
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqdata_user (
record_id int4 NOT NULL,
user_id int4 NOT NULL,
PRIMARY KEY (record_id, user_id))";

//faqglossary
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqglossary (
id SERIAL NOT NULL,
lang VARCHAR(5) NOT NULL,
item VARCHAR(255) NOT NULL,
definition TEXT NOT NULL,
PRIMARY KEY (id, lang))";

//faqgroup_right
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqgroup_right (
group_id int4 NOT NULL,
right_id int4 NOT NULL,
PRIMARY KEY (group_id, right_id)
)";

//faqinstances
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqinstances (
id SERIAL NOT NULL,
url VARCHAR(255) NOT NULL,
instance VARCHAR(255) NOT NULL,
comment TEXT NULL,
created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
modified TIMESTAMP NOT NULL,
PRIMARY KEY (id)
)";

//faqinstances_config
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqinstances_config (
instance_id int4 NOT NULL,
config_name VARCHAR(255) NOT NULL default '',
config_value VARCHAR(255) DEFAULT NULL,
PRIMARY KEY (instance_id, config_name)
)";

//faqnews
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqnews (
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
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqquestions (
id SERIAL NOT NULL,
lang varchar(5) NOT NULL,
username varchar(100) NOT NULL,
email varchar(100) NOT NULL,
category_id int4 NOT NULL,
question text NOT NULL,
created varchar(20) NOT NULL,
is_visible char(1) default 'Y',
answer_id INTEGER NOT NULL DEFAULT 0,
PRIMARY KEY (id))";

//faqright
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqright (
right_id SERIAL NOT NULL,
name VARCHAR(50) NULL,
description TEXT NULL,
for_users int4 NULL DEFAULT 1,
for_groups int4 NULL DEFAULT 1,
PRIMARY KEY (right_id)
)";

//faqsearches
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqsearches (
id SERIAL NOT NULL ,
lang VARCHAR(5) NOT NULL ,
searchterm VARCHAR(255) NOT NULL ,
searchdate TIMESTAMP,
PRIMARY KEY (id, lang)
)";

//faqsessions
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqsessions (
sid SERIAL NOT NULL,
user_id int4 NOT NULL,
ip text NOT NULL,
time int4 NOT NULL,
PRIMARY KEY (sid)
)";
$query[] = "CREATE INDEX " . PMF_Db::getTablePrefix() . "index_time ON " . PMF_Db::getTablePrefix() . "faqsessions (time)";

//faqstopwords
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqstopwords (
id INTEGER NOT NULL,
lang VARCHAR(5) NOT NULL,
stopword VARCHAR(64) NOT NULL,
PRIMARY KEY (id, lang))";

//faqtags
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqtags (
tagging_id SERIAL NOT NULL,
tagging_name VARCHAR(255) NOT NULL,
PRIMARY KEY (tagging_id, tagging_name)
)";

//faquserdata
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faquserdata (
user_id SERIAL NOT NULL,
last_modified varchar(14) NULL,
display_name VARCHAR(128) NULL,
email VARCHAR(128) NULL
)";

//faquserlogin
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faquserlogin (
login VARCHAR(128) NOT NULL,
pass VARCHAR(80) NULL,
PRIMARY KEY (login)
)";

//faquser_group
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faquser_group (
user_id int4 NOT NULL,
group_id int4 NOT NULL,
PRIMARY KEY (user_id, group_id)
)";

//faquser_right
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faquser_right (
user_id int4 NOT NULL,
right_id int4 NOT NULL,
PRIMARY KEY (user_id, right_id)
)";

//faqvisits
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqvisits (
id SERIAL NOT NULL,
lang varchar(5) NOT NULL,
visits int4 NOT NULL,
last_visit int4 NOT NULL,
PRIMARY KEY (id, lang))";

//faqvoting
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqvoting (
id SERIAL NOT NULL,
artikel int4 NOT NULL,
vote int4 NOT NULL,
usr int4 NOT NULL,
datum varchar(20) NOT NULL default '',
ip varchar(15) NOT NULL default '',
PRIMARY KEY (id))";

//faqattachment
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqattachment (
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
$query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqattachment_file (
virtual_hash char(32) NOT NULL,
contents bytea,
PRIMARY KEY (virtual_hash))";
