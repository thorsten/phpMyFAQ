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
 * @copyright 2012-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-08-27
 */

namespace phpMyFAQ\Setup;

use Composer\Autoload\ClassLoader;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use phpMyFAQ\Component\Alert;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Entity\InstanceEntity;
use phpMyFAQ\Filter;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Database as InstanceDatabase;
use phpMyFAQ\Instance\Database\Stopwords;
use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Instance\Master;
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
            'name' => 'add_user',
            'description' => 'Right to add user accounts',
        ],
        [
            'name' => 'edit_user',
            'description' => 'Right to edit user accounts',
        ],
        [
            'name' => 'delete_user',
            'description' => 'Right to delete user accounts',
        ],
        //4 => "add_faq",
        [
            'name' => 'add_faq',
            'description' => 'Right to add faq entries',
        ],
        //5 => "edit_faq",
        [
            'name' => 'edit_faq',
            'description' => 'Right to edit faq entries',
        ],
        //6 => "delete_faq",
        [
            'name' => 'delete_faq',
            'description' => 'Right to delete faq entries',
        ],
        //7 => "viewlog",
        [
            'name' => 'viewlog',
            'description' => 'Right to view logfiles',
        ],
        //8 => "adminlog",
        [
            'name' => 'adminlog',
            'description' => 'Right to view admin log',
        ],
        //9 => "delcomment",
        [
            'name' => 'delcomment',
            'description' => 'Right to delete comments',
        ],
        //10 => "addnews",
        [
            'name' => 'addnews',
            'description' => 'Right to add news',
        ],
        //11 => "editnews",
        [
            'name' => 'editnews',
            'description' => 'Right to edit news',
        ],
        //12 => "delnews",
        [
            'name' => 'delnews',
            'description' => 'Right to delete news',
        ],
        //13 => "addcateg",
        [
            'name' => 'addcateg',
            'description' => 'Right to add categories',
        ],
        //14 => "editcateg",
        [
            'name' => 'editcateg',
            'description' => 'Right to edit categories',
        ],
        //15 => "delcateg",
        [
            'name' => 'delcateg',
            'description' => 'Right to delete categories',
        ],
        //16 => "passwd",
        [
            'name' => 'passwd',
            'description' => 'Right to change passwords',
        ],
        //17 => "editconfig",
        [
            'name' => 'editconfig',
            'description' => 'Right to edit configuration',
        ],
        //18 => "viewadminlink",
        [
            'name' => 'viewadminlink',
            'description' => 'Right to see the link to the admin section'
        ],
        //19 => "backup delatt", // Duplicate, removed with 2.7.3
        //[
        //    'name' => 'delatt',
        //    'description' => 'Right to delete attachments'
        //],
        //20 => "backup",
        [
            'name' => 'backup',
            'description' => 'Right to save backups',
        ],
        //21 => "restore",
        [
            'name' => 'restore',
            'description' => 'Right to load backups',
        ],
        //22 => "delquestion",
        [
            'name' => 'delquestion',
            'description' => 'Right to delete questions',
        ],
        //23 => 'addglossary',
        [
            'name' => 'addglossary',
            'description' => 'Right to add glossary entries',
        ],
        //24 => 'editglossary',
        [
            'name' => 'editglossary',
            'description' => 'Right to edit glossary entries',
        ],
        //25 => 'delglossary'
        [
            'name' => 'delglossary',
            'description' => 'Right to delete glossary entries',
        ],
        //26 => 'changebtrevs'
        [
            'name' => 'changebtrevs',
            'description' => 'Right to edit revisions',
        ],
        //27 => "addgroup",
        [
            'name' => 'addgroup',
            'description' => 'Right to add group accounts',
        ],
        //28 => "editgroup",
        [
            'name' => 'editgroup',
            'description' => 'Right to edit group accounts',
        ],
        //29 => "delgroup",
        [
            'name' => 'delgroup',
            'description' => 'Right to delete group accounts',
        ],
        //30 => "addtranslation",
        [
            'name' => 'addtranslation',
            'description' => 'Right to add translation',
        ],
        //31 => "edittranslation",
        [
            'name' => 'edittranslation',
            'description' => 'Right to edit translations',
        ],
        //32 => "deltranslation",
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
            'name' => 'addattachment',
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
            'name' => 'addcomment',
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
            'name' => 'view_sections',
            'description' => 'Right to view sections'
        ],
        [
            'name' => 'view_news',
            'description' => 'Right to view news'
        ],
        [
            'name' => 'add_section',
            'description' => 'Right to add sections'
        ],
        [
            'name' => 'edit_section',
            'description' => 'Right to edit sections'
        ],
        [
            'name' => 'delete_section',
            'description' => 'Right to delete sections'
        ],
        [
            'name' => 'administrate_sections',
            'description' => 'Right to administrate sections'
        ],
        [
            'name' => 'administrate_groups',
            'description' => 'Right to administrate groups'
        ],
    ];

    /**
     * Configuration array.
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
        'main.enableRewriteRules' => 'false',
        'main.enableUserTracking' => 'true',
        'main.metaDescription' => 'phpMyFAQ should be the answer for all questions in life',
        'main.metaKeywords' => '',
        'main.metaPublisher' => '__PHPMYFAQ_PUBLISHER__',
        'main.send2friendText' => '',
        'main.titleFAQ' => 'phpMyFAQ Codename Pontus',
        'main.urlValidateInterval' => '86400',
        'main.enableWysiwygEditor' => 'true',
        'main.enableWysiwygEditorFrontend' => 'false',
        'main.enableMarkdownEditor' => 'false',
        'main.templateSet' => 'default',
        'main.optionalMailAddress' => 'false',
        'main.dateFormat' => 'Y-m-d H:i',
        'main.maintenanceMode' => 'false',
        'main.enableGravatarSupport' => 'false',
        'main.enableGzipCompression' => 'true',
        'main.customPdfHeader' => '',
        'main.customPdfFooter' => '',
        'main.enableSmartAnswering' => 'true',
        'main.enableCategoryRestrictions' => 'true',
        'main.enableSendToFriend' => 'true',
        'main.privacyURL' => '',
        'main.enableAutoUpdateHint' => 'true',
        'security.loginWithEmailAddress' => 'false',
        'main.enableAskQuestions' => 'false',
        'main.enableNotifications' => 'false',
        'main.contactInformationHTML' => 'false',

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
        'records.attachmentsPath' => 'attachments',
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


        'spam.checkBannedWords' => 'true',
        'spam.enableCaptchaCode' => null,
        'spam.enableSafeEmail' => 'true',
        'spam.manualActivation' => 'true',
        'spam.mailAddressInExport' => 'true',

        'socialnetworks.enableTwitterSupport' => 'false',
        'socialnetworks.twitterConsumerKey' => '',
        'socialnetworks.twitterConsumerSecret' => '',
        'socialnetworks.twitterAccessTokenKey' => '',
        'socialnetworks.twitterAccessTokenSecret' => '',
        'socialnetworks.disableAll' => 'false',

        'seo.metaTagsHome' => 'index, follow',
        'seo.metaTagsFaqs' => 'index, follow',
        'seo.metaTagsCategories' => 'index, follow',
        'seo.metaTagsPages' => 'index, follow',
        'seo.metaTagsAdmin' => 'noindex, nofollow',
        'seo.enableXMLSitemap' => 'true',

        'mail.remoteSMTP' => 'false',
        'mail.remoteSMTPServer' => '',
        'mail.remoteSMTPUsername' => '',
        'mail.remoteSMTPPassword' => '',
        'mail.remoteSMTPPort' => '25',
        'mail.remoteSMTPEncryption' => '',
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

        'ad.adSupport' => 'false',
        'ad.ad_mapping.name' => 'cn',
        'ad.ad_mapping.username' => 'samAccountName',
        'ad.ad_mapping.mail' => 'mail',
        'ad.ad_mapping.memberOf' => '',
        'ad.ad_use_domain_prefix' => 'true',
        'ad.ad_options.LDAP_OPT_PROTOCOL_VERSION' => '3',
        'ad.ad_options.LDAP_OPT_REFERRALS' => '0',
        'ad.ad_use_memberOf' => 'false',
        'ad.ad_use_sasl' => 'false',
        'ad.ad_use_multiple_servers' => 'false',
        'ad.ad_use_anonymous_login' => 'false',
        'ad.ad_use_dynamic_login' => 'false',
        'ad.ad_dynamic_login_attribute' => 'uid',

        'api.enableAccess' => 'true',
        'api.apiClientToken' => '',
    ];

    /**
     * Constructor.
     *
     * @throws \Exception
     */
    public function __construct(protected System $system)
    {
        parent::__construct();

        $dynMainConfig = [
            'main.currentVersion' => System::getVersion(),
            'main.currentApiVersion' => System::getApiVersion(),
            'main.phpMyFAQToken' => bin2hex(random_bytes(16)),
            'spam.enableCaptchaCode' => (extension_loaded('gd') ? 'true' : 'false'),
        ];
        $this->mainConfig = array_merge($this->mainConfig, $dynMainConfig);
    }

    /**
     * Check absolutely necessary stuff and die.
     */
    public function checkBasicStuff(): void
    {
        if (!$this->checkMinimumPhpVersion()) {
            Alert::danger(
                'ad_entryins_fail',
                sprintf('Sorry, but you need PHP %s or later!', System::VERSION_MINIMUM_PHP)
            );
            System::renderFooter();
        }

        if (!function_exists('date_default_timezone_set')) {
            Alert::danger(
                'ad_entryins_fail',
                'Sorry, but setting a default timezone does not work in your environment!'
            );
            echo '<p class="alert alert-danger">Sorry, but setting a default timezone does not work in your ' .
                'environment!</p>';
            System::renderFooter();
        }

        if (!$this->system->checkDatabase()) {
            echo '<p class="alert alert-danger">No supported database detected! Please install one of the following' .
                ' database systems and enable the corresponding PHP extension in php.ini:</p>';
            echo '<ul>';
            foreach ($this->system->getSupportedDatabases() as $database) {
                printf('    <li>%s</li>', $database[1]);
            }
            echo '</ul>';
            System::renderFooter();
        }

        if (!$this->system->checkRequiredExtensions()) {
            echo '<p class="alert alert-danger">The following extensions are missing! Please enable the PHP ' .
                'extension(s) in php.ini.</p>';
            echo '<ul>';
            foreach ($this->system->getMissingExtensions() as $extension) {
                printf('    <li>ext/%s</li>', $extension);
            }
            echo '</ul>';
            System::renderFooter();
        }

        if (!$this->system->checkInstallation()) {
            echo '<p class="alert alert-danger">The setup script found the file <code>config/database.php</code>. It ' .
                'looks like you\'re already running a version of phpMyFAQ. Please run the <a href="update.php">update' .
                ' script</a>.</p>';
            System::renderFooter();
        }
    }

    /**
     * Checks the minimum required PHP version, defined in System.
     */
    public function checkMinimumPhpVersion(): bool
    {
        return true;
    }

    /**
     * Checks for the minimum PHP requirement and if the database credentials file is readable.
     */
    public function checkPreUpgrade(string $databaseType): void
    {
        $database = null;
        if (!$this->checkMinimumPhpVersion()) {
            Alert::danger(
                'ad_entryins_fail',
                sprintf('Sorry, but you need PHP %s or later!', System::VERSION_MINIMUM_PHP)
            );
            System::renderFooter();
        }

        if (!is_readable(PMF_ROOT_DIR . '/config/database.php')) {
            echo '<p class="alert alert-danger">It seems you never run a version of phpMyFAQ.<br>' .
                'Please use the <a href="index.php">install script</a>.</p>';
            System::renderFooter();
        }

        if ('' !== $databaseType) {
            $databaseFound = false;
            foreach ($this->system->getSupportedDatabases() as $database => $values) {
                if ($database === $databaseType) {
                    $databaseFound = true;
                    break;
                }
            }
            if (!$databaseFound) {
                echo '<p class="alert alert-danger">It seems you\'re using an unsupported database version.<br>' .
                    'We found ' . ucfirst($database) .
                    '<br>' . 'Please use the change the database type in <code>config/database.php</code>.</p>';
                System::renderFooter();
            }
        }
    }

    /**
     * Checks if the file permissions are okay.
     */
    public function checkFilesystemPermissions(): void
    {
        $instanceSetup = new Setup();
        $instanceSetup->setRootDir(PMF_ROOT_DIR);

        $dirs = ['/attachments', '/config', '/data', '/images', '/logs'];
        $failedDirs = $instanceSetup->checkDirs($dirs);
        $numDirs = sizeof($failedDirs);

        if (1 <= $numDirs) {
            printf(
                '<p class="alert alert-danger">The following %s could not be created or %s not writable:</p><ul>',
                (1 < $numDirs) ? 'directories' : 'directory',
                (1 < $numDirs) ? 'are' : 'is'
            );
            foreach ($failedDirs as $dir) {
                echo "<li>$dir</li>\n";
            }
            printf(
                '</ul><p class="alert alert-danger">Please create %s manually and/or change access to chmod 775 (or ' .
                'greater if necessary).</p>',
                (1 < $numDirs) ? 'them' : 'it'
            );
            System::renderFooter();
        }
    }

    /**
     * Checks some non-critical settings and print some hints.
     *
     * @todo We should return an array of messages
     */
    public function checkNoncriticalSettings(): void
    {
        if (!$this->system->getHttpsStatus()) {
            echo '<p class="alert alert-warning">phpMyFAQ could not find HTTPS support. For security reasons we ' .
                'recommend activating HTTPS.</p>';
        }
        if (!extension_loaded('gd')) {
            echo '<p class="alert alert-warning">You don\'t have GD support enabled in your PHP installation. Please ' .
                'enable GD support in your php.ini file otherwise you can\'t use Captchas for spam protection.</p>';
        }
        if (!function_exists('imagettftext')) {
            echo '<p class="alert alert-warning">You don\'t have Freetype support enabled in the GD extension of ' .
                'your PHP installation. Please enable Freetype support in GD extension otherwise the Captchas ' .
                'for spam protection will be quite easy to break.</p>';
        }
        if (!extension_loaded('curl') || !extension_loaded('openssl')) {
            echo '<p class="alert alert-warning">You don\'t have cURL and/or OpenSSL support enabled in your PHP ' .
                'installation. Please enable cURL and/or OpenSSL support in your php.ini file otherwise you can\'t ' .
                'use the Twitter support and/or Elasticsearch.</p>';
        }
        if (!extension_loaded('fileinfo')) {
            echo '<p class="alert alert-warning">You don\'t have Fileinfo support enabled in your PHP installation. ' .
                'Please enable Fileinfo support in your php.ini file otherwise you can\'t use our backup/restore ' .
                'functionality.</p>';
        }
    }

    /**
     * Checks if phpMyFAQ database tables are available
     * @deprecated moved to class Update
     */
    public function checkAvailableDatabaseTables(DatabaseDriver $database): void
    {
        $query = sprintf(
            'SELECT * FROM %s%s',
            Database::getTablePrefix(),
            'faqconfig'
        );
        $result = $database->query($query);
        if ($database->numRows($result) === 0) {
            echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Table faqconfig not found.</p>\n";
            System::renderFooter(true);
        }
    }

    /**
     * Starts the installation.
     *
     * @param array|null $setup
     * @throws Exception|AuthenticationException
     */
    public function startInstall(array $setup = null): void
    {
        $ldapSetup = [];
        $query = $uninstall = $dbSetup = [];

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
                printf(
                    '<p class="alert alert-danger"><strong>Error:</strong> Invalid server type: %s</p>',
                    $dbSetup['dbType']
                );
                System::renderFooter(true);
            }
        } else {
            echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please select a database type.</p>\n";
            System::renderFooter(true);
        }

        $dbSetup['dbServer'] = Filter::filterInput(INPUT_POST, 'sql_server', FILTER_SANITIZE_SPECIAL_CHARS, '');
        if (is_null($dbSetup['dbServer']) && !System::isSqlite($dbSetup['dbType'])) {
            echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add a database server.</p>\n";
            System::renderFooter(true);
        }

        // Check database port
        if (!isset($setup['dbType'])) {
            $dbSetup['dbPort'] = Filter::filterInput(INPUT_POST, 'sql_port', FILTER_VALIDATE_INT);
        } else {
            $dbSetup['dbPort'] = $setup['dbPort'];
        }
        if (is_null($dbSetup['dbPort']) && ! System::isSqlite($dbSetup['dbType'])) {
            echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a valid database port.</p>\n";
            System::renderFooter(true);
        }

        $dbSetup['dbUser'] = Filter::filterInput(INPUT_POST, 'sql_user', FILTER_SANITIZE_SPECIAL_CHARS, '');
        if (is_null($dbSetup['dbUser']) && !System::isSqlite($dbSetup['dbType'])) {
            echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add a database username.</p>\n";
            System::renderFooter(true);
        }

        $dbSetup['dbPassword'] = Filter::filterInput(INPUT_POST, 'sql_password', FILTER_SANITIZE_SPECIAL_CHARS, '');
        if (is_null($dbSetup['dbPassword']) && !System::isSqlite($dbSetup['dbType'])) {
            // Password can be empty...
            $dbSetup['dbPassword'] = '';
        }

        // Check the database name
        if (!isset($setup['dbType'])) {
            $dbSetup['dbDatabaseName'] = Filter::filterInput(INPUT_POST, 'sql_db', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $dbSetup['dbDatabaseName'] = $setup['dbDatabaseName'];
        }
        if (is_null($dbSetup['dbDatabaseName']) && !System::isSqlite($dbSetup['dbType'])) {
            echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add a database name.</p>\n";
            System::renderFooter(true);
        }

        if (System::isSqlite($dbSetup['dbType'])) {
            $dbSetup['dbServer'] = Filter::filterInput(
                INPUT_POST,
                'sql_sqlitefile',
                FILTER_SANITIZE_SPECIAL_CHARS,
                $setup['dbServer']
            );
            if (is_null($dbSetup['dbServer'])) {
                echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add a SQLite database " .
                    "filename.</p>\n";
                System::renderFooter(true);
            }
        }

        // check database connection
        Database::setTablePrefix($dbSetup['dbPrefix']);
        $db = Database::factory($dbSetup['dbType']);
        try {
            $db->connect(
                $dbSetup['dbServer'],
                $dbSetup['dbUser'],
                $dbSetup['dbPassword'],
                $dbSetup['dbDatabaseName'],
                $dbSetup['dbPort']
            );
        } catch (Exception $e) {
            printf("<p class=\"alert alert-danger\"><strong>DB Error:</strong> %s</p>\n", $e->getMessage());
            System::renderFooter(true);
        }

        $configuration = new Configuration($db);

        //
        // Check LDAP if enabled
        //
        $ldapEnabled = Filter::filterInput(INPUT_POST, 'ldap_enabled', FILTER_SANITIZE_SPECIAL_CHARS);
        if (extension_loaded('ldap') && !is_null($ldapEnabled)) {
            $ldapSetup = [];

            // check LDAP entries
            $ldapSetup['ldapServer'] = Filter::filterInput(INPUT_POST, 'ldap_server', FILTER_SANITIZE_SPECIAL_CHARS);
            if (is_null($ldapSetup['ldapServer'])) {
                echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add a LDAP server.</p>\n";
                System::renderFooter(true);
            }

            $ldapSetup['ldapPort'] = Filter::filterInput(INPUT_POST, 'ldap_port', FILTER_VALIDATE_INT);
            if (is_null($ldapSetup['ldapPort'])) {
                echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add a LDAP port.</p>\n";
                System::renderFooter(true);
            }

            $ldapSetup['ldapBase'] = Filter::filterInput(INPUT_POST, 'ldap_base', FILTER_SANITIZE_SPECIAL_CHARS);
            if (is_null($ldapSetup['ldapBase'])) {
                echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add a LDAP base search DN.</p>\n";
                System::renderFooter(true);
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
            $ldap->connect(
                $ldapSetup['ldapServer'],
                $ldapSetup['ldapPort'],
                $ldapSetup['ldapBase'],
                $ldapSetup['ldapUser'],
                $ldapSetup['ldapPassword']
            );

            if (!$ldap) {
                echo '<p class="alert alert-danger"><strong>LDAP Error:</strong> ' . $ldap->error() . "</p>\n";
                System::renderFooter(true);
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
                echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add at least one Elasticsearch " .
                    "host.</p>\n";
                System::renderFooter(true);
            }

            $esSetup['hosts'] = $esHosts['elasticsearch_server'];

            // ES Index name
            $esSetup['index'] = Filter::filterInput(INPUT_POST, 'elasticsearch_index', FILTER_SANITIZE_SPECIAL_CHARS);
            if (is_null($esSetup['index'])) {
                echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add an Elasticsearch index " .
                    "name.</p>\n";
                System::renderFooter(true);
            }

            $psr4Loader = new ClassLoader();
            $psr4Loader->addPsr4('Elasticsearch\\', PMF_SRC_DIR . '/libs/elasticsearch/src/Elasticsearch');
            $psr4Loader->addPsr4('GuzzleHttp\\Ring\\', PMF_SRC_DIR . '/libs/guzzlehttp/ringphp/src');
            $psr4Loader->addPsr4('Monolog\\', PMF_SRC_DIR . '/libs/monolog/src/Monolog');
            $psr4Loader->addPsr4('Psr\\', PMF_SRC_DIR . '/libs/psr/log/Psr');
            $psr4Loader->addPsr4('React\\Promise\\', PMF_SRC_DIR . '/libs/react/promise/src');
            $psr4Loader->register();

            // check LDAP connection
            $esHosts = array_values($esHosts['elasticsearch_server']);
            $esClient = ClientBuilder::create()->setHosts($esHosts)->build();

            if (!$esClient) {
                echo '<p class="alert alert-danger"><strong>Elasticsearch Error:</strong> No connection.</p>';
                System::renderFooter(true);
            }
        } else {
            $esSetup = [];
        }

        // check login name
        if (!isset($setup['loginname'])) {
            $loginName = Filter::filterInput(INPUT_POST, 'loginname', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $loginName = $setup['loginname'];
        }
        if (is_null($loginName)) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> Please add a login name for your account.</p>';
            System::renderFooter(true);
        }

        // check user entries
        if (!isset($setup['password'])) {
            $password = Filter::filterInput(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $password = $setup['password'];
        }
        if (is_null($password)) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> Please add a password for your account.</p>';
            System::renderFooter(true);
        }

        if (!isset($setup['password_retyped'])) {
            $passwordRetyped = Filter::filterInput(INPUT_POST, 'password_retyped', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $passwordRetyped = $setup['password_retyped'];
        }

        if (is_null($passwordRetyped)) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> Please add a retyped password.</p>';
            System::renderFooter(true);
        }

        if (strlen((string) $password) <= 7 || strlen((string) $passwordRetyped) <= 7) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> Your password and retyped password are too ' .
                'short. Please set your password and your retyped password with a minimum of 8 characters.</p>';
            System::renderFooter(true);
        }

        if ($password != $passwordRetyped) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> Your password and retyped password are not ' .
                'equal. Please check your password and your retyped password.</p>';
            System::renderFooter(true);
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
            echo '<p class="alert alert-danger"><strong>Error:</strong> Setup cannot write to ./config/database.php.' .
                '</p>';
            $this->system->cleanFailedInstallationFiles();
            System::renderFooter(true);
        }

        // check LDAP is enabled
        if (extension_loaded('ldap') && !is_null($ldapEnabled) && count($ldapSetup)) {
            if (!$instanceSetup->createLdapFile($ldapSetup, '')) {
                echo '<p class="alert alert-danger"><strong>Error:</strong> Setup cannot write to ./config/ldap.php.' .
                    '</p>';
                $this->system->cleanFailedInstallationFiles();
                System::renderFooter(true);
            }
        }

        // check if Elasticsearch is enabled
        if (!is_null($esEnabled) && count($esSetup)) {
            if (!$instanceSetup->createElasticsearchFile($esSetup, '')) {
                echo '<p class="alert alert-danger"><strong>Error:</strong> Setup cannot write to ' .
                    './config/elasticsearch.php.</p>';
                $this->system->cleanFailedInstallationFiles();
                System::renderFooter(true);
            }
        }

        // connect to the database using config/database.php
        $dbConfig = new DatabaseConfiguration($rootDir . '/config/database.php');
        try {
            $db = Database::factory($dbSetup['dbType']);
        } catch (Exception $exception) {
            printf("<p class=\"alert alert-danger\"><strong>DB Error:</strong> %s</p>\n", $exception->getMessage());
            $this->system->cleanFailedInstallationFiles();
            System::renderFooter(true);
        }

        $db->connect(
            $dbConfig->getServer(),
            $dbConfig->getUser(),
            $dbConfig->getPassword(),
            $dbConfig->getDatabase(),
            $dbConfig->getPort()
        );

        if (!$db) {
            printf("<p class=\"alert alert-danger\"><strong>DB Error:</strong> %s</p>\n", $db->error());
            $this->system->cleanFailedInstallationFiles();
            System::renderFooter(true);
        }

        try {
            $databaseInstaller = InstanceDatabase::factory($configuration, $dbSetup['dbType']);
            $databaseInstaller->createTables($dbSetup['dbPrefix']);
        } catch (Exception $exception) {
            printf("<p class=\"alert alert-danger\"><strong>DB Error:</strong> %s</p>\n", $exception->getMessage());
            $this->system->cleanFailedInstallationFiles();
            System::renderFooter(true);
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
                echo '<p class="alert alert-danger"><strong>Error:</strong> Please install your version of phpMyFAQ 
                    once again or send us a <a href=\"https://www.phpmyfaq.de\" target=\"_blank\">bug report</a>.</p>';
                printf('<p class="alert alert-danger"><strong>DB error:</strong> %s</p>', $db->error());
                printf('<code>%s</code>', htmlentities($executeQuery));
                $this->system->dropTables($uninstall);
                $this->system->cleanFailedInstallationFiles();
                System::renderFooter(true);
            }
            usleep(1000);
            ++$count;
            if (!($count % 10)) {
                echo '| ';
            }
        }

        $link = new Link('', $configuration);

        // add main configuration, add personal settings
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
        $admin = new User($configuration);
        if (!$admin->createUser($loginName, $password, '', 1)) {
            printf(
                '<p class="alert alert-danger"><strong>Fatal installation error:</strong><br>' .
                "Couldn't create the admin user: %s</p>\n",
                $admin->error()
            );
            $this->system->cleanFailedInstallationFiles();
            System::renderFooter(true);
        }
        $admin->setStatus('protected');
        $adminData = [
            'display_name' => $realname,
            'email' => $email,
        ];
        $admin->setUserData($adminData);

        // add default rights
        foreach ($this->mainRights as $right) {
            $admin->perm->grantUserRight(1, $admin->perm->addRight($right));
        }

        // Add an anonymous user account
        $instanceSetup->createAnonymousUser($configuration);

        // Add primary instance
        $instanceData = new InstanceEntity();
        $instanceData
            ->setUrl($link->getSystemUri($_SERVER['SCRIPT_NAME']))
            ->setInstance($link->getSystemRelativeUri('setup/index.php'))
            ->setComment('phpMyFAQ ' . System::getVersion());
        $faqInstance = new Instance($configuration);
        $faqInstance->addInstance($instanceData);

        $faqInstanceMaster = new Master($configuration);
        $faqInstanceMaster->createMaster($faqInstance);

        // connect to Elasticsearch if enabled
        if (!is_null($esEnabled) && is_file($rootDir . '/config/elasticsearch.php')) {
            $esConfig = new ElasticsearchConfiguration($rootDir . '/config/elasticsearch.php');

            $configuration->setElasticsearchConfig($esConfig);

            $esClient = ClientBuilder::create()->setHosts($esConfig->getHosts())->build();

            $configuration->setElasticsearch($esClient);

            $faqInstanceElasticsearch = new Elasticsearch($configuration);
            $faqInstanceElasticsearch->createIndex();
        }
    }
}
