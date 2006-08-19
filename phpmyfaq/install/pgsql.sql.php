<?php
/**
* $Id: pgsql.sql.php,v 1.24 2006-08-19 13:02:33 matteo Exp $
*
* CREATE TABLE instruction for PostgreSQL database
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Tom Rochester <tom.rochester@gmail.com>
* @author       Matteo Scaramuccia <matteo@scaramuccia.com>
* @since        2004-09-18
* @copyright    (c) 2004-2006 phpMyFAQ Team
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
$uninst[] = "DROP TABLE ".$sqltblpre."faqadminsessions";
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
$uninst[] = "DROP TABLE ".$sqltblpre."faqsessions";
$uninst[] = "DROP TABLE ".$sqltblpre."faqtags";
$uninst[] = "DROP TABLE ".$sqltblpre."faquser";
$uninst[] = "DROP TABLE ".$sqltblpre."faquserdata";
$uninst[] = "DROP TABLE ".$sqltblpre."faquserlogin";
$uninst[] = "DROP TABLE ".$sqltblpre."faquser_group";
$uninst[] = "DROP TABLE ".$sqltblpre."faquser_right";
$uninst[] = "DROP TABLE ".$sqltblpre."faqvisits";
$uninst[] = "DROP TABLE ".$sqltblpre."faqvoting";
// DROP SEQUENCES
$uninst[] = "DROP SEQUENCE faqadminlog_id_seq";
$uninst[] = "DROP SEQUENCE faqcategories_id_seq";
$uninst[] = "DROP SEQUENCE faqchanges_id_seq";
$uninst[] = "DROP SEQUENCE faqcomments_id_comment_seq";
$uninst[] = "DROP SEQUENCE faqdata_id_seq";
$uninst[] = "DROP SEQUENCE faqdata_revisions_id_seq";
$uninst[] = "DROP SEQUENCE faqdata_tags_tagging_id_seq";
$uninst[] = "DROP SEQUENCE faqglossary_id_seq";
$uninst[] = "DROP SEQUENCE faqgroup_group_id_seq";
$uninst[] = "DROP SEQUENCE faqlinkverifyrules_id_seq";
$uninst[] = "DROP SEQUENCE faqnews_id_seq";
$uninst[] = "DROP SEQUENCE faqquestions_id_seq";
$uninst[] = "DROP SEQUENCE faqright_right_id_seq";
$uninst[] = "DROP SEQUENCE faqsessions_sid_seq";
$uninst[] = "DROP SEQUENCE faquser_user_id_seq";
$uninst[] = "DROP SEQUENCE faquserdata_user_id_seq";
$uninst[] = "DROP SEQUENCE faqvisits_id_seq";
$uninst[] = "DROP SEQUENCE faqvoting_id_seq";

//faquser
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
usr int4 NOT NULL REFERENCES ".$sqltblpre."faquser(user_id),
text text NOT NULL,
ip text NOT NULL,
PRIMARY KEY (id))";

//faqadminsessions
$query[] = "CREATE TABLE ".$sqltblpre."faqadminsessions (
uin varchar(50) NOT NULL,
usr text NOT NULL,
pass varchar(64) NOT NULL,
ip text NOT NULL,
time int4 NOT NULL)";

//faqcaptcha
$query[] = "CREATE TABLE ".$sqltblpre."faqcaptcha (
id varchar(6) NOT NULL,
useragent varchar(255) NOT NULL,
language varchar(2) NOT NULL,
ip varchar(64) NOT NULL,
captcha_time int4 NOT NULL,
PRIMARY KEY (id))";

//faqcategories
$query[] = "CREATE TABLE ".$sqltblpre."faqcategories (
id SERIAL NOT NULL,
lang varchar(5) NOT NULL,
parent_id int4 NOT NULL,
name varchar(255) NOT NULL,
description varchar(255) NOT NULL,
user_id int4 NOT NULL REFERENCES ".$sqltblpre."faquser(user_id),
PRIMARY KEY (id, lang))";

//faqcategoryrelations
$query[] = "CREATE TABLE ".$sqltblpre."faqcategoryrelations (
category_id int4 NOT NULL,
category_lang VARCHAR(5) NOT NULL,
record_id int4 NOT NULL,
record_lang VARCHAR(5) NOT NULL,
PRIMARY KEY  (category_id,category_lang,record_id,record_lang)
)";

//faqcategory_group
$query[] = "CREATE TABLE ".$sqltblpre."faqcategory_group (
category_id int4 NOT NULL,
group_id int4 NOT NULL REFERENCES ".$sqltblpre."faqgroup(group_id),
PRIMARY KEY (category_id, group_id))";

//faqcategory_user
$query[] = "CREATE TABLE ".$sqltblpre."faqcategory_user (
category_id int4 NOT NULL,
user_id int4 NOT NULL REFERENCES ".$sqltblpre."faquser(user_id),
PRIMARY KEY (category_id, user_id))";

//faqchanges
$query[] = "CREATE TABLE ".$sqltblpre."faqchanges (
id SERIAL NOT NULL,
beitrag int4 NOT NULL,
lang varchar(5) NOT NULL,
revision_id int4 NOT NULL DEFAULT 0,
usr int4 NOT NULL REFERENCES ".$sqltblpre."faquser(user_id),
datum int4 NOT NULL,
what text NOT NULL,
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
helped text NOT NULL,
PRIMARY KEY (id_comment))";

//faqconfig
$query[] = "CREATE TABLE ".$sqltblpre."faqconfig (
config_name varchar(255) NOT NULL default '',
config_value varchar(255) NOT NULL default '',
PRIMARY KEY (config_name))";

//faqdata
$query[] = "CREATE TABLE ".$sqltblpre."faqdata (
id SERIAL NOT NULL,
lang varchar(5) NOT NULL,
solution_id int4 NOT NULL,
revision_id int4 NOT NULL DEFAULT 0,
active char(3) NOT NULL,
keywords text NOT NULL,
thema text NOT NULL,
content text NOT NULL,
author varchar(255) NOT NULL,
email varchar(255) NOT NULL,
comment char(1) NOT NULL default 'y',
datum varchar(15) NOT NULL,
links_state varchar(7) NOT NULL,
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
keywords text NOT NULL,
thema text NOT NULL,
content text NOT NULL,
author varchar(255) NOT NULL,
email varchar(255) NOT NULL,
comment char(1) default 'y',
datum varchar(15) NOT NULL,
links_state varchar(7) NOT NULL,
links_check_date int4 DEFAULT 0 NOT NULL,
date_start varchar(14) NOT NULL DEFAULT '00000000000000',
date_end varchar(14) NOT NULL DEFAULT '99991231235959',
PRIMARY KEY (id, lang, solution_id, revision_id))";

//faqdata_group
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_group (
record_id int4 NOT NULL,
group_id int4 NOT NULL REFERENCES ".$sqltblpre."faqgroup(group_id),
PRIMARY KEY (record_id, group_id))";

//faqdata_tags
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_tags (
tagging_id SERIAL NOT NULL,
tagging_name VARCHAR(255) NOT NULL,
PRIMARY KEY (tagging_id, tagging_name)
)";

//faqdata_user
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_user (
record_id int4 NOT NULL,
user_id int4 NOT NULL REFERENCES ".$sqltblpre."faquser(user_id),
PRIMARY KEY (record_id, user_id))";

//faqglossary
$query[] = "CREATE TABLE ".$sqltblpre."faqglossary (
id SERIAL NOT NULL,
lang VARCHAR(2) NOT NULL,
item VARCHAR(255) NOT NULL,
definition TEXT NOT NULL,
PRIMARY KEY (id, lang))";

//faqgroup_right
$query[] = "CREATE TABLE ".$sqltblpre."faqgroup_right (
group_id int4 NOT NULL REFERENCES ".$sqltblpre."faqgroup(group_id),
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
link varchar(255) NOT NULL,
linktitel varchar(255) NOT NULL,
target varchar(255) NOT NULL,
PRIMARY KEY (id))";

//faqquestions
$query[] = "CREATE TABLE ".$sqltblpre."faqquestions (
id SERIAL NOT NULL,
ask_username varchar(100) NOT NULL,
ask_usermail varchar(100) NOT NULL,
ask_rubrik varchar(100) NOT NULL,
ask_content text NOT NULL,
ask_date varchar(20) NOT NULL,
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

//faqsessions
$query[] = "CREATE TABLE ".$sqltblpre."faqsessions (
sid SERIAL NOT NULL,
ip text NOT NULL,
time int4 NOT NULL,
PRIMARY KEY (sid)
)";

//faqtags
$query[] = "CREATE TABLE ".$sqltblpre."faqtags (
record_id INT4 NOT NULL,
tagging_id INT4 NOT NULL,
PRIMARY KEY (record_id, tagging_id)
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
user_id int4 NOT NULL REFERENCES ".$sqltblpre."faquser(user_id),
group_id int4 NOT NULL REFERENCES ".$sqltblpre."faqgroup(group_id),
PRIMARY KEY (user_id, group_id)
)";

//faquser_right
$query[] = "CREATE TABLE ".$sqltblpre."faquser_right (
user_id int4 NOT NULL REFERENCES ".$sqltblpre."faquser(user_id),
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
usr int4 NOT NULL REFERENCES ".$sqltblpre."faquser(user_id),
datum varchar(20) NOT NULL default '',
ip varchar(15) NOT NULL default '',
PRIMARY KEY (id))";
