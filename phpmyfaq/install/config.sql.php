<?php
/**
* $Id: config.sql.php,v 1.3 2006-07-02 16:12:30 matteo Exp $
*
* INSERT instruction for configuration
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2006-07-02
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

$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('adminmail', 'webmaster@example.org')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('attmax', '100000')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('bannedIP', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('detection', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('disatt', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('enableadminlog', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('enablevisibility', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('ipcheck', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('language', '".$language."')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('ldap_support', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('metaDescription', 'phpMyFAQ should be the answer for all questions in life')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('metaKeywords', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('metaPublisher', 'John Doe')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('mod_rewrite', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('msgContactOwnText', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('numNewsArticles', '3')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('numRecordsPage', '10')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('parse_php', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('permLevel', '".$permLevel."')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('referenceURL', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('send2friendText', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('spamCheckBannedWords', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('spamEnableCatpchaCode', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('spamEnableSafeEmail', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('title', 'phpMyFAQ Codename Prometheus')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('tracking', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('URLValidateInterval', '86400')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('version', '".VERSION."')";
