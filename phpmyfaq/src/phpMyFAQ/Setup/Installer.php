<?php

/**
 * The Installer class installs phpMyFAQ. Classy.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Florian Anderiasch <florian@phpmyfaq.net>
 * @copyright 2012-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-08-27
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use Composer\Autoload\ClassLoader;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use OpenSearch\SymfonyClientFactory;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Configuration\OpenSearchConfiguration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Entity\InstanceEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\Filter;
use phpMyFAQ\Forms;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Database as InstanceDatabase;
use phpMyFAQ\Instance\Database\Stopwords;
use phpMyFAQ\Instance\Main;
use phpMyFAQ\Instance\Search\Elasticsearch;
use phpMyFAQ\Instance\Search\OpenSearch;
use phpMyFAQ\Instance\Setup;
use phpMyFAQ\Ldap;
use phpMyFAQ\Link;
use phpMyFAQ\System;
use phpMyFAQ\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Installer
 *
 * @package phpMyFAQ
 */
class Installer extends Setup
{
    /**
     * Array with user rights.
     * @var array<array<string, string>>
     */
    protected array $mainRights = [
        [
            'name' => PermissionType::USER_ADD->value,
            'description' => 'Right to add user accounts',
        ],
        [
            'name' => PermissionType::USER_EDIT->value,
            'description' => 'Right to edit user accounts',
        ],
        [
            'name' => PermissionType::USER_DELETE->value,
            'description' => 'Right to delete user accounts',
        ],
        [
            'name' => PermissionType::FAQ_ADD->value,
            'description' => 'Right to add faq entries',
        ],
        [
            'name' => PermissionType::FAQ_EDIT->value,
            'description' => 'Right to edit faq entries',
        ],
        [
            'name' => PermissionType::FAQ_DELETE->value,
            'description' => 'Right to delete faq entries',
        ],
        [
            'name' => PermissionType::STATISTICS_VIEWLOGS->value,
            'description' => 'Right to view logfiles',
        ],
        [
            'name' => PermissionType::STATISTICS_ADMINLOG->value,
            'description' => 'Right to view admin log',
        ],
        [
            'name' => PermissionType::COMMENT_DELETE->value,
            'description' => 'Right to delete comments',
        ],
        [
            'name' => PermissionType::NEWS_ADD->value,
            'description' => 'Right to add news',
        ],
        [
            'name' => PermissionType::NEWS_EDIT->value,
            'description' => 'Right to edit news',
        ],
        [
            'name' => PermissionType::NEWS_DELETE->value,
            'description' => 'Right to delete news',
        ],
        [
            'name' => PermissionType::PAGE_ADD->value,
            'description' => 'Right to add custom pages',
        ],
        [
            'name' => PermissionType::PAGE_EDIT->value,
            'description' => 'Right to edit custom pages',
        ],
        [
            'name' => PermissionType::PAGE_DELETE->value,
            'description' => 'Right to delete custom pages',
        ],
        [
            'name' => PermissionType::CATEGORY_ADD->value,
            'description' => 'Right to add categories',
        ],
        [
            'name' => PermissionType::CATEGORY_EDIT->value,
            'description' => 'Right to edit categories',
        ],
        [
            'name' => PermissionType::CATEGORY_DELETE->value,
            'description' => 'Right to delete categories',
        ],
        [
            'name' => PermissionType::PASSWORD_CHANGE->value,
            'description' => 'Right to change passwords',
        ],
        [
            'name' => PermissionType::CONFIGURATION_EDIT->value,
            'description' => 'Right to edit configuration',
        ],
        [
            'name' => PermissionType::VIEW_ADMIN_LINK->value,
            'description' => 'Right to see the link to the admin section',
        ],
        [
            'name' => PermissionType::BACKUP->value,
            'description' => 'Right to save backups',
        ],
        [
            'name' => PermissionType::RESTORE->value,
            'description' => 'Right to load backups',
        ],
        [
            'name' => PermissionType::QUESTION_DELETE->value,
            'description' => 'Right to delete questions',
        ],
        [
            'name' => PermissionType::GLOSSARY_ADD->value,
            'description' => 'Right to add glossary entries',
        ],
        [
            'name' => PermissionType::GLOSSARY_EDIT->value,
            'description' => 'Right to edit glossary entries',
        ],
        [
            'name' => PermissionType::GLOSSARY_DELETE->value,
            'description' => 'Right to delete glossary entries',
        ],
        [
            'name' => PermissionType::REVISION_UPDATE->value,
            'description' => 'Right to edit revisions',
        ],
        [
            'name' => PermissionType::GROUP_ADD->value,
            'description' => 'Right to add group accounts',
        ],
        [
            'name' => PermissionType::GROUP_EDIT->value,
            'description' => 'Right to edit group accounts',
        ],
        [
            'name' => PermissionType::GROUP_DELETE->value,
            'description' => 'Right to delete group accounts',
        ],
        [
            'name' => PermissionType::FAQ_APPROVE->value,
            'description' => 'Right to approve FAQs',
        ],
        [
            'name' => PermissionType::ATTACHMENT_ADD->value,
            'description' => 'Right to add attachments',
        ],
        [
            'name' => PermissionType::ATTACHMENT_EDIT->value,
            'description' => 'Right to edit attachments',
        ],
        [
            'name' => PermissionType::ATTACHMENT_DELETE->value,
            'description' => 'Right to delete attachments',
        ],
        [
            'name' => PermissionType::ATTACHMENT_DOWNLOAD->value,
            'description' => 'Right to download attachments',
        ],
        [
            'name' => PermissionType::REPORTS->value,
            'description' => 'Right to generate reports',
        ],
        [
            'name' => PermissionType::FAQ_ADD->value,
            'description' => 'Right to add FAQs in frontend',
        ],
        [
            'name' => PermissionType::QUESTION_ADD->value,
            'description' => 'Right to add questions in frontend',
        ],
        [
            'name' => PermissionType::COMMENT_ADD->value,
            'description' => 'Right to add comments in frontend',
        ],
        [
            'name' => PermissionType::INSTANCE_EDIT->value,
            'description' => 'Right to edit multi-site instances',
        ],
        [
            'name' => PermissionType::INSTANCE_ADD->value,
            'description' => 'Right to add multi-site instances',
        ],
        [
            'name' => PermissionType::INSTANCE_DELETE->value,
            'description' => 'Right to delete multi-site instances',
        ],
        [
            'name' => PermissionType::EXPORT->value,
            'description' => 'Right to export the complete FAQ',
        ],
        [
            'name' => PermissionType::FAQS_VIEW->value,
            'description' => 'Right to view FAQs',
        ],
        [
            'name' => PermissionType::CATEGORIES_VIEW->value,
            'description' => 'Right to view categories',
        ],
        [
            'name' => PermissionType::NEWS_VIEW->value,
            'description' => 'Right to view news',
        ],
        [
            'name' => PermissionType::GROUPS_ADMINISTRATE->value,
            'description' => 'Right to administrate groups',
        ],
        [
            'name' => PermissionType::FORMS_EDIT->value,
            'description' => 'Right to edit forms',
        ],
        [
            'name' => PermissionType::FAQ_TRANSLATE->value,
            'description' => 'Right to translate FAQs',
        ],
    ];

    /**
     * Configuration array.
     *
     * @var array<string, string|null>
     */
    protected array $mainConfig = [
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
        'api.onlyPublicQuestions' => 'true',
        'api.ignoreOrphanedFaqs' => 'true',
        'upgrade.dateLastChecked' => '',
        'upgrade.lastDownloadedPackage' => '',
        'upgrade.onlineUpdateEnabled' => 'false',
        'upgrade.releaseEnvironment' => '__PHPMYFAQ_RELEASE__',
        'layout.templateSet' => 'default',
        'layout.enablePrivacyLink' => 'true',
        'layout.enableCookieConsent' => 'true',
        'layout.contactInformationHTML' => 'false',
        'layout.customCss' => '',
    ];

    /**
     * Array with form inputs
     * @var array<array<string, int|string>>
     */
    public array $formInputs = [
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

    /**
     * Constructor.
     *
     * @throws \Exception
     */
    public function __construct(
        private readonly System $system,
    ) {
        parent::__construct();

        $dynMainConfig = [
            'main.currentVersion' => System::getVersion(),
            'main.currentApiVersion' => System::getApiVersion(),
            'main.phpMyFAQToken' => bin2hex(random_bytes(16)),
            'spam.enableCaptchaCode' => extension_loaded('gd') ? 'true' : 'false',
            'upgrade.releaseEnvironment' => System::isDevelopmentVersion()
                ? ReleaseType::DEVELOPMENT->value
                : ReleaseType::STABLE->value,
        ];

        $this->mainConfig = array_merge($this->mainConfig, $dynMainConfig);
    }

    /**
     * Removes the database.php and the ldap.php if an installation failed.
     */
    public static function cleanFailedInstallationFiles(): void
    {
        if (file_exists(PMF_ROOT_DIR . '/content/core/config/database.php')) {
            unlink(PMF_ROOT_DIR . '/content/core/config/database.php');
        }

        if (file_exists(PMF_ROOT_DIR . '/content/core/config/ldap.php')) {
            unlink(PMF_ROOT_DIR . '/content/core/config/ldap.php');
        }
    }

    /**
     * Check the necessary stuff and throw an exception if something is wrong.
     * @throws Exception
     */
    public function checkBasicStuff(): void
    {
        if (!$this->checkMinimumPhpVersion()) {
            throw new Exception(sprintf('Sorry, but you need PHP %s or later!', System::VERSION_MINIMUM_PHP));
        }

        if (!function_exists('date_default_timezone_set')) {
            throw new Exception('Sorry, but setting a default timezone does not work in your environment!');
        }

        if (!$this->system->checkDatabase()) {
            throw new Exception('No supported database detected!');
        }

        if (!$this->system->checkRequiredExtensions()) {
            throw new Exception(sprintf('Some required PHP extensions are missing: %s', implode(
                ', ',
                $this->system->getMissingExtensions(),
            )));
        }

        if (!$this->system->checkInstallation()) {
            throw new Exception(
                'Looks like phpMyFAQ is already installed! Please use the <a href="../update">update</a>.',
            );
        }
    }

    /**
     * Checks if the file permissions are okay.
     */
    public function checkFilesystemPermissions(): ?string
    {
        $instanceSetup = new Setup();
        $instanceSetup->setRootDir(PMF_ROOT_DIR);

        $dirs = [
            '/content/core/config',
            '/content/core/data',
            '/content/core/logs',
            '/content/user/images',
            '/content/user/attachments',
        ];
        $failedDirs = $instanceSetup->checkDirs($dirs);
        $numDirs = count($failedDirs);

        $hints = '';
        if (1 <= $numDirs) {
            $hints .= sprintf(
                '<p class="alert alert-danger">The following %s could not be created or %s not writable:</p><ul>',
                1 < $numDirs ? 'directories' : 'directory',
                1 < $numDirs ? 'are' : 'is',
            );
            foreach ($failedDirs as $failedDir) {
                $hints .= "<li>{$failedDir}</li>\n";
            }

            return $hints
            . sprintf(
                '</ul><p class="alert alert-danger">Please create %s manually and/or change access to chmod 775 (or '
                . 'greater if necessary).</p>',
                1 < $numDirs ? 'them' : 'it',
            );
        }

        return null;
    }

    /**
     * Checks some non-critical settings and print some hints.
     *
     * @return string[]
     */
    public function checkNoncriticalSettings(): array
    {
        $hints = [];
        if (!$this->system->getHttpsStatus()) {
            $hints[] =
                '<p class="alert alert-warning">HTTPS support is not enabled in your web server.'
                . ' To ensure the security of your data and protect against potential vulnerabilities,'
                . ' we highly recommend enabling HTTPS. Please configure your web server to support HTTPS as soon as'
                . ' possible.</p>';
        }

        if (!extension_loaded('gd')) {
            $hints[] =
                '<p class="alert alert-warning">You don\'t have GD support enabled in your PHP installation. '
                . "Please enable GD support in your php.ini file otherwise you can't use Captchas for spam protection."
                . '</p>';
        }

        if (!function_exists('imagettftext')) {
            $hints[] =
                '<p class="alert alert-warning">You don\'t have Freetype support enabled in the GD extension '
                . ' of your PHP installation. Please enable Freetype support in GD extension otherwise the Captchas '
                . 'for spam protection will be quite easy to break.</p>';
        }

        if (!extension_loaded('curl') || !extension_loaded('openssl')) {
            $hints[] =
                '<p class="alert alert-warning">You don\'t have cURL and/or OpenSSL support enabled in your '
                . 'PHP installation. Please enable cURL and/or OpenSSL support in your php.ini file otherwise you '
                . " can't use Elasticsearch.</p>";
        }

        if (!extension_loaded('fileinfo')) {
            $hints[] =
                '<p class="alert alert-warning">You don\'t have Fileinfo support enabled in your PHP '
                . "installation. Please enable Fileinfo support in your php.ini file otherwise you can't use our "
                . 'backup/restore functionality.</p>';
        }

        if (!extension_loaded('sodium')) {
            $hints[] =
                '<p class="alert alert-warning">You don\'t have Sodium support enabled in your PHP '
                . "installation. Please enable Sodium support in your php.ini file otherwise you can't use our "
                . 'backup/restore functionality.</p>';
        }

        return $hints;
    }

    /**
     * @throws Exception
     */
    public function checkInitialRewriteBasePath(Request $request): bool
    {
        $basePath = $request->getBasePath();
        $basePath = rtrim($basePath, 'setup');

        $htaccessPath = PMF_ROOT_DIR . '/.htaccess';

        $htaccessUpdater = new HtaccessUpdater();
        return $htaccessUpdater->updateRewriteBase($htaccessPath, $basePath);
    }

    /**
     * Starts the installation.
     *
     * @throws Exception|AuthenticationException
     * @throws \Exception
     */
    public function startInstall(?array $setup = null): void
    {
        $ldapSetup = [];
        $query = [];
        $uninstall = [];
        $dbSetup = [];

        // Check table prefix
        $dbSetup['dbPrefix'] = Filter::filterInput(INPUT_POST, 'sqltblpre', FILTER_SANITIZE_SPECIAL_CHARS, '');
        if ('' !== $dbSetup['dbPrefix']) {
            Database::setTablePrefix($dbSetup['dbPrefix']);
        }

        // Check database entries
        if (!isset($setup['dbType'])) {
            $dbSetup['dbType'] = Filter::filterInput(INPUT_POST, 'sql_type', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $dbSetup['dbType'] = $setup['dbType'];
        }

        if (!is_null($dbSetup['dbType'])) {
            $dbSetup['dbType'] = trim((string) $dbSetup['dbType']);
            if (str_starts_with($dbSetup['dbType'], 'pdo_')) {
                $dataBaseFile = 'Pdo' . ucfirst(substr($dbSetup['dbType'], 4));
            } else {
                $dataBaseFile = ucfirst($dbSetup['dbType']);
            }

            if (!file_exists(PMF_SRC_DIR . '/phpMyFAQ/Instance/Database/' . $dataBaseFile . '.php')) {
                throw new Exception(sprintf('Installation Error: Invalid server type "%s"', $dbSetup['dbType']));
            }
        } else {
            throw new Exception('Installation Error: Please select a database type.');
        }

        $dbSetup['dbServer'] = Filter::filterInput(INPUT_POST, 'sql_server', FILTER_SANITIZE_SPECIAL_CHARS, '');
        if (is_null($dbSetup['dbServer']) && !System::isSqlite($dbSetup['dbType'])) {
            throw new Exception('Installation Error: Please add a database server.');
        }

        // Check database port
        if (!isset($setup['dbType'])) {
            $dbSetup['dbPort'] = Filter::filterInput(INPUT_POST, 'sql_port', FILTER_VALIDATE_INT);
        } else {
            $dbSetup['dbPort'] = $setup['dbPort'];
        }

        if (is_null($dbSetup['dbPort']) && !System::isSqlite($dbSetup['dbType'])) {
            throw new Exception('Installation Error: Please add a valid database port.');
        }

        $dbSetup['dbUser'] = Filter::filterInput(INPUT_POST, 'sql_user', FILTER_SANITIZE_SPECIAL_CHARS, '');
        if (is_null($dbSetup['dbUser']) && !System::isSqlite($dbSetup['dbType'])) {
            throw new Exception('Installation Error: Please add a database username.');
        }

        $dbSetup['dbPassword'] = Filter::filterInput(INPUT_POST, 'sql_password', FILTER_SANITIZE_SPECIAL_CHARS, '');
        if (is_null($dbSetup['dbPassword']) && !System::isSqlite($dbSetup['dbType'])) {
            // A password can be empty...
            $dbSetup['dbPassword'] = '';
        }

        // Check the database name
        if (!isset($setup['dbType'])) {
            $dbSetup['dbDatabaseName'] = Filter::filterInput(INPUT_POST, 'sql_db', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $dbSetup['dbDatabaseName'] = $setup['dbDatabaseName'];
        }

        if (is_null($dbSetup['dbDatabaseName']) && !System::isSqlite($dbSetup['dbType'])) {
            throw new Exception('Installation Error: Please add a database name.');
        }

        if (System::isSqlite($dbSetup['dbType'])) {
            $dbSetup['dbServer'] = Filter::filterInput(
                INPUT_POST,
                'sql_sqlitefile',
                FILTER_SANITIZE_SPECIAL_CHARS,
                $setup['dbServer'] ?? null,
            );
            if (is_null($dbSetup['dbServer'])) {
                throw new Exception('Installation Error: Please add a SQLite database filename.');
            }
        }

        // check database connection
        Database::setTablePrefix($dbSetup['dbPrefix']);
        $db = Database::factory($dbSetup['dbType']);
        $db->connect(
            $dbSetup['dbServer'],
            $dbSetup['dbUser'],
            $dbSetup['dbPassword'],
            $dbSetup['dbDatabaseName'],
            $dbSetup['dbPort'],
        );

        $configuration = new Configuration($db);

        // Check LDAP if enabled

        $ldapEnabled = Filter::filterInput(INPUT_POST, 'ldap_enabled', FILTER_SANITIZE_SPECIAL_CHARS);
        if (extension_loaded('ldap') && !is_null($ldapEnabled)) {
            // check LDAP entries
            $ldapSetup['ldapServer'] = Filter::filterInput(INPUT_POST, 'ldap_server', FILTER_SANITIZE_SPECIAL_CHARS);
            if (is_null($ldapSetup['ldapServer'])) {
                throw new Exception('LDAP Installation Error: Please add a LDAP server.');
            }

            $ldapSetup['ldapPort'] = Filter::filterInput(INPUT_POST, 'ldap_port', FILTER_VALIDATE_INT);
            if (is_null($ldapSetup['ldapPort'])) {
                throw new Exception('LDAP Installation Error: Please add a LDAP port.');
            }

            $ldapSetup['ldapBase'] = Filter::filterInput(INPUT_POST, 'ldap_base', FILTER_SANITIZE_SPECIAL_CHARS);
            if (is_null($ldapSetup['ldapBase'])) {
                throw new Exception('LDAP Installation Error: Please add a LDAP base search DN.');
            }

            // LDAP User and LDAP password are optional
            $ldapSetup['ldapUser'] = Filter::filterInput(INPUT_POST, 'ldap_user', FILTER_SANITIZE_SPECIAL_CHARS);
            $ldapSetup['ldapPassword'] = Filter::filterInput(
                INPUT_POST,
                'ldap_password',
                FILTER_SANITIZE_SPECIAL_CHARS,
            );

            // set LDAP Config to prevent DB query
            foreach ($this->mainConfig as $configKey => $configValue) {
                if (!str_contains($configKey, 'ldap.')) {
                    continue;
                }

                $configuration->set($configKey, $configValue);
            }

            // check LDAP connection
            $ldap = new Ldap($configuration);
            $ldapConnection = $ldap->connect(
                $ldapSetup['ldapServer'],
                $ldapSetup['ldapPort'],
                $ldapSetup['ldapBase'],
                $ldapSetup['ldapUser'],
                $ldapSetup['ldapPassword'],
            );

            if (!$ldapConnection) {
                throw new Exception(sprintf('LDAP Installation Error: %s.', $ldap->error()));
            }
        }

        // Check Elasticsearch if enabled

        $esEnabled = Filter::filterInput(INPUT_POST, 'elasticsearch_enabled', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!is_null($esEnabled)) {
            $esSetup = [];
            $esHostFilter = [
                'elasticsearch_server' => [
                    'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
                    'flags' => FILTER_REQUIRE_ARRAY,
                ],
            ];

            // ES hosts
            $esHosts = Filter::filterInputArray(INPUT_POST, $esHostFilter);
            if (is_null($esHosts)) {
                throw new Exception('Elasticsearch Installation Error: Please add at least one Elasticsearch host.');
            }

            $esSetup['hosts'] = $esHosts['elasticsearch_server'];

            // ES Index name
            $esSetup['index'] = Filter::filterInput(INPUT_POST, 'elasticsearch_index', FILTER_SANITIZE_SPECIAL_CHARS);
            if (is_null($esSetup['index'])) {
                throw new Exception('Elasticsearch Installation Error: Please add an Elasticsearch index name.');
            }

            $classLoader = new ClassLoader();
            $classLoader->addPsr4('Elasticsearch\\', PMF_SRC_DIR . '/libs/elasticsearch/src/Elasticsearch');
            $classLoader->addPsr4('Monolog\\', PMF_SRC_DIR . '/libs/monolog/src/Monolog');
            $classLoader->addPsr4('Psr\\', PMF_SRC_DIR . '/libs/psr/log/Psr');
            $classLoader->addPsr4('React\\Promise\\', PMF_SRC_DIR . '/libs/react/promise/src');
            $classLoader->register();

            // check Elasticsearch connection
            $esHosts = array_values($esHosts['elasticsearch_server']);
            $esClient = ClientBuilder::create()->setHosts($esHosts)->build();

            if (!$esClient) {
                throw new Exception('Elasticsearch Installation Error: No connection to Elasticsearch.');
            }
        } else {
            $esSetup = [];
        }

        // Check OpenSearch if enabled

        $openSearchEnabled = Filter::filterInput(INPUT_POST, 'opensearch_enabled', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!is_null($openSearchEnabled)) {
            $osSetup = [];
            $osHostFilter = [
                'opensearch_server' => [
                    'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
                    'flags' => FILTER_REQUIRE_ARRAY,
                ],
            ];

            // OS hosts
            $osHosts = Filter::filterInputArray(INPUT_POST, $osHostFilter);
            if (is_null($osHosts)) {
                throw new Exception('OpenSearch Installation Error: Please add at least one OpenSearch host.');
            }

            $osSetup['hosts'] = $osHosts['opensearch_server'];

            // OS Index name
            $osSetup['index'] = Filter::filterInput(INPUT_POST, 'opensearch_index', FILTER_SANITIZE_SPECIAL_CHARS);
            if (is_null($osSetup['index'])) {
                throw new Exception('OpenSearch Installation Error: Please add an OpenSearch index name.');
            }

            // check OpenSearch connection
            $osHosts = array_values($osHosts['opensearch_server']);
            $osClient = new SymfonyClientFactory()->create([
                'base_uri' => $osHosts[0],
                'verify_peer' => false,
            ]);

            if (!$osClient) {
                throw new Exception('OpenSearch Installation Error: No connection to OpenSearch.');
            }
        } else {
            $osSetup = [];
        }

        // check the login name
        if (!isset($setup['loginname'])) {
            $loginName = Filter::filterInput(INPUT_POST, 'loginname', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $loginName = $setup['loginname'];
        }

        if (is_null($loginName)) {
            throw new Exception('Installation Error: Please add a login name for your account.');
        }

        // check user entries
        if (!isset($setup['password'])) {
            $password = Filter::filterInput(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $password = $setup['password'];
        }

        if (is_null($password)) {
            throw new Exception('Installation Error: Please add a password for your account.');
        }

        if (!isset($setup['password_retyped'])) {
            $passwordRetyped = Filter::filterInput(INPUT_POST, 'password_retyped', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $passwordRetyped = $setup['password_retyped'];
        }

        if (is_null($passwordRetyped)) {
            throw new Exception('Installation Error: Please add a retyped password.');
        }

        if (strlen((string) $password) <= 7 || strlen((string) $passwordRetyped) <= 7) {
            throw new Exception(
                'Installation Error: Your password and retyped password are too short. Please set your password '
                . 'and your retyped password with a minimum of 8 characters.',
            );
        }

        if ($password !== $passwordRetyped) {
            throw new Exception(
                'Installation Error: Your password and retyped password are not equal. Please check your password '
                . 'and your retyped password.',
            );
        }

        $language = Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_SPECIAL_CHARS, 'en');
        $realname = Filter::filterInput(INPUT_POST, 'realname', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL, '');
        $permLevel = Filter::filterInput(INPUT_POST, 'permLevel', FILTER_SANITIZE_SPECIAL_CHARS, 'basic');

        $rootDir = $setup['rootDir'] ?? PMF_ROOT_DIR;

        $instanceSetup = new Setup();
        $instanceSetup->setRootDir($rootDir);

        // Write the DB variables in database.php
        if (!$instanceSetup->createDatabaseFile($dbSetup)) {
            self::cleanFailedInstallationFiles();
            throw new Exception('Installation Error: Setup cannot write to ./content/core/config/database.php.');
        }

        // check LDAP is enabled
        if (
            extension_loaded('ldap')
            && !is_null($ldapEnabled)
            && count($ldapSetup)
            && !$instanceSetup->createLdapFile($ldapSetup, '')
        ) {
            self::cleanFailedInstallationFiles();
            throw new Exception('LDAP Installation Error: Setup cannot write to ./content/core/config/ldap.php.');
        }

        // check if Elasticsearch is enabled
        if (!is_null($esEnabled) && count($esSetup) && !$instanceSetup->createElasticsearchFile($esSetup, '')) {
            self::cleanFailedInstallationFiles();
            throw new Exception(
                'Elasticsearch Installation Error: Setup cannot write to ./content/core/config/elasticsearch.php.',
            );
        }

        // check if OpenSearch is enabled
        if (!is_null($openSearchEnabled) && count($osSetup) && !$instanceSetup->createOpenSearchFile($osSetup, '')) {
            self::cleanFailedInstallationFiles();
            throw new Exception(
                'OpenSearch Installation Error: Setup cannot write to ./content/core/config/opensearch.php.',
            );
        }

        // connect to the database using config/database.php
        $databaseConfiguration = new DatabaseConfiguration($rootDir . '/content/core/config/database.php');
        try {
            $db = Database::factory($dbSetup['dbType']);
        } catch (Exception $exception) {
            self::cleanFailedInstallationFiles();
            throw new Exception(sprintf('Database Installation Error: %s', $exception->getMessage()));
        }

        $db->connect(
            $databaseConfiguration->getServer(),
            $databaseConfiguration->getUser(),
            $databaseConfiguration->getPassword(),
            $databaseConfiguration->getDatabase(),
            $databaseConfiguration->getPort(),
        );

        if (!$db instanceof DatabaseDriver) {
            self::cleanFailedInstallationFiles();
            throw new Exception(sprintf('Database Installation Error: %s', $db->error()));
        }

        try {
            $databaseInstaller = InstanceDatabase::factory($configuration, $dbSetup['dbType']);
            $databaseInstaller->createTables($dbSetup['dbPrefix']);
        } catch (Exception $exception) {
            self::cleanFailedInstallationFiles();
            throw new Exception(sprintf('Database Installation Error: %s', $exception->getMessage()));
        }

        $stopWords = new Stopwords($configuration);
        $stopWords->executeInsertQueries($dbSetup['dbPrefix']);

        $this->system->setDatabase($db);

        // Erase any table before starting creating the required ones
        if (!System::isSqlite($dbSetup['dbType'])) {
            $this->system->dropTables($uninstall);
        }

        // Start creating the required tables
        $count = 0;
        foreach ($query as $executeQuery) {
            $result = @$db->query($executeQuery);
            if (!$result) {
                $this->system->dropTables($uninstall);
                self::cleanFailedInstallationFiles();
                throw new Exception(sprintf(
                    'Installation Error: Please install your version of phpMyFAQ once again: %s (%s)',
                    $db->error(),
                    htmlentities($executeQuery),
                ));
            }

            usleep(1000);
            ++$count;
            if (($count % 10) === 0) {
                echo '| ';
            }
        }

        $link = new Link('', $configuration);

        // add the main configuration, add personal settings
        $this->mainConfig['main.metaPublisher'] = $realname;
        $this->mainConfig['main.administrationMail'] = $email;
        $this->mainConfig['main.language'] = $language;
        $this->mainConfig['security.permLevel'] = $permLevel;

        foreach ($this->mainConfig as $name => $value) {
            $configuration->add($name, $value);
        }

        $configuration->update(['main.referenceURL' => $setup['mainUrl'] ?? $link->getSystemUri('/setup/index.php')]);
        $configuration->add('security.salt', md5($configuration->getDefaultUrl()));

        // add an admin account and rights
        $user = new User($configuration);
        if (!$user->createUser($loginName, $password, '', 1)) {
            self::cleanFailedInstallationFiles();
            throw new Exception(sprintf(
                'Fatal Installation Error: Could not create the admin user: %s',
                $user->error(),
            ));
        }

        $user->setStatus('protected');
        $adminData = [
            'display_name' => $realname,
            'email' => $email,
        ];
        $user->setUserData($adminData);
        $user->setSuperAdmin(true);

        // add default rights
        foreach ($this->mainRights as $mainRight) {
            $user->perm->grantUserRight(1, $user->perm->addRight($mainRight));
        }

        // add inputs in table "faqforms"
        $forms = new Forms($configuration);
        foreach ($this->formInputs as $formInput) {
            $forms->insertInputIntoDatabase($formInput);
        }

        // Add an anonymous user account
        $instanceSetup->createAnonymousUser($configuration);

        // Add primary instance
        $instanceEntity = new InstanceEntity();
        $instanceEntity
            ->setUrl($link->getSystemUri(Request::createFromGlobals()->getScriptName()))
            ->setInstance($link->getSystemRelativeUri('setup/index.php'))
            ->setComment('phpMyFAQ ' . System::getVersion());
        $faqInstance = new Instance($configuration);
        $faqInstance->create($instanceEntity);

        $main = new Main($configuration);
        $main->createMain($faqInstance);

        // connect to Elasticsearch if enabled
        if (!is_null($esEnabled) && is_file($rootDir . '/config/elasticsearch.php')) {
            $elasticsearchConfiguration = new ElasticsearchConfiguration($rootDir . '/config/elasticsearch.php');

            $configuration->setElasticsearchConfig($elasticsearchConfiguration);

            $esClient = ClientBuilder::create()->setHosts($elasticsearchConfiguration->getHosts())->build();

            $configuration->setElasticsearch($esClient);

            $elasticsearch = new Elasticsearch($configuration);
            $elasticsearch->createIndex();
        }

        // connect to OpenSearch if enabled
        if (!is_null($openSearchEnabled) && is_file($rootDir . '/config/opensearch.php')) {
            $openSearchConfiguration = new OpenSearchConfiguration($rootDir . '/config/opensearch.php');

            $configuration->setOpenSearchConfig($openSearchConfiguration);

            $osClient = new SymfonyClientFactory()->create([
                'base_uri' => $openSearchConfiguration->getHosts()[0],
                'verify_peer' => false,
            ]);

            $configuration->setOpenSearch($osClient);

            $openSearch = new OpenSearch($configuration);
            $openSearch->createIndex();
        }

        // adjust RewriteBase in .htaccess
        $environmentConfigurator = new EnvironmentConfigurator($configuration);
        $environmentConfigurator->adjustRewriteBaseHtaccess();
    }

    /**
     * Checks the minimum required PHP version, defined in System class.
     * Returns true if it's okay.
     */
    public function checkMinimumPhpVersion(): bool
    {
        return version_compare(PHP_VERSION, System::VERSION_MINIMUM_PHP) >= 0;
    }

    public function hasLdapSupport(): bool
    {
        return extension_loaded('ldap');
    }

    public function hasElasticsearchSupport(): bool
    {
        return extension_loaded('curl') && extension_loaded('openssl');
    }
}
