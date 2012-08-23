<?php
/**
 * Default configuration values for every phpMyFAQ instance
 *
 * PHP Version 5.3
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
$mainConfig = array(
    'main.currentVersion'                     => PMF_System::getVersion(),
    'main.currentApiVersion'                  => PMF_System::getApiVersion(),
    'main.language'                           => '__PHPMYFAQ_LANGUAGE__',
    'main.languageDetection'                  => 'true',
    'main.phpMyFAQToken'                      => md5(uniqid(rand())),
    'main.referenceURL'                       => '__PHPMYFAQ_REFERENCE_URL__',
    'main.administrationMail'                 => 'webmaster@example.org',
    'main.contactInformations'                => '',
    'main.enableAdminLog'                     => 'true',
    'main.enableRewriteRules'                 => 'false',
    'main.enableUserTracking'                 => 'true',
    'main.languageDetection'                  => 'true',
    'main.metaDescription'                    => 'phpMyFAQ should be the answer for all questions in life',
    'main.metaKeywords'                       => '',
    'main.metaPublisher'                      => '__PHPMYFAQ_PUBLISHER__',
    'main.send2friendText'                    => '',
    'main.titleFAQ'                           => 'phpMyFAQ Codename Perdita',
    'main.urlValidateInterval'                => '86400',
    'main.enableWysiwygEditor'                => 'true',
    'main.templateSet'                        => 'default',
    'main.optionalMailAddress'                => 'false',
    'main.enableGoogleTranslation'            => 'false',
    'main.googleTranslationKey'               => '',
    'main.dateFormat'                         => 'Y-m-d H:i',
    'main.maintenanceMode'                    => 'false',

    'records.numberOfRecordsPerPage'          => '10',
    'records.numberOfShownNewsEntries'        => '3',
    'records.defaultActivation'               => 'false',
    'records.defaultAllowComments'            => 'false',
    'records.enableVisibilityQuestions'       => 'false',
    'records.numberOfRelatedArticles'         => '5',
    'records.orderby'                         => 'id',
    'records.sortby'                          => 'DESC',
    'records.orderingPopularFaqs'             => 'visits',
    'records.disableAttachments'              => 'true',
    'records.maxAttachmentSize'               => '100000',
    'records.attachmentsPath'                 => 'attachments',
    'records.attachmentsStorageType'          => '0',
    'records.enableAttachmentEncryption'      => 'false',
    'records.defaultAttachmentEncKey'         => '',
    'records.enableCloseQuestion'             => 'false',
    'records.enableDeleteQuestion'            => 'false',
    'records.autosaveActive'                  => 'false',
    'records.autosaveSecs'                    => '180',

    'search.useAjaxSearchOnStartpage'         => 'false',
    'search.numberSearchTerms'                => '10',
    'search.relevance'                        => 'thema,content,keywords',
    'search.enableRelevance'                  => 'false',

    'security.permLevel'                      => 'basic',
    'security.ipCheck'                        => 'false',
    'security.enableLoginOnly'                => 'false',
    'security.ldapSupport'                    => 'false',
    'security.bannedIPs'                      => '',
    'security.ssoSupport'                     => 'false',
    'security.ssoLogoutRedirect'              => '',
    'security.useSslForLogins'                => 'false',
    'security.useSslOnly'                     => 'false',
    'security.forcePasswordUpdate'            => 'false',

    'spam.checkBannedWords'                   => 'true',
    'spam.enableCaptchaCode'                  => (extension_loaded('gd') ? 'true' : 'false'),
    'spam.enableSafeEmail'                    => 'true',

    'socialnetworks.enableTwitterSupport'     => 'false',
    'socialnetworks.twitterConsumerKey'       => '',
    'socialnetworks.twitterConsumerSecret'    => '',
    'socialnetworks.twitterAccessTokenKey'    => '',
    'socialnetworks.twitterAccessTokenSecret' => '',
    'socialnetworks.enableFacebookSupport'    => 'false',

    'cache.varnishEnable'                     => 'false',
    'cache.varnishHost'                       => '127.0.0.1',
    'cache.varnishPort'                       => '2000',
    'cache.varnishSecret'                     => '',
    'cache.varnishTimeout'                    => '500'
);
