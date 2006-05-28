<?php
/**
* $Id: sqlite.sql.php,v 1.12 2006-05-28 19:26:08 thorstenr Exp $
*
* CREATE TABLE instructions for SQLite
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Johannes Schlüter <johannes@php.net>
* @since        2005-08-18
* @copyright    (c) 2006 phpMyFAQ Team
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
$uninst[] = "DROP TABLE ".$sqltblpre."faqchanges";
$uninst[] = "DROP TABLE ".$sqltblpre."faqcomments";
$uninst[] = "DROP TABLE ".$sqltblpre."faqconfig";
$uninst[] = "DROP TABLE ".$sqltblpre."faqdata";
$uninst[] = "DROP TABLE ".$sqltblpre."faqdata_revisions";
$uninst[] = "DROP TABLE ".$sqltblpre."faqglossary";
$uninst[] = "DROP TABLE ".$sqltblpre."faqgroup";
$uninst[] = "DROP TABLE ".$sqltblpre."faqgroup_right";
$uninst[] = "DROP TABLE ".$sqltblpre."faqlinkverifyrules";
$uninst[] = "DROP TABLE ".$sqltblpre."faqnews";
$uninst[] = "DROP TABLE ".$sqltblpre."faqquestions";
$uninst[] = "DROP TABLE ".$sqltblpre."faqright";
$uninst[] = "DROP TABLE ".$sqltblpre."faqsessions";
$uninst[] = "DROP TABLE ".$sqltblpre."faquser";
$uninst[] = "DROP TABLE ".$sqltblpre."faquserdata";
$uninst[] = "DROP TABLE ".$sqltblpre."faquserlogin";
$uninst[] = "DROP TABLE ".$sqltblpre."faquser_group";
$uninst[] = "DROP TABLE ".$sqltblpre."faquser_right";
$uninst[] = "DROP TABLE ".$sqltblpre."faqvisits";
$uninst[] = "DROP TABLE ".$sqltblpre."faqvoting";

//faqadminlog
$query[] = "CREATE TABLE ".$sqltblpre."faqadminlog (
id int(11) NOT NULL,
time int(11) NOT NULL,
usr int(11) NOT NULL,
text text NOT NULL,
ip text NOT NULL,
PRIMARY KEY (id))";

//faqadminsessions
$query[] = "CREATE TABLE ".$sqltblpre."faqadminsessions (
uin varchar(50) NOT NULL,
usr tinytext NOT NULL,
pass varchar(64) NOT NULL,
ip text NOT NULL,
time int(11) NOT NULL)";

// faqcaptcha
$query[] = "CREATE TABLE ".$sqltblpre."faqcaptcha (
id varchar(6) NOT NULL,
useragent varchar(255) NOT NULL,
language varchar(2) NOT NULL,
ip varchar(64) NOT NULL,
captcha_time int(11) NOT NULL,
PRIMARY KEY (id))";

//faqcategories
$query[] = "CREATE TABLE ".$sqltblpre."faqcategories (
id INT(11) NOT NULL,
lang VARCHAR(5) NOT NULL,
parent_id INT(11) NOT NULL,
name VARCHAR(255) NOT NULL,
description VARCHAR(255) NOT NULL,
user_id int(2) NOT NULL,
PRIMARY KEY (id,lang))";

//faqcategoryrelations
$query[] = "CREATE TABLE ".$sqltblpre."faqcategoryrelations (
category_id INT(11) NOT NULL,
category_lang VARCHAR(5) NOT NULL default '',
record_id INT(11) NOT NULL,
record_lang VARCHAR(5) NOT NULL default '',
PRIMARY KEY  (category_id,category_lang,record_id,record_lang)
)";

//faqchanges
$query[] = "CREATE TABLE ".$sqltblpre."faqchanges (
id int(11) NOT NULL,
beitrag int(11) NOT NULL,
lang varchar(5) NOT NULL,
usr int(11) NOT NULL,
datum int(11) NOT NULL,
what text NOT NULL,
PRIMARY KEY (id))";

//faqcomments
$query[] = "CREATE TABLE ".$sqltblpre."faqcomments (
id_comment int(11) NOT NULL,
id int(11) NOT NULL,
usr varchar(255) NOT NULL,
email varchar(255) NOT NULL,
comment text NOT NULL,
datum int(15) NOT NULL,
helped text NOT NULL,
PRIMARY KEY (id_comment))";

//faqconfig
$query[] = "CREATE TABLE ".$sqltblpre."faqconfig (
config_name varchar(255) NOT NULL default '',
config_value varchar(255) NOT NULL default '',
PRIMARY KEY (config_name))";

//faqdata
$query[] = "CREATE TABLE ".$sqltblpre."faqdata (
id int(11) NOT NULL,
lang varchar(5) NOT NULL,
solution_id int(11) NOT NULL,
revision_id int(11) NOT NULL DEFAULT 0,
active char(3) NOT NULL,
keywords text NOT NULL,
thema text NOT NULL,
content longtext NOT NULL,
author varchar(255) NOT NULL,
email varchar(255) NOT NULL,
comment char(1) NOT NULL default 'y',
datum varchar(15) NOT NULL,
linkState VARCHAR(7) NOT NULL,
linkCheckDate INT(11) DEFAULT '0' NOT NULL,
PRIMARY KEY (id, lang))";

//faqdata_revisions
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_revisions (
id integer NOT NULL,
lang varchar(5) NOT NULL,
solution_id int(11) NOT NULL,
revision_id integer(11) NOT NULL DEFAULT 0,
active char(3) NOT NULL,
keywords text NOT NULL,
thema text NOT NULL,
content text NOT NULL,
author varchar(255) NOT NULL,
email varchar(255) NOT NULL,
comment char(1) default 'y',
datum varchar(15) NOT NULL,
linkState VARCHAR(7) NOT NULL,
linkCheckDate INT(11) DEFAULT '0' NOT NULL,
PRIMARY KEY (id, lang, solution_id, revision_id))";

//faqglossary
$query[] = "CREATE TABLE ".$sqltblpre."faqglossary (
id INT(11) NOT NULL ,
lang VARCHAR(2) NOT NULL ,
item VARCHAR(255) NOT NULL ,
definition TEXT NOT NULL,
PRIMARY KEY (id, lang))";

//faqgroup
$query[] = "CREATE TABLE ".$sqltblpre."faqgroup (
group_id INT(11) NOT NULL,
name VARCHAR(25) NULL,
description TEXT NULL,
auto_join INT(1) NULL,
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
id int(11) NOT NULL,
header varchar(255) NOT NULL,
artikel text NOT NULL,
datum varchar(14) NOT NULL,
link varchar(255) NOT NULL,
linktitel varchar(255) NOT NULL,
target varchar(255) NOT NULL,
PRIMARY KEY (id))";

//faqquestions
$query[] = "CREATE TABLE ".$sqltblpre."faqquestions (
id int(11) NOT NULL,
ask_username varchar(100) NOT NULL,
ask_usermail varchar(100) NOT NULL,
ask_rubrik varchar(100) NOT NULL,
ask_content text NOT NULL,
ask_date varchar(20) NOT NULL,
PRIMARY KEY (id))";

//faqright
$query[] = "CREATE TABLE ".$sqltblpre."faqright (
right_id INT(11) NOT NULL,
name VARCHAR(50) NULL,
description TEXT NULL,
for_users INT(1) NULL DEFAULT 1,
for_groups INT(1) NULL DEFAULT 1,
PRIMARY KEY(right_id)
)";

//faqsessions
$query[] = "CREATE TABLE ".$sqltblpre."faqsessions (
sid int(11) NOT NULL,
ip text NOT NULL,
time int(11) NOT NULL,
PRIMARY KEY sid (sid))";

//faquser
$query[] = "CREATE TABLE ".$sqltblpre."faquser (
user_id INT(11) NOT NULL,
login VARCHAR(25) NOT NULL,
session_id VARCHAR(150) NULL,
session_timestamp INT(11) NOT NULL,
ip VARCHAR(15) NULL,
account_status VARCHAR(50) NULL,
last_login TIMESTAMP(14) NULL,
auth_source VARCHAR(100) NULL,
member_since TIMESTAMP(14) NULL,
PRIMARY KEY(user_id),
UNIQUE INDEX session(session_id),
UNIQUE INDEX login(login)
)";

//faquserdata
$query[] = "CREATE TABLE ".$sqltblpre."faquserdata (
user_id INT(11) NOT NULL,
last_modified TIMESTAMP(14) NULL,
display_name VARCHAR(50) NULL,
email VARCHAR(100) NULL
)";

//faquserlogin
$query[] = "CREATE TABLE ".$sqltblpre."faquserlogin (
login VARCHAR(25) NOT NULL,
pass VARCHAR(150) NULL,
PRIMARY KEY(login)
)";

//Sfaquser_group
$query[] = "CREATE TABLE ".$sqltblpre."faquser_group (
user_id INT(11) NOT NULL,
group_id INT(11) NOT NULL,
PRIMARY KEY(user_id, group_id)
)";

//faquser_login
$query[] = "CREATE TABLE ".$sqltblpre."faquser_right (
user_id INT(11) NOT NULL,
right_id INT(11) NOT NULL,
PRIMARY KEY(user_id, right_id)
)";

//faqvisits
$query[] = "CREATE TABLE ".$sqltblpre."faqvisits (
id int(11) NOT NULL,
lang varchar(5) NOT NULL,
visits int(11) NOT NULL,
last_visit int(15) NOT NULL,
PRIMARY KEY (id, lang))";

//faqvoting
$query[] = "CREATE TABLE ".$sqltblpre."faqvoting (
id int(11) NOT NULL,
artikel int(11) NOT NULL,
vote int(11) NOT NULL,
usr int(11) NOT NULL,
datum varchar(20) NOT NULL default '',
ip varchar(15) NOT NULL default '',
PRIMARY KEY (id))";
