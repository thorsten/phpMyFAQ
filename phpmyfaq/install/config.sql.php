<?php
/**
 * INSERT queries for the phpMyFAQ configuration for fresh installations
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Setup
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-07-02
 */

// Main
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.administrationMail', 'webmaster@example.org')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.contactInformations', '')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.currentVersion', '" . PMF_System::getVersion() . "')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.currentApiVersion', '" . PMF_System::getApiVersion() . "')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.enableAdminLog', 'true')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.enableRewriteRules', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.enableUserTracking', 'true')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.language', '" . $language . "')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.languageDetection', 'true')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.metaDescription', 'phpMyFAQ should be the answer for all questions in life')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.metaKeywords', '')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.metaPublisher', 'John Doe')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.phpMyFAQToken', '')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.referenceURL', '')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.send2friendText', '')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.titleFAQ', 'phpMyFAQ Codename Perdita')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.urlValidateInterval', '86400')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.enableWysiwygEditor', 'true')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.templateSet', 'default')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.optionalMailAddress', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.enableGoogleTranslation', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.googleTranslationKey', '')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('main.dateFormat', 'Y-m-d H:i')";

// Records
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.numberOfRecordsPerPage', '10')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.numberOfShownNewsEntries', '3')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.defaultActivation', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.defaultAllowComments', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.enableVisibilityQuestions', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.numberOfRelatedArticles', '5')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.orderby', 'id')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.sortby', 'DESC')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.orderingPopularFaqs', 'visits')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.disableAttachments', 'true')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.maxAttachmentSize', '100000')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.attachmentsPath', 'attachments')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.attachmentsStorageType', '0')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.enableAttachmentEncryption', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('records.defaultAttachmentEncKey', '')";

// Search
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('search.useAjaxSearchOnStartpage', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('search.numberSearchTerms', '10')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('search.relevance', 'thema,content,keywords')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('search.enableRelevance', 'false')";

// Security
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('security.permLevel', '" . $permLevel . "')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('security.ipCheck', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('security.enableLoginOnly', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('security.ldapSupport', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('security.bannedIPs', '')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('security.ssoSupport', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('security.ssoLogoutRedirect', '')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('security.useSslForLogins', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('security.useSslOnly', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('security.forcePasswordUpdate', 'false')";

// Spam
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('spam.checkBannedWords', 'true')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('spam.enableCaptchaCode', 'true')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('spam.enableSafeEmail', 'true')";

// Social Networks
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('socialnetworks.enableTwitterSupport', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('socialnetworks.twitterConsumerKey', '')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('socialnetworks.twitterConsumerSecret', '')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('socialnetworks.twitterAccessTokenKey', '')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('socialnetworks.twitterAccessTokenSecret', '')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('socialnetworks.enableFacebookSupport', 'false')";

// Cache
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('cache.varnishEnable', 'false')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('cache.varnishHost', '127.0.0.1')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('cache.varnishPort', '2000')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('cache.varnishSecret', '')";
$query[] = "INSERT INTO " . $sqltblpre . "faqconfig VALUES ('cache.varnishTimeout', '500')";
