<?php
/**
 * CREATE TABLE instruction for MS SQL Server database
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
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-07-21
 */

$uninst[] = "DROP TABLE ".$sqltblpre."faqadminlog";
$uninst[] = "DROP TABLE ".$sqltblpre."faqattachment";
$uninst[] = "DROP TABLE ".$sqltblpre."faqattachment_file";
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


//faqdata_user
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_user (
record_id integer NOT NULL,
user_id integer NOT NULL,
PRIMARY KEY (record_id, user_id))";

//faqadminlog
$query[] = "CREATE TABLE ".$sqltblpre."faqadminlog (
id integer NOT NULL,
time integer NOT NULL,
usr integer NOT NULL,
text VARCHAR(8000) NOT NULL,
ip varchar(64) NOT NULL,
PRIMARY KEY (id))";

//faqattachment
$query[] = "CREATE TABLE " . $sqltblpre . "faqattachment (
id INTEGER NOT NULL,
record_id INTEGER NOT NULL,
record_lang VARCHAR(5) NOT NULL,
real_hash CHAR(32) NOT NULL,
virtual_hash CHAR(32) NOT NULL,
password_hash CHAR(40) NULL,
filename VARCHAR(255) NOT NULL,
filesize INTEGER NOT NULL,
encrypted INTEGER NOT NULL DEFAULT 0,
mime_type VARCHAR(255) NULL,
PRIMARY KEY (id))";

//faqattachment file
$query[] = "CREATE TABLE " . $sqltblpre . "faqattachment_file (
virtual_hash CHAR(32) NOT NULL,
contents TEXT NOT NULL,
PRIMARY KEY (virtual_hash))";

//faqcaptcha
$query[] = "CREATE TABLE ".$sqltblpre."faqcaptcha (
id varchar(6) NOT NULL,
useragent varchar(255) NOT NULL,
language varchar(5) NOT NULL,
ip varchar(64) NOT NULL,
captcha_time integer NOT NULL,
PRIMARY KEY (id))";

//faqcategories
$query[] = "CREATE TABLE ".$sqltblpre."faqcategories (
id integer NOT NULL,
lang varchar(5) NOT NULL,
parent_id SMALLINT NOT NULL,
name varchar(255) NOT NULL,
description varchar(255) DEFAULT NULL,
user_id integer NOT NULL,
PRIMARY KEY (id, lang))";

//faqcategoryrelations
$query[] = "CREATE TABLE ".$sqltblpre."faqcategoryrelations (
category_id integer NOT NULL,
category_lang varchar(5) NOT NULL,
record_id integer NOT NULL,
record_lang varchar(5) NOT NULL,
PRIMARY KEY  (category_id, category_lang, record_id, record_lang)
)";
$query[] = "CREATE INDEX ".$sqltblpre."idx_records ON ".$sqltblpre."faqcategoryrelations
(record_id, record_lang)";

//faqcategory_group
$query[] = "CREATE TABLE ".$sqltblpre."faqcategory_group (
category_id integer NOT NULL,
group_id integer NOT NULL,
PRIMARY KEY (category_id, group_id))";

//faqcategory_user
$query[] = "CREATE TABLE ".$sqltblpre."faqcategory_user (
category_id integer NOT NULL,
user_id integer NOT NULL,
PRIMARY KEY (category_id, user_id))";

//faqchanges
$query[] = "CREATE TABLE ".$sqltblpre."faqchanges (
id integer NOT NULL,
beitrag SMALLINT NOT NULL,
lang varchar(5) NOT NULL,
revision_id integer NOT NULL DEFAULT 0,
usr integer NOT NULL ,
datum integer NOT NULL,
what text DEFAULT NULL,
PRIMARY KEY (id, lang))";

//faqcomments
$query[] = "CREATE TABLE ".$sqltblpre."faqcomments (
id_comment integer NOT NULL,
id integer NOT NULL,
type varchar(10) NOT NULL,
usr varchar(255) NOT NULL,
email varchar(255) NOT NULL,
comment text NOT NULL,
datum varchar(64) NOT NULL,
helped text DEFAULT NULL,
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
sticky INTEGER NOT NULL,
keywords text DEFAULT NULL,
thema text NOT NULL,
content text DEFAULT NULL,
author varchar(255) NOT NULL,
email varchar(255) NOT NULL,
comment char(1) default 'y',
datum varchar(15) NOT NULL,
links_state varchar(7) DEFAULT NULL,
links_check_date integer DEFAULT 0 NOT NULL,
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
sticky INTEGER NOT NULL,
keywords text DEFAULT NULL,
thema text NOT NULL,
content text DEFAULT NULL,
author varchar(255) NOT NULL,
email varchar(255) NOT NULL,
comment char(1) default 'y',
datum varchar(15) NOT NULL,
links_state varchar(7) DEFAULT NULL,
links_check_date integer DEFAULT 0 NOT NULL,
date_start varchar(14) NOT NULL DEFAULT '00000000000000',
date_end varchar(14) NOT NULL DEFAULT '99991231235959',
PRIMARY KEY (id, lang, solution_id, revision_id))";

//faqdata_group
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_group (
record_id integer NOT NULL,
group_id integer NOT NULL,
PRIMARY KEY (record_id, group_id))";

//faqdata_tags
$query[] = "CREATE TABLE ".$sqltblpre."faqdata_tags (
record_id INTEGER NOT NULL,
tagging_id INTEGER NOT NULL,
PRIMARY KEY (record_id, tagging_id)
)";

//faqglossary
$query[] = "CREATE TABLE ".$sqltblpre."faqglossary (
id integer NOT NULL ,
lang varchar(5) NOT NULL ,
item varchar(255) NOT NULL ,
definition text NOT NULL,
PRIMARY KEY (id, lang))";

//faqgroup
$query[] = "CREATE TABLE ".$sqltblpre."faqgroup (
group_id integer NOT NULL,
name varchar(25) NULL,
description text NULL,
auto_join integer NULL,
PRIMARY KEY(group_id)
)
CREATE UNIQUE INDEX idxName ON ".$sqltblpre."faqgroup (name)";

//faqgroup_right
$query[] = "CREATE TABLE ".$sqltblpre."faqgroup_right (
group_id integer NOT NULL,
right_id integer NOT NULL,
PRIMARY KEY(group_id, right_id)
)";

//faqlinkverifyrules
$query[] = "CREATE TABLE ".$sqltblpre."faqlinkverifyrules (
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

//faqnews
$query[] = "CREATE TABLE ".$sqltblpre."faqnews (
id integer NOT NULL,
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
id integer NOT NULL,
username varchar(100) NOT NULL,
email varchar(100) NOT NULL,
category_id integer NOT NULL,
question text NOT NULL,
created varchar(20) NOT NULL,
is_visible char(1) default 'Y',
answer_id INTEGER NOT NULL DEFAULT 0,
PRIMARY KEY (id))";

//faqright
$query[] = "CREATE TABLE ".$sqltblpre."faqright (
right_id integer NOT NULL,
name varchar(50) NULL,
description text NULL,
for_users integer NULL DEFAULT 1,
for_groups integer NULL DEFAULT 1,
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
sid integer NOT NULL,
user_id integer NOT NULL,
ip varchar(64) NOT NULL,
time integer NOT NULL,
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
tagging_id INTEGER NOT NULL,
tagging_name VARCHAR(255) NOT NULL ,
PRIMARY KEY (tagging_id, tagging_name)
)";

//faquser
$query[] = "CREATE TABLE ".$sqltblpre."faquser (
user_id integer NOT NULL,
login varchar(25) NOT NULL,
session_id varchar(150) NULL,
session_timestamp integer NULL,
ip varchar(15) NULL,
account_status varchar(50) NULL,
last_login varchar(14) NULL,
auth_source varchar(100) NULL,
member_since varchar(14) NULL,
PRIMARY KEY (user_id))";

//faquserdata
$query[] = "CREATE TABLE ".$sqltblpre."faquserdata (
user_id integer NOT NULL,
last_modified varchar(14) NULL,
display_name varchar(50) NULL,
email varchar(100) NULL
)";

//faquserlogin
$query[] = "CREATE TABLE ".$sqltblpre."faquserlogin (
login varchar(128) NOT NULL,
pass varchar(80) NULL,
PRIMARY KEY (login)
)";

//faquser_group
$query[] = "CREATE TABLE ".$sqltblpre."faquser_group (
user_id integer NOT NULL,
group_id integer NOT NULL,
PRIMARY KEY (user_id, group_id)
)";

//faquser_right
$query[] = "CREATE TABLE ".$sqltblpre."faquser_right (
user_id integer NOT NULL,
right_id integer NOT NULL,
PRIMARY KEY (user_id, right_id)
)";

//faqvisits
$query[] = "CREATE TABLE ".$sqltblpre."faqvisits (
id integer NOT NULL,
lang varchar(5) NOT NULL,
visits SMALLINT NOT NULL,
last_visit integer NOT NULL,
PRIMARY KEY (id, lang))";

//faqvoting
$query[] = "CREATE TABLE ".$sqltblpre."faqvoting (
id integer NOT NULL,
artikel SMALLINT NOT NULL,
vote SMALLINT NOT NULL,
usr SMALLINT NOT NULL,
datum varchar(20) DEFAULT '',
ip varchar(15) DEFAULT '',
PRIMARY KEY (id))";