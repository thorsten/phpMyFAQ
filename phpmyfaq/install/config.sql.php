<?php
/**
 * $Id: config.sql.php,v 1.30 2007-03-29 18:06:15 thorstenr Exp $
 *
 * INSERT instruction for configuration
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2006-07-02
 * @copyright   (c) 2006-2007 phpMyFAQ Team
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

$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.administrationMail', 'webmaster@example.org')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.maxAttachmentSize', '100000')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.bannedIPs', '')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.languageDetection', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.disableAttachments', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.enableAdminLog', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('records.enableVisibilityQuestions', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.ipCheck', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.language', '".$language."')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.ldapSupport', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.metaDescription', 'phpMyFAQ should be the answer for all questions in life')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.metaKeywords', '')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.metaPublisher', 'John Doe')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.enableRewriteRules', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.contactInformations', '')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.numberOfShownNewsEntries', '3')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('numRecordsPage', '10')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('records.numberOfRelatedArticles', '5')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.permLevel', '".$permLevel."')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.phpMyFAQToken', '')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('records.orderby', 'id')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('records.sortby', 'DESC')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.referenceURL', '')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('send2friendText', '')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('spamCheckBannedWords', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('spamEnableCatpchaCode', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('spamEnableSafeEmail', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('title', 'phpMyFAQ Codename \"Prometheus\"')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('tracking', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('URLValidateInterval', '86400')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('version', '".VERSION."')";
