<?php
/**
 * INSERT instruction for configuration
 *
 * @package    phpMyFAQ
 * @subpackage Installation
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2006-07-02
 * @version    SVN: $Id$
 * @copyright  2006-2009 phpMyFAQ Team
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
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.bannedIPs', '')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.contactInformations', '')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.currentVersion', '".VERSION."')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.currentApiVersion', '".APIVERSION."')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.disableAttachments', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.enableAdminLog', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.enableRewriteRules', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.enableUserTracking', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.ipCheck', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.language', '".$language."')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.languageDetection', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.ldapSupport', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.maxAttachmentSize', '100000')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.metaDescription', 'phpMyFAQ should be the answer for all questions in life')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.metaKeywords', '')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.metaPublisher', 'John Doe')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.numberOfRecordsPerPage', '10')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.numberOfShownNewsEntries', '3')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.permLevel', '".$permLevel."')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.phpMyFAQToken', '')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.referenceURL', '')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.send2friendText', '')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.titleFAQ', 'phpMyFAQ Codename Portia')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.urlValidateInterval', '86400')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.enableWysiwygEditor', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.attachmentsPath', 'attachments')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.templateSet', 'default')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.attachmentsStorageType', '0')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.enableAttachmentEncryption', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.defaultAttachmentEncKey', '')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.useSslForLogins', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.numberSearchTerms', '10')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.orderingPopularFaqs', 'visits')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.optionalMailAddress', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.useAjaxSearchOnStartpage', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.enableGoogleTranslation', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('main.googleTranslationKey', 'INSERT-YOUR-KEY')";

$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('records.defaultActivation', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('records.defaultAllowComments', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('records.enableVisibilityQuestions', 'false')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('records.numberOfRelatedArticles', '5')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('records.orderby', 'id')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('records.sortby', 'DESC')";

$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('spam.checkBannedWords', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('spam.enableCaptchaCode', 'true')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('spam.enableSafeEmail', 'true')";

$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('search.relevance', 'thema,content,keywords')";
$query[] = "INSERT INTO ".$sqltblpre."faqconfig VALUES ('search.enableRelevance', 'false')";
