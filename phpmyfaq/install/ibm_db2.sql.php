<?php
/**
* $Id: ibm_db2.sql.php,v 1.17.2.1 2007-05-12 13:59:22 thorstenr Exp $
*
* CREATE TABLE instruction for IBM DB2 Universal Database, IBM Cloudscape,
* and Apache Derby databases
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2005-07-31
* @copyright    (c) 2005-2007 phpMyFAQ Team
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
$uninst[] = "DROP TABLE ".$sqltblpre."faqsessions";
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
id integer NOT NULL,
time integer NOT NULL,
usr varchar(255) NOT NULL,
text varchar(512) NOT NULL,
ip varchar(64) NOT NULL,
PRIMARY KEY (id))";

//faqcaptcha
$query[] = "CREATE TABLE ".$sqltblpre."faqcaptcha (
id varchar(6) NOT NULL,
useragent varchar(255) NOT NULL,
language varchar(2) NOT NULL,
ip varchar(64) NOT NULL,
captcha_time integer NOT NULL,
PRIMARY KEY (id))";

//faqcategories
$query[] = "CREATE TABLE ".$sqltblpre."faqcategories (
id integer NOT NULL,
lang varchar(5) NOT NULL,
parent_id INTEGER NOT NULL,
name varchar(255) NOT NULL,
description varchar(255) DEFAULT NULL,
user_id integer NOT NULL,
PRIMARY KEY (id, lang))";

//faqcategoryrelations
$query[] = "CREATE TABLE ".$sqltblpre."faqcategoryrelations (
category_id INTEGER NOT NULL,
category_lang VARCHAR(5) NOT NULL default '',
record_id INTEGER NOT NULL,
record_lang VARCHAR(5) NOT NULL default '',
PRIMARY KEY  (category_id, category_lang, record_id, record_lang)
)";
$query[] = "CREATE INDEX idx_records ON ".$sqltblpre."faqcategoryrelations
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
lang varchar(5) NOT NULL,
revision_id integer NOT NULL DEFAULT 0,
usr varchar(255) NOT NULL,
datum INTEGER NOT NULL,
what varchar(512) DEFAULT NULL,
PRIMARY KEY (id, lang))";

//faqcomments
$query[] = "CREATE TABLE  ".$sqltblpre."faqcomments (
id_comment integer NOT NULL,
id integer NOT NULL,
type varchar(10) NOT NULL,
usr varchar(255) NOT NULL,
email varchar(255) NOT NULL,
comment CLOB NOT NULL,
datum varchar(64) NOT NULL,
helped varchar(255) DEFAULT NULL,
PRIMARY KEY (id_comment))";

//faqconfig
$query[] = "CREATE TABLE ".$sqltblpre."faqconfig (
config_name varchar(255) NOT NULL default '',
config_value varchar(255) DEFAULT NULL,
PRIMARY KEY (config_name))";

//faqdata
$query[] = "CREATE TABLE ".$sqltblpre."faqdata (
id integer NOT NULL,
lang varchar(5) NOT NULL,
solution_id integer NOT NULL,
revision_id integer NOT NULL DEFAULT 0,
active char(3) NOT NULL,
keywords varchar(255) DEFAULT NULL,
thema varchar(255) NOT NULL,
content CLOB DEFAULT NULL,
author varchar(255) NOT NULL,
email varchar(255) NOT NULL,
comment char(1) default 'y',
datum varchar(15) NOT NULL,
links_state varchar(7) DEFAULT NULL,
links_check_date integer DEFAULT 0,
date_start varchar(14) NOT NULL DEFAULT '00000000000000',
date_end varchar(14) NOT NULL DEFAULT '99991231235959',
PRIMARY KEY (id, lang))";

//faqdata_revisions
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_revisions (
id integer NOT NULL,
lang varchar(5) NOT NULL,
solution_id integer NOT NULL,
revision_id integer NOT NULL DEFAULT 0,
active char(3) NOT NULL,
keywords varchar(255) DEFAULT NULL,
thema varchar(255) NOT NULL,
content CLOB DEFAULT NULL,
author varchar(255) NOT NULL,
email varchar(255) NOT NULL,
comment char(1) default 'y',
datum varchar(15) NOT NULL,
links_state varchar(7) DEFAULT NULL,
links_check_date integer DEFAULT 0,
date_start varchar(14) NOT NULL DEFAULT '00000000000000',
date_end varchar(14) NOT NULL DEFAULT '99991231235959',
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
name VARCHAR(25) NOT NULL,
description CLOB NOT NULL,
auto_join INTEGER NOT NULL,
PRIMARY KEY(group_id)
)";

//faqgroup_right
$query[] = "CREATE TABLE ".$sqltblpre."faqgroup_right (
group_id INTEGER NOT NULL,
right_id INTEGER NOT NULL,
PRIMARY KEY(group_id, right_id)
)";

//faqlinkverifyrules
$query[] = "CREATE TABLE ".$sqltblpre."faqlinkverifyrules (
id INTEGER NOT NULL,
type varchar(6) NOT NULL default '',
url varchar(255) NOT NULL default '',
reason varchar(255) NOT NULL default '',
enabled VARCHAR(1) NOT NULL default 'y',
locked VARCHAR(1) NOT NULL default 'n',
owner varchar(255) NOT NULL default '',
dtInsertDate varchar(15) NOT NULL default '',
dtUpdateDate varchar(15) NOT NULL default '',
PRIMARY KEY (id)
)";

//faqnews
$query[] = "CREATE TABLE ".$sqltblpre."faqnews (
id integer NOT NULL,
lang varchar(5) NOT NULL,
header varchar(255) NOT NULL,
artikel CLOB NOT NULL,
datum varchar(14) NOT NULL,
author_name  varchar(255) NOT NULL,
author_email varchar(255) NOT NULL,
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
id integer NOT NULL,
ask_username varchar(100) NOT NULL,
ask_usermail varchar(100) NOT NULL,
ask_rubrik integer NOT NULL,
ask_content CLOB NOT NULL,
ask_date varchar(20) NOT NULL,
is_visible char(1) default 'Y',
PRIMARY KEY (id))";

//faqright
$query[] = "CREATE TABLE ".$sqltblpre."faqright (
right_id INTEGER NOT NULL,
name VARCHAR(50) NOT NULL,
description CLOB NOT NULL,
for_users INTEGER NOT NULL DEFAULT 1,
for_groups INTEGER NOT NULL DEFAULT 1,
PRIMARY KEY (right_id)
)";

//faqsessions
$query[] = "CREATE TABLE ".$sqltblpre."faqsessions (
sid INTEGER NOT NULL,
user_id INTEGER NOT NULL,
ip varchar(64) NOT NULL,
time integer NOT NULL,
PRIMARY KEY (sid)
)";

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
session_id VARCHAR(150) NOT NULL,
session_timestamp INTEGER NOT NULL,
ip VARCHAR(15) NOT NULL,
account_status VARCHAR(50) NOT NULL,
last_login VARCHAR(14) NOT NULL,
auth_source VARCHAR(100) NOT NULL,
member_since VARCHAR(14) NOT NULL,
PRIMARY KEY (user_id)
)";

//faquserdata
$query[] = "CREATE TABLE ".$sqltblpre."faquserdata (
user_id INTEGER NOT NULL,
last_modified varchar(14) NOT NULL,
display_name VARCHAR(50) NOT NULL,
email VARCHAR(100) NOT NULL
)";

//faquserlogin
$query[] = "CREATE TABLE ".$sqltblpre."faquserlogin (
login VARCHAR(25) NOT NULL,
pass VARCHAR(25) NOT NULL,
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
lang varchar(5) NOT NULL,
visits INTEGER NOT NULL,
last_visit INTEGER NOT NULL,
PRIMARY KEY (id, lang))";

//faqvoting
$query[] = "CREATE TABLE ".$sqltblpre."faqvoting (
id integer NOT NULL,
artikel INTEGER NOT NULL,
vote INTEGER NOT NULL,
usr INTEGER NOT NULL,
datum varchar(20) DEFAULT '',
ip varchar(15) DEFAULT '',
PRIMARY KEY (id))";
