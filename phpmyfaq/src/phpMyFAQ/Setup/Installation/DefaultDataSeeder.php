<?php

/**
 * Holds default configuration, permissions, and form inputs for installation.
 *
 * Provides data arrays previously embedded in the monolithic Installer class,
 * plus convenience methods for recording seed operations.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-31
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Installation;

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\System;

class DefaultDataSeeder
{
    /**
     * Configuration array.
     *
     * @var array<string, string|null>
     */
    private array $mainConfig;

    /**
     * Array with user rights.
     * @var array<array<string, string>>
     */
    private readonly array $mainRights;

    /**
     * Array with form inputs.
     * @var array<array<string, int|string>>
     */
    private readonly array $formInputs;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->mainConfig = self::buildDefaultConfig();
        $this->mainRights = self::buildDefaultRights();
        $this->formInputs = self::buildDefaultFormInputs();
    }

    /**
     * Returns the configuration array with dynamic values applied.
     *
     * @return array<string, string|null>
     */
    public function getMainConfig(): array
    {
        return $this->mainConfig;
    }

    /**
     * Applies personal settings to the config.
     */
    public function applyPersonalSettings(string $realname, string $email, string $language, string $permLevel): void
    {
        $this->mainConfig['main.metaPublisher'] = $realname;
        $this->mainConfig['main.administrationMail'] = $email;
        $this->mainConfig['main.language'] = $language;
        $this->mainConfig['security.permLevel'] = $permLevel;
    }

    /**
     * Seeds all configuration entries into the database.
     */
    public function seedConfig(Configuration $configuration): void
    {
        foreach ($this->mainConfig as $name => $value) {
            $configuration->add($name, $value);
        }
    }

    /**
     * Returns the permissions array.
     *
     * @return array<array<string, string>>
     */
    public function getMainRights(): array
    {
        return $this->mainRights;
    }

    /**
     * Returns the form inputs array.
     *
     * @return array<array<string, int|string>>
     */
    public function getFormInputs(): array
    {
        return $this->formInputs;
    }

    /**
     * @return array<string, string|null>
     * @throws \Exception
     */
    private static function buildDefaultConfig(): array
    {
        $config = [
            'main.currentVersion' => null,
            'main.currentApiVersion' => null,
            'main.language' => '__PHPMYFAQ_LANGUAGE__',
            'main.languageDetection' => 'true',
            'main.phpMyFAQToken' => null,
            'main.referenceURL' => '__PHPMYFAQ_REFERENCE_URL__',
            'main.administrationMail' => 'webmaster@example.org',
            'main.contactInformation' => '',
            'main.enableAdminLog' => 'true',
            'main.enableUserTracking' => 'true',
            'main.metaDescription' => 'phpMyFAQ should be the answer for all questions in life',
            'main.metaPublisher' => '__PHPMYFAQ_PUBLISHER__',
            'main.titleFAQ' => 'phpMyFAQ Codename Palaimon',
            'main.enableWysiwygEditor' => 'true',
            'main.enableWysiwygEditorFrontend' => 'false',
            'main.enableMarkdownEditor' => 'false',
            'main.enableCommentEditor' => 'false',
            'main.dateFormat' => 'Y-m-d H:i',
            'main.maintenanceMode' => 'false',
            'main.enableGravatarSupport' => 'false',
            'main.customPdfHeader' => '',
            'main.customPdfFooter' => '',
            'main.enableSmartAnswering' => 'true',
            'main.enableCategoryRestrictions' => 'true',
            'main.enableSendToFriend' => 'true',
            'main.privacyURL' => '',
            'main.termsURL' => '',
            'main.imprintURL' => '',
            'main.cookiePolicyURL' => '',
            'main.accessibilityStatementURL' => '',
            'main.enableAutoUpdateHint' => 'true',
            'main.enableAskQuestions' => 'false',
            'main.enableNotifications' => 'false',
            'main.botIgnoreList' =>
                'nustcrape,webpost,GoogleBot,msnbot,crawler,scooter,bravobrian,archiver,'
                    . 'w3c,controler,wget,bot,spider,Yahoo! Slurp,htdig,gsa-crawler,AirControler,Uptime-Kuma,facebookcatalog/1.0,'
                    . 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php),facebookexternalhit/1.1',
            'records.numberOfRecordsPerPage' => '10',
            'records.numberOfShownNewsEntries' => '3',
            'records.defaultActivation' => 'false',
            'records.defaultAllowComments' => 'false',
            'records.enableVisibilityQuestions' => 'false',
            'records.numberOfRelatedArticles' => '5',
            'records.orderby' => 'id',
            'records.sortby' => 'DESC',
            'records.orderingPopularFaqs' => 'visits',
            'records.disableAttachments' => 'true',
            'records.maxAttachmentSize' => '100000',
            'records.attachmentsPath' => 'content/user/attachments',
            'records.attachmentsStorageType' => '0',
            'records.enableAttachmentEncryption' => 'false',
            'records.defaultAttachmentEncKey' => '',
            'records.enableCloseQuestion' => 'false',
            'records.enableDeleteQuestion' => 'false',
            'records.randomSort' => 'false',
            'records.allowCommentsForGuests' => 'true',
            'records.allowQuestionsForGuests' => 'true',
            'records.allowNewFaqsForGuests' => 'true',
            'records.hideEmptyCategories' => 'false',
            'records.allowDownloadsForGuests' => 'false',
            'records.numberMaxStoredRevisions' => '10',
            'records.enableAutoRevisions' => 'false',
            'records.orderStickyFaqsCustom' => 'false',
            'records.allowedMediaHosts' => 'www.youtube.com',
            'search.numberSearchTerms' => '10',
            'search.relevance' => 'thema,content,keywords',
            'search.enableRelevance' => 'false',
            'search.enableHighlighting' => 'true',
            'search.searchForSolutionId' => 'true',
            'search.popularSearchTimeWindow' => '180',
            'search.enableElasticsearch' => 'false',
            'search.enableOpenSearch' => 'false',
            'security.permLevel' => 'basic',
            'security.ipCheck' => 'false',
            'security.enableLoginOnly' => 'false',
            'security.bannedIPs' => '',
            'security.ssoSupport' => 'false',
            'security.ssoLogoutRedirect' => '',
            'security.useSslForLogins' => 'false',
            'security.useSslOnly' => 'false',
            'security.forcePasswordUpdate' => 'false',
            'security.enableRegistration' => 'true',
            'security.domainWhiteListForRegistrations' => '',
            'security.enableSignInWithMicrosoft' => 'false',
            'security.enableGoogleReCaptchaV2' => 'false',
            'security.googleReCaptchaV2SiteKey' => '',
            'security.googleReCaptchaV2SecretKey' => '',
            'security.loginWithEmailAddress' => 'false',
            'security.enableWebAuthnSupport' => 'false',
            'security.enableAdminSessionTimeoutCounter' => 'true',
            'spam.checkBannedWords' => 'true',
            'spam.enableCaptchaCode' => null,
            'spam.enableSafeEmail' => 'true',
            'spam.manualActivation' => 'true',
            'spam.mailAddressInExport' => 'true',
            'seo.title' => 'phpMyFAQ Codename Porus',
            'seo.description' => 'phpMyFAQ should be the answer for all questions in life',
            'seo.enableXMLSitemap' => 'true',
            'seo.enableRichSnippets' => 'false',
            'seo.metaTagsHome' => 'index, follow',
            'seo.metaTagsFaqs' => 'index, follow',
            'seo.metaTagsCategories' => 'index, follow',
            'seo.metaTagsPages' => 'index, follow',
            'seo.metaTagsAdmin' => 'noindex, nofollow',
            'seo.contentRobotsText' => 'User-agent: *\nDisallow: /admin/\nSitemap: /sitemap.xml',
            'seo.contentLlmsText' =>
                "# phpMyFAQ LLMs.txt\n\n"
                    . "This file provides information about the AI/LLM training data availability for this FAQ system.\n\n"
                    . "Contact: Please see the contact information on the main website.\n\n"
                    . "The FAQ content in this system is available for LLM training purposes.\n"
                    . "Please respect the licensing terms and usage guidelines of the content.\n\n"
                    . 'For more information about this FAQ system, visit: https://www.phpmyfaq.de',
            'mail.noReplySenderAddress' => '',
            'mail.remoteSMTP' => 'false',
            'mail.remoteSMTPServer' => '',
            'mail.remoteSMTPUsername' => '',
            'mail.remoteSMTPPassword' => '',
            'mail.remoteSMTPPort' => '25',
            'mail.remoteSMTPDisableTLSPeerVerification' => 'false',
            'ldap.ldapSupport' => 'false',
            'ldap.ldap_mapping.name' => 'cn',
            'ldap.ldap_mapping.username' => 'samAccountName',
            'ldap.ldap_mapping.mail' => 'mail',
            'ldap.ldap_mapping.memberOf' => '',
            'ldap.ldap_use_domain_prefix' => 'true',
            'ldap.ldap_options.LDAP_OPT_PROTOCOL_VERSION' => '3',
            'ldap.ldap_options.LDAP_OPT_REFERRALS' => '0',
            'ldap.ldap_use_memberOf' => 'false',
            'ldap.ldap_use_sasl' => 'false',
            'ldap.ldap_use_multiple_servers' => 'false',
            'ldap.ldap_use_anonymous_login' => 'false',
            'ldap.ldap_use_dynamic_login' => 'false',
            'ldap.ldap_dynamic_login_attribute' => 'uid',
            'ldap.ldap_use_group_restriction' => 'false',
            'ldap.ldap_group_allowed_groups' => '',
            'ldap.ldap_group_auto_assign' => 'false',
            'ldap.ldap_group_mapping' => '',
            'api.enableAccess' => 'true',
            'api.apiClientToken' => '',
            'api.onlyActiveFaqs' => 'true',
            'api.onlyActiveCategories' => 'true',
            'api.rateLimit.requests' => '100',
            'api.rateLimit.interval' => '3600',
            'translation.provider' => 'none',
            'translation.googleApiKey' => '',
            'translation.deeplApiKey' => '',
            'translation.deeplUseFreeApi' => 'true',
            'translation.azureKey' => '',
            'translation.azureRegion' => '',
            'translation.amazonAccessKeyId' => '',
            'translation.amazonSecretAccessKey' => '',
            'translation.amazonRegion' => 'us-east-1',
            'translation.libreTranslateUrl' => 'https://libretranslate.com',
            'translation.libreTranslateApiKey' => '',
            'routing.useAttributesOnly' => 'false',
            'routing.cache.enabled' => 'false',
            'routing.cache.dir' => './cache',
            'api.onlyPublicQuestions' => 'true',
            'api.ignoreOrphanedFaqs' => 'true',
            'queue.transport' => 'database',
            'upgrade.dateLastChecked' => '',
            'upgrade.lastDownloadedPackage' => '',
            'upgrade.onlineUpdateEnabled' => 'false',
            'upgrade.releaseEnvironment' => '__PHPMYFAQ_RELEASE__',
            'layout.templateSet' => 'default',
            'layout.enablePrivacyLink' => 'true',
            'layout.enableCookieConsent' => 'true',
            'layout.contactInformationHTML' => 'false',
            'layout.customCss' => '',
            'push.enableWebPush' => 'false',
            'push.vapidPublicKey' => '',
            'push.vapidPrivateKey' => '',
            'push.vapidSubject' => '',
        ];

        // Apply dynamic values
        $config['main.currentVersion'] = System::getVersion();
        $config['main.currentApiVersion'] = System::getApiVersion();
        $config['main.phpMyFAQToken'] = bin2hex(random_bytes(16));
        $config['spam.enableCaptchaCode'] = extension_loaded('gd') ? 'true' : 'false';
        $config['upgrade.releaseEnvironment'] = System::isDevelopmentVersion()
            ? ReleaseType::DEVELOPMENT->value
            : ReleaseType::STABLE->value;

        return $config;
    }

    /**
     * @return array<array<string, string>>
     */
    private static function buildDefaultRights(): array
    {
        return [
            ['name' => PermissionType::USER_ADD->value, 'description' => 'Right to add user accounts'],
            ['name' => PermissionType::USER_EDIT->value, 'description' => 'Right to edit user accounts'],
            ['name' => PermissionType::USER_DELETE->value, 'description' => 'Right to delete user accounts'],
            ['name' => PermissionType::FAQ_ADD->value, 'description' => 'Right to add faq entries'],
            ['name' => PermissionType::FAQ_EDIT->value, 'description' => 'Right to edit faq entries'],
            ['name' => PermissionType::FAQ_DELETE->value, 'description' => 'Right to delete faq entries'],
            ['name' => PermissionType::STATISTICS_VIEWLOGS->value, 'description' => 'Right to view logfiles'],
            ['name' => PermissionType::STATISTICS_ADMINLOG->value, 'description' => 'Right to view admin log'],
            ['name' => PermissionType::COMMENT_DELETE->value, 'description' => 'Right to delete comments'],
            ['name' => PermissionType::NEWS_ADD->value, 'description' => 'Right to add news'],
            ['name' => PermissionType::NEWS_EDIT->value, 'description' => 'Right to edit news'],
            ['name' => PermissionType::NEWS_DELETE->value, 'description' => 'Right to delete news'],
            ['name' => PermissionType::PAGE_ADD->value, 'description' => 'Right to add custom pages'],
            ['name' => PermissionType::PAGE_EDIT->value, 'description' => 'Right to edit custom pages'],
            ['name' => PermissionType::PAGE_DELETE->value, 'description' => 'Right to delete custom pages'],
            ['name' => PermissionType::CATEGORY_ADD->value, 'description' => 'Right to add categories'],
            ['name' => PermissionType::CATEGORY_EDIT->value, 'description' => 'Right to edit categories'],
            ['name' => PermissionType::CATEGORY_DELETE->value, 'description' => 'Right to delete categories'],
            ['name' => PermissionType::PASSWORD_CHANGE->value, 'description' => 'Right to change passwords'],
            ['name' => PermissionType::CONFIGURATION_EDIT->value, 'description' => 'Right to edit configuration'],
            [
                'name' => PermissionType::VIEW_ADMIN_LINK->value,
                'description' => 'Right to see the link to the admin section',
            ],
            ['name' => PermissionType::BACKUP->value, 'description' => 'Right to save backups'],
            ['name' => PermissionType::RESTORE->value, 'description' => 'Right to load backups'],
            ['name' => PermissionType::QUESTION_DELETE->value, 'description' => 'Right to delete questions'],
            ['name' => PermissionType::GLOSSARY_ADD->value, 'description' => 'Right to add glossary entries'],
            ['name' => PermissionType::GLOSSARY_EDIT->value, 'description' => 'Right to edit glossary entries'],
            ['name' => PermissionType::GLOSSARY_DELETE->value, 'description' => 'Right to delete glossary entries'],
            ['name' => PermissionType::REVISION_UPDATE->value, 'description' => 'Right to edit revisions'],
            ['name' => PermissionType::GROUP_ADD->value, 'description' => 'Right to add group accounts'],
            ['name' => PermissionType::GROUP_EDIT->value, 'description' => 'Right to edit group accounts'],
            ['name' => PermissionType::GROUP_DELETE->value, 'description' => 'Right to delete group accounts'],
            ['name' => PermissionType::FAQ_APPROVE->value, 'description' => 'Right to approve FAQs'],
            ['name' => PermissionType::ATTACHMENT_ADD->value, 'description' => 'Right to add attachments'],
            ['name' => PermissionType::ATTACHMENT_EDIT->value, 'description' => 'Right to edit attachments'],
            ['name' => PermissionType::ATTACHMENT_DELETE->value, 'description' => 'Right to delete attachments'],
            ['name' => PermissionType::ATTACHMENT_DOWNLOAD->value, 'description' => 'Right to download attachments'],
            ['name' => PermissionType::REPORTS->value, 'description' => 'Right to generate reports'],
            ['name' => PermissionType::FAQ_ADD->value, 'description' => 'Right to add FAQs in frontend'],
            ['name' => PermissionType::QUESTION_ADD->value, 'description' => 'Right to add questions in frontend'],
            ['name' => PermissionType::COMMENT_ADD->value, 'description' => 'Right to add comments in frontend'],
            ['name' => PermissionType::INSTANCE_EDIT->value, 'description' => 'Right to edit multi-site instances'],
            ['name' => PermissionType::INSTANCE_ADD->value, 'description' => 'Right to add multi-site instances'],
            ['name' => PermissionType::INSTANCE_DELETE->value, 'description' => 'Right to delete multi-site instances'],
            ['name' => PermissionType::EXPORT->value, 'description' => 'Right to export the complete FAQ'],
            ['name' => PermissionType::FAQS_VIEW->value, 'description' => 'Right to view FAQs'],
            ['name' => PermissionType::CATEGORIES_VIEW->value, 'description' => 'Right to view categories'],
            ['name' => PermissionType::NEWS_VIEW->value, 'description' => 'Right to view news'],
            ['name' => PermissionType::GROUPS_ADMINISTRATE->value, 'description' => 'Right to administrate groups'],
            ['name' => PermissionType::FORMS_EDIT->value, 'description' => 'Right to edit forms'],
            ['name' => PermissionType::FAQ_TRANSLATE->value, 'description' => 'Right to translate FAQs'],
        ];
    }

    /**
     * @return array<array<string, int|string>>
     */
    private static function buildDefaultFormInputs(): array
    {
        return [
            // Ask Question inputs
            [
                'form_id' => 1,
                'input_id' => 1,
                'input_type' => 'title',
                'input_label' => 'msgQuestion',
                'input_active' => 1,
                'input_required' => -1,
                'input_lang' => 'default',
            ],
            [
                'form_id' => 1,
                'input_id' => 2,
                'input_type' => 'message',
                'input_label' => 'msgNewQuestion',
                'input_active' => 1,
                'input_required' => -1,
                'input_lang' => 'default',
            ],
            [
                'form_id' => 1,
                'input_id' => 3,
                'input_type' => 'text',
                'input_label' => 'msgNewContentName',
                'input_active' => 1,
                'input_required' => 1,
                'input_lang' => 'default',
            ],
            [
                'form_id' => 1,
                'input_id' => 4,
                'input_type' => 'email',
                'input_label' => 'msgNewContentMail',
                'input_active' => 1,
                'input_required' => 1,
                'input_lang' => 'default',
            ],
            [
                'form_id' => 1,
                'input_id' => 5,
                'input_type' => 'select',
                'input_label' => 'msgNewContentCategory',
                'input_active' => 1,
                'input_required' => 1,
                'input_lang' => 'default',
            ],
            [
                'form_id' => 1,
                'input_id' => 6,
                'input_type' => 'textarea',
                'input_label' => 'msgAskYourQuestion',
                'input_active' => -1,
                'input_required' => -1,
                'input_lang' => 'default',
            ],
            // Add New FAQ inputs
            [
                'form_id' => 2,
                'input_id' => 1,
                'input_type' => 'title',
                'input_label' => 'msgNewContentHeader',
                'input_active' => 1,
                'input_required' => -1,
                'input_lang' => 'default',
            ],
            [
                'form_id' => 2,
                'input_id' => 2,
                'input_type' => 'message',
                'input_label' => 'msgNewContentAddon',
                'input_active' => 1,
                'input_required' => -1,
                'input_lang' => 'default',
            ],
            [
                'form_id' => 2,
                'input_id' => 3,
                'input_type' => 'text',
                'input_label' => 'msgNewContentName',
                'input_active' => 1,
                'input_required' => 1,
                'input_lang' => 'default',
            ],
            [
                'form_id' => 2,
                'input_id' => 4,
                'input_type' => 'email',
                'input_label' => 'msgNewContentMail',
                'input_active' => 1,
                'input_required' => 1,
                'input_lang' => 'default',
            ],
            [
                'form_id' => 2,
                'input_id' => 5,
                'input_type' => 'select',
                'input_label' => 'msgNewContentCategory',
                'input_active' => 1,
                'input_required' => 1,
                'input_lang' => 'default',
            ],
            [
                'form_id' => 2,
                'input_id' => 6,
                'input_type' => 'textarea',
                'input_label' => 'msgNewContentTheme',
                'input_active' => -1,
                'input_required' => -1,
                'input_lang' => 'default',
            ],
            [
                'form_id' => 2,
                'input_id' => 7,
                'input_type' => 'textarea',
                'input_label' => 'msgNewContentArticle',
                'input_active' => 1,
                'input_required' => 1,
                'input_lang' => 'default',
            ],
            [
                'form_id' => 2,
                'input_id' => 8,
                'input_type' => 'text',
                'input_label' => 'msgNewContentKeywords',
                'input_active' => 1,
                'input_required' => 1,
                'input_lang' => 'default',
            ],
            [
                'form_id' => 2,
                'input_id' => 9,
                'input_type' => 'title',
                'input_label' => 'msgNewContentLink',
                'input_active' => 1,
                'input_required' => 1,
                'input_lang' => 'default',
            ],
        ];
    }
}
