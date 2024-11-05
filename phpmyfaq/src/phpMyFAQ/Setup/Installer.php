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
 * @copyright 2012-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-08-27
 */

namespace phpMyFAQ\Setup;

use Composer\Autoload\ClassLoader;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
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
use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Instance\Main;
use phpMyFAQ\Instance\Setup;
use phpMyFAQ\Ldap;
use phpMyFAQ\Link;
use phpMyFAQ\System;
use phpMyFAQ\User;

/**
 * Class Installer
 *
 * @package phpMyFAQ
 */
class Installer extends Setup
{
    /**
     * Array with user rights.
     * @var array<array>
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
        //10 => "addnews",
        [
            'name' => PermissionType::NEWS_ADD->value,
            'description' => 'Right to add news',
        ],
        //11 => "editnews",
        [
            'name' => PermissionType::NEWS_EDIT->value,
            'description' => 'Right to edit news',
        ],
        //12 => "delnews",
        [
            'name' => PermissionType::NEWS_DELETE->value,
            'description' => 'Right to delete news',
        ],
        //13 => "addcateg",
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
        //16 => "passwd",
        [
            'name' => PermissionType::PASSWORD_CHANGE->value,
            'description' => 'Right to change passwords',
        ],
        //17 => "editconfig",
        [
            'name' => PermissionType::CONFIGURATION_EDIT->value,
            'description' => 'Right to edit configuration',
        ],
        //18 => "viewadminlink",
        [
            'name' => PermissionType::VIEW_ADMIN_LINK->value,
            'description' => 'Right to see the link to the admin section'
        ],
        //20 => "backup",
        [
            'name' => PermissionType::BACKUP->value,
            'description' => 'Right to save backups',
        ],
        //21 => "restore",
        [
            'name' => PermissionType::RESTORE->value,
            'description' => 'Right to load backups',
        ],
        //22 => "delquestion",
        [
            'name' => PermissionType::QUESTION_DELETE->value,
            'description' => 'Right to delete questions',
        ],
        //23 => 'addglossary',
        [
            'name' => PermissionType::GLOSSARY_ADD->value,
            'description' => 'Right to add glossary entries',
        ],
        //24 => 'editglossary',
        [
            'name' => PermissionType::GLOSSARY_EDIT->value,
            'description' => 'Right to edit glossary entries',
        ],
        //25 => 'delglossary'
        [
            'name' => PermissionType::GLOSSARY_DELETE->value,
            'description' => 'Right to delete glossary entries',
        ],
        //26 => 'changebtrevs'
        [
            'name' => PermissionType::REVISION_UPDATE->value,
            'description' => 'Right to edit revisions',
        ],
        //27 => "addgroup",
        [
            'name' => PermissionType::GROUP_ADD->value,
            'description' => 'Right to add group accounts',
        ],
        //28 => "editgroup",
        [
            'name' => PermissionType::GROUP_EDIT->value,
            'description' => 'Right to edit group accounts',
        ],
        //29 => "delgroup",
        [
            'name' => PermissionType::GROUP_DELETE->value,
            'description' => 'Right to delete group accounts',
        ],
        //30 => "addtranslation", @deprecated will be removed in 4.1
        [
            'name' => 'addtranslation',
            'description' => 'Right to add translation',
        ],
        //31 => "edittranslation", @deprecated will be removed in 4.1
        [
            'name' => 'edittranslation',
            'description' => 'Right to edit translations',
        ],
        //32 => "deltranslation", @deprecated will be removed in 4.1
        [
            'name' => 'deltranslation',
            'description' => 'Right to delete translations',
        ],
        // 33 => 'approverec'
        [
            'name' => 'approverec',
            'description' => 'Right to approve records',
        ],
        // 34 => 'addattachment'
        [
            'name' => PermissionType::ATTACHMENT_ADD->value,
            'description' => 'Right to add attachments',
        ],
        // 35 => 'editattachment'
        [
            'name' => 'editattachment',
            'description' => 'Right to edit attachments',
        ],
        // 36 => 'delattachment'
        [
            'name' => 'delattachment',
            'description' => 'Right to delete attachments',
        ],
        // 37 => 'dlattachment'
        [
            'name' => 'dlattachment',
            'description' => 'Right to download attachments',
        ],
        // 38 => 'reports'
        [
            'name' => 'reports',
            'description' => 'Right to generate reports',
        ],
        // 39 => 'addfaq'
        [
            'name' => 'addfaq',
            'description' => 'Right to add FAQs in frontend',
        ],
        // 40 => 'addquestion'
        [
            'name' => 'addquestion',
            'description' => 'Right to add questions in frontend',
        ],
        // 41 => 'addcomment'
        [
            'name' => PermissionType::COMMENT_ADD->value,
            'description' => 'Right to add comments in frontend',
        ],
        // 42 => 'editinstances'
        [
            'name' => 'editinstances',
            'description' => 'Right to edit multi-site instances',
        ],
        // 43 => 'addinstances'
        [
            'name' => 'addinstances',
            'description' => 'Right to add multi-site instances',
        ],
        // 44 => 'delinstances'
        [
            'name' => 'delinstances',
            'description' => 'Right to delete multi-site instances',
        ],
        [
            'name' => 'export',
            'description' => 'Right to export the complete FAQ',
        ],
        [
            'name' => 'view_faqs',
            'description' => 'Right to view FAQs'
        ],
        [
            'name' => 'view_categories',
            'description' => 'Right to view categories'
        ],
        [
            'name' => 'view_news',
            'description' => 'Right to view news'
        ],
        [
            'name' => 'administrate_groups',
            'description' => 'Right to administrate groups'
        ],
        [
            'name' => 'forms_edit',
            'description' => 'Right to edit forms'
        ]
    ];

    /**
     * Configuration array.
     *
     * @var array<string, string>
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
        'main.titleFAQ' => 'phpMyFAQ Codename Porus',
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
        'main.enableAutoUpdateHint' => 'true',
        'main.enableAskQuestions' => 'false',
        'main.enableNotifications' => 'false',
        'main.botIgnoreList' => 'nustcrape,webpost,GoogleBot,msnbot,crawler,scooter,bravobrian,archiver,' .
        'w3c,controler,wget,bot,spider,Yahoo! Slurp,htdig,gsa-crawler,AirControler,Uptime-Kuma,facebookcatalog/1.0,' .
        'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php),facebookexternalhit/1.1',

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
        'search.enableElasticsearch' => 'false',

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

        'api.enableAccess' => 'true',
        'api.apiClientToken' => '',

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
     * @var array<array>
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
            'input_lang' => 'default'
        ],
        [
            'form_id' => 1,
            'input_id' => 2,
            'input_type' => 'message',
            'input_label' => 'msgNewQuestion',
            'input_active' => 1,
            'input_required' => -1,
            'input_lang' => 'default'
        ],
        [
            'form_id' => 1,
            'input_id' => 3,
            'input_type' => 'text',
            'input_label' => 'msgNewContentName',
            'input_active' => 1,
            'input_required' => 1,
            'input_lang' => 'default'
        ],
        [
            'form_id' => 1,
            'input_id' => 4,
            'input_type' => 'email',
            'input_label' => 'msgNewContentMail',
            'input_active' => 1,
            'input_required' => 1,
            'input_lang' => 'default'
        ],
        [
            'form_id' => 1,
            'input_id' => 5,
            'input_type' => 'select',
            'input_label' => 'msgNewContentCategory',
            'input_active' => 1,
            'input_required' => 1,
            'input_lang' => 'default'
        ],
        [
            'form_id' => 1,
            'input_id' => 6,
            'input_type' => 'textarea',
            'input_label' => 'msgAskYourQuestion',
            'input_active' => -1,
            'input_required' => -1,
            'input_lang' => 'default'
        ],
        // Add New FAQ inputs
        [
            'form_id' => 2,
            'input_id' => 1,
            'input_type' => 'title',
            'input_label' => 'msgNewContentHeader',
            'input_active' => 1,
            'input_required' => -1,
            'input_lang' => 'default'
        ],
        [
            'form_id' => 2,
            'input_id' => 2,
            'input_type' => 'message',
            'input_label' => 'msgNewContentAddon',
            'input_active' => 1,
            'input_required' => -1,
            'input_lang' => 'default'
        ],
        [
            'form_id' => 2,
            'input_id' => 3,
            'input_type' => 'text',
            'input_label' => 'msgNewContentName',
            'input_active' => 1,
            'input_required' => 1,
            'input_lang' => 'default'
        ],
        [
            'form_id' => 2,
            'input_id' => 4,
            'input_type' => 'email',
            'input_label' => 'msgNewContentMail',
            'input_active' => 1,
            'input_required' => 1,
            'input_lang' => 'default'
        ],
        [
            'form_id' => 2,
            'input_id' => 5,
            'input_type' => 'select',
            'input_label' => 'msgNewContentCategory',
            'input_active' => 1,
            'input_required' => 1,
            'input_lang' => 'default'
        ],
        [
            'form_id' => 2,
            'input_id' => 6,
            'input_type' => 'textarea',
            'input_label' => 'msgNewContentTheme',
            'input_active' => -1,
            'input_required' => -1,
            'input_lang' => 'default'
        ],
        [
            'form_id' => 2,
            'input_id' => 7,
            'input_type' => 'textarea',
            'input_label' => 'msgNewContentArticle',
            'input_active' => 1,
            'input_required' => 1,
            'input_lang' => 'default'
        ],
        [
            'form_id' => 2,
            'input_id' => 8,
            'input_type' => 'text',
            'input_label' => 'msgNewContentKeywords',
            'input_active' => 1,
            'input_required' => 1,
            'input_lang' => 'default'
        ],
        [
            'form_id' => 2,
            'input_id' => 9,
            'input_type' => 'title',
            'input_label' => 'msgNewContentLink',
            'input_active' => 1,
            'input_required' => 1,
            'input_lang' => 'default'
        ]
    ];

    /**
     * Constructor.
     *
     * @throws \Exception
     */
    public function __construct(private readonly System $system)
    {
        parent::__construct();

        $dynMainConfig = [
            'main.currentVersion' => System::getVersion(),
            'main.currentApiVersion' => System::getApiVersion(),
            'main.phpMyFAQToken' => bin2hex(random_bytes(16)),
            'spam.enableCaptchaCode' => (extension_loaded('gd') ? 'true' : 'false'),
            'upgrade.releaseEnvironment' =>
                System::isDevelopmentVersion() ? ReleaseType::DEVELOPMENT->value : ReleaseType::STABLE->value
        ];

        $this->mainConfig = array_merge($this->mainConfig, $dynMainConfig);
    }

    /**
     * Removes the database.php and the ldap.php if an installation failed.
     */
    public static function cleanFailedInstallationFiles(): void
    {
        // Remove './config/database.php' file: no need of prompt anything to the user
        if (file_exists(PMF_ROOT_DIR . '/content/core/config/database.php')) {
            unlink(PMF_ROOT_DIR . '/content/core/config/database.php');
        }

        // Remove './config/ldap.php' file: no need of prompt anything to the user
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
            throw new Exception(
                sprintf(
                    'Some required PHP extensions are missing: %s',
                    implode(', ', $this->system->getMissingExtensions())
                )
            );
        }

        if (!$this->system->checkInstallation()) {
            throw new Exception('phpMyFAQ is already installed! Please use the <a href="../update">update</a>.');
        }
    }

    /**
     * Checks if the file permissions are okay.
     */
    public function checkFilesystemPermissions(): string|null
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
                (1 < $numDirs) ? 'directories' : 'directory',
                (1 < $numDirs) ? 'are' : 'is'
            );
            foreach ($failedDirs as $failedDir) {
                $hints .= "<li>{$failedDir}</li>\n";
            }

            $hints .= sprintf(
                '</ul><p class="alert alert-danger">Please create %s manually and/or change access to chmod 775 (or ' .
                'greater if necessary).</p>',
                (1 < $numDirs) ? 'them' : 'it'
            );

            return $hints;
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
            $hints[] = '<p class="alert alert-warning">HTTPS support is not enabled in your web server.' .
                ' To ensure the security of your data and protect against potential vulnerabilities,' .
                ' we highly recommend enabling HTTPS. Please configure your web server to support HTTPS as soon as' .
                ' possible.</p>';
        }

        if (!extension_loaded('gd')) {
            $hints[] = '<p class="alert alert-warning">You don\'t have GD support enabled in your PHP installation. ' .
                "Please enable GD support in your php.ini file otherwise you can't use Captchas for spam protection." .
                "</p>";
        }

        if (!function_exists('imagettftext')) {
            $hints[] = '<p class="alert alert-warning">You don\'t have Freetype support enabled in the GD extension ' .
                ' of your PHP installation. Please enable Freetype support in GD extension otherwise the Captchas ' .
                'for spam protection will be quite easy to break.</p>';
        }

        if (!extension_loaded('curl') || !extension_loaded('openssl')) {
            $hints[] = '<p class="alert alert-warning">You don\'t have cURL and/or OpenSSL support enabled in your ' .
                "PHP installation. Please enable cURL and/or OpenSSL support in your php.ini file otherwise you " .
                " can't use Elasticsearch.</p>";
        }

        if (!extension_loaded('fileinfo')) {
            $hints[] = '<p class="alert alert-warning">You don\'t have Fileinfo support enabled in your PHP ' .
                "installation. Please enable Fileinfo support in your php.ini file otherwise you can't use our " .
                'backup/restore functionality.</p>';
        }

        if (!extension_loaded('sodium')) {
            $hints[] = '<p class="alert alert-warning">You don\'t have Sodium support enabled in your PHP ' .
                "installation. Please enable Sodium support in your php.ini file otherwise you can't use our " .
                'backup/restore functionality.</p>';
        }

        return $hints;
    }

    /**
     * Starts the installation.
     *
     * @param array|null $setup
     * @throws Exception|AuthenticationException
     */
    public function startInstall(array|null $setup = null): void
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
            if (!file_exists(PMF_SRC_DIR . '/phpMyFAQ/Instance/Database/' . ucfirst($dbSetup['dbType']) . '.php')) {
                throw new Exception(sprintf('Installation Error: Invalid server type: %s', $dbSetup['dbType']));
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

        if (is_null($dbSetup['dbPort']) && ! System::isSqlite($dbSetup['dbType'])) {
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
                $setup['dbServer'] ?? null
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
            $dbSetup['dbPort']
        );

        $configuration = new Configuration($db);

        //
        // Check LDAP if enabled
        //
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
                FILTER_SANITIZE_SPECIAL_CHARS
            );

            // set LDAP Config to prevent DB query
            foreach ($this->mainConfig as $configKey => $configValue) {
                if (str_contains($configKey, 'ldap.')) {
                    $configuration->set($configKey, $configValue);
                }
            }

            // check LDAP connection
            $ldap = new Ldap($configuration);
            $ldapConnection = $ldap->connect(
                $ldapSetup['ldapServer'],
                $ldapSetup['ldapPort'],
                $ldapSetup['ldapBase'],
                $ldapSetup['ldapUser'],
                $ldapSetup['ldapPassword']
            );

            if (!$ldapConnection) {
                throw new Exception(sprintf('LDAP Installation Error: %s.', $ldap->error()));
            }
        }

        //
        // Check Elasticsearch if enabled
        //
        $esEnabled = Filter::filterInput(INPUT_POST, 'elasticsearch_enabled', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!is_null($esEnabled)) {
            $esSetup = [];
            $esHostFilter = [
                'elasticsearch_server' => [
                    'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
                    'flags' => FILTER_REQUIRE_ARRAY
                ]
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

            // check LDAP connection
            $esHosts = array_values($esHosts['elasticsearch_server']);
            $esClient = ClientBuilder::create()->setHosts($esHosts)->build();

            if (!$esClient) {
                throw new Exception('Elasticsearch Installation Error: No connection to Elasticsearch.');
            }
        } else {
            $esSetup = [];
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
                'Installation Error: Your password and retyped password are too short. Please set your password ' .
                'and your retyped password with a minimum of 8 characters.'
            );
        }

        if ($password !== $passwordRetyped) {
            throw new Exception(
                'Installation Error: Your password and retyped password are not equal. Please check your password ' .
                'and your retyped password.'
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
            extension_loaded('ldap') &&
            !is_null($ldapEnabled) &&
            count($ldapSetup) &&
            !$instanceSetup->createLdapFile($ldapSetup, '')
        ) {
            self::cleanFailedInstallationFiles();
            throw new Exception('LDAP Installation Error: Setup cannot write to ./content/core/config/ldap.php.');
        }

        // check if Elasticsearch is enabled
        if (!is_null($esEnabled) && count($esSetup) && !$instanceSetup->createElasticsearchFile($esSetup, '')) {
            self::cleanFailedInstallationFiles();
            throw new Exception(
                'Elasticsearch Installation Error: Setup cannot write to ./content/core/config/elasticsearch.php.'
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
            $databaseConfiguration->getPort()
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
                throw new Exception(
                    sprintf(
                        'Installation Error: Please install your version of phpMyFAQ once again: %s (%s)',
                        $db->error(),
                        htmlentities($executeQuery)
                    )
                );
            }

            usleep(1000);
            ++$count;
            if ($count % 10 === 0) {
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

        $configuration->update(['main.referenceURL' => $link->getSystemUri('/setup/index.php')]);
        $configuration->add('security.salt', md5($configuration->getDefaultUrl()));

        // add an admin account and rights
        $user = new User($configuration);
        if (!$user->createUser($loginName, $password, '', 1)) {
            self::cleanFailedInstallationFiles();
            throw new Exception(
                sprintf('Fatal Installation Error: Could not create the admin user: %s', $user->error())
            );
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
        foreach ($this->formInputs as $input) {
            $forms->insertInputIntoDatabase($input);
        }

        // Add an anonymous user account
        $instanceSetup->createAnonymousUser($configuration);

        // Add primary instance
        $instanceEntity = new InstanceEntity();
        $instanceEntity
            ->setUrl($link->getSystemUri($_SERVER['SCRIPT_NAME']))
            ->setInstance($link->getSystemRelativeUri('setup/index.php'))
            ->setComment('phpMyFAQ ' . System::getVersion());
        $faqInstance = new Instance($configuration);
        $faqInstance->create($instanceEntity);

        $faqMainInstance = new Main($configuration);
        $faqMainInstance->createMain($faqInstance);

        // connect to Elasticsearch if enabled
        if (!is_null($esEnabled) && is_file($rootDir . '/config/elasticsearch.php')) {
            $elasticsearchConfiguration = new ElasticsearchConfiguration($rootDir . '/config/elasticsearch.php');

            $configuration->setElasticsearchConfig($elasticsearchConfiguration);

            $esClient = ClientBuilder::create()->setHosts($elasticsearchConfiguration->getHosts())->build();

            $configuration->setElasticsearch($esClient);

            $faqInstanceElasticsearch = new Elasticsearch($configuration);
            $faqInstanceElasticsearch->createIndex();
        }

        // adjust RewriteBase in .htaccess
        $this->adjustRewriteBaseHtaccess($rootDir);
    }

    /**
     * Checks the minimum required PHP version, defined in System class.
     * Returns true if it's okay.
     */
    public function checkMinimumPhpVersion(): bool
    {
        return version_compare(PHP_VERSION, System::VERSION_MINIMUM_PHP) >= 0;
    }

    /**
     * Adjusts the RewriteBase in the .htaccess file for the user's environment to avoid errors with controllers.
     * Returns true, if the file was successfully changed.
     *
     * @throws Exception
     */
    public function adjustRewriteBaseHtaccess(string $path): bool
    {
        $htaccessPath = $path . '/.htaccess';

        if (!file_exists($htaccessPath)) {
            throw new Exception(sprintf('The %s file does not exist!', $htaccessPath));
        }

        $lines = file($htaccessPath);
        $newLines = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'RewriteBase')) {
                $requestUri = filter_input(INPUT_SERVER, 'PHP_SELF');
                $rewriteBase = substr((string) $requestUri, 0, strpos((string) $requestUri, 'setup/index.php'));
                $rewriteBase = ($rewriteBase === '') ? '/' : $rewriteBase;
                $newLines[] = 'RewriteBase ' . $rewriteBase . PHP_EOL;
            } else {
                $newLines[] = $line;
            }
        }

        return file_put_contents($htaccessPath, implode('', $newLines)) !== false;
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
