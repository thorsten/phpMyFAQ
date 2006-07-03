<?php
/**
* $Id: mysql.update.sql.php,v 1.1 2006-07-03 20:42:01 matteo Exp $
*
* CREATE TABLE instruction for MySQL database
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Tom Rochester <tom.rochester@gmail.com>
* @author       Lars Tiedemann <php@larstiedemann.de>
* @since        2006-07-03
* @copyright    (c) 2001-2006 phpMyFAQ Team
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

//faqconfig
$query[] = "CREATE TABLE ".$sqltblpre."faqconfig (
config_name varchar(255) NOT NULL default '',
config_value varchar(255) NOT NULL default '',
PRIMARY KEY (config_name))";

//faqglossary
$query[] = "CREATE TABLE ".$sqltblpre."faqglossary (
id INT(11) NOT NULL ,
lang VARCHAR(2) NOT NULL ,
item VARCHAR(255) NOT NULL ,
definition TEXT NOT NULL,
PRIMARY KEY (id, lang))";

//faqgroup
$query[] = "CREATE TABLE IF NOT EXISTS ".$sqltblpre."faqgroup (
group_id INT(11) UNSIGNED NOT NULL,
name VARCHAR(25) NULL,
description TEXT NULL,
auto_join INT(1) UNSIGNED NULL,
PRIMARY KEY(group_id),
UNIQUE INDEX name(name)
)";

//faqgroup_right
$query[] = "CREATE TABLE IF NOT EXISTS ".$sqltblpre."faqgroup_right (
group_id INT(11) UNSIGNED NOT NULL,
right_id INT(11) UNSIGNED NOT NULL,
PRIMARY KEY(group_id, right_id)
)";

//faqlinkverifyrules
$query[] = "CREATE TABLE ".$sqltblpre."faqlinkverifyrules (
id int(11) NOT NULL default '0',
type varchar(6) NOT NULL default '',
url varchar(255) NOT NULL default '',
reason varchar(255) NOT NULL default '',
enabled enum('y','n') NOT NULL default 'y',
locked enum('y','n') NOT NULL default 'n',
owner varchar(255) NOT NULL default '',
dtInsertDate varchar(15) NOT NULL default '',
dtUpdateDate varchar(15) NOT NULL default '',
PRIMARY KEY (id)
)";

//faqright
$query[] = "CREATE TABLE IF NOT EXISTS ".$sqltblpre."faqright (
right_id INT(11) UNSIGNED NOT NULL,
name VARCHAR(50) NULL,
description TEXT NULL,
for_users INT(1) UNSIGNED NULL DEFAULT 1,
for_groups INT(1) UNSIGNED NULL DEFAULT 1,
PRIMARY KEY(right_id)
)";

//faquser
$query[] = "CREATE TABLE IF NOT EXISTS ".$sqltblpre."faquser (
user_id INT(11) UNSIGNED NOT NULL,
login VARCHAR(25) NOT NULL,
session_id VARCHAR(150) NULL,
session_timestamp INT(11) UNSIGNED NOT NULL,
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
$query[] = "CREATE TABLE IF NOT EXISTS ".$sqltblpre."faquserdata (
user_id INT(11) UNSIGNED NOT NULL,
last_modified TIMESTAMP(14) NULL,
display_name VARCHAR(50) NULL,
email VARCHAR(100) NULL
)";

//faquserlogin
$query[] = "CREATE TABLE IF NOT EXISTS ".$sqltblpre."faquserlogin (
login VARCHAR(25) NOT NULL,
pass VARCHAR(150) NULL,
PRIMARY KEY(login)
)";

//faquser_group
$query[] = "CREATE TABLE IF NOT EXISTS ".$sqltblpre."faquser_group (
user_id INT(11) UNSIGNED NOT NULL,
group_id INT(11) UNSIGNED NOT NULL,
PRIMARY KEY(user_id, group_id)
)";

//faquser_right
$query[] = "CREATE TABLE IF NOT EXISTS ".$sqltblpre."faquser_right (
user_id INT(11) UNSIGNED NOT NULL,
right_id INT(11) UNSIGNED NOT NULL,
PRIMARY KEY(user_id, right_id)
)";