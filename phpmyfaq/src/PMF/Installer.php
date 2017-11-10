<?php

use Symfony\Component\ClassLoader\Psr4ClassLoader;
use Elasticsearch\ClientBuilder;
use Psr\Log\NullLogger;
use GuzzleHttp\Ring\Client\CurlHandler;

/**
 * The Installer class installs phpMyFAQ. Classy.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Florian Anderiasch <florian@phpmyfaq.net>
 * @copyright 2012-2017 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-08-27
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Installer.
 *
 * @category  phpMyFAQ
 * @author    Florian Anderiasch <florian@phpmyfaq.net>
 * @copyright 2012-2017 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-08-27
 */
class PMF_Installer
{
    /**
     * PMF_System object.
     *
     * @var PMF_System
     */
    protected $_system;

    /**
     * Array with user rights.
     *
     * @var array
     */
    protected $_mainRights = array(
        //1 => "adduser",
        array(
            'name' => 'adduser',
            'description' => 'Right to add user accounts',
        ),
        //2 => "edituser",
        array(
            'name' => 'edituser',
            'description' => 'Right to edit user accounts',
        ),
        //3 => "deluser",
        array(
            'name' => 'deluser',
            'description' => 'Right to delete user accounts',
        ),
        //4 => "addbt",
        array(
            'name' => 'addbt',
            'description' => 'Right to add faq entries',
        ),
        //5 => "editbt",
        array(
            'name' => 'editbt',
            'description' => 'Right to edit faq entries',
        ),
        //6 => "delbt",
        array(
            'name' => 'delbt',
            'description' => 'Right to delete faq entries',
        ),
        //7 => "viewlog",
        array(
            'name' => 'viewlog',
            'description' => 'Right to view logfiles',
        ),
        //8 => "adminlog",
        array(
            'name' => 'adminlog',
            'description' => 'Right to view admin log',
        ),
        //9 => "delcomment",
        array(
            'name' => 'delcomment',
            'description' => 'Right to delete comments',
        ),
        //10 => "addnews",
        array(
            'name' => 'addnews',
            'description' => 'Right to add news',
        ),
        //11 => "editnews",
        array(
            'name' => 'editnews',
            'description' => 'Right to edit news',
        ),
        //12 => "delnews",
        array(
            'name' => 'delnews',
            'description' => 'Right to delete news',
        ),
        //13 => "addcateg",
        array(
            'name' => 'addcateg',
            'description' => 'Right to add categories',
        ),
        //14 => "editcateg",
        array(
            'name' => 'editcateg',
            'description' => 'Right to edit categories',
        ),
        //15 => "delcateg",
        array(
            'name' => 'delcateg',
            'description' => 'Right to delete categories',
        ),
        //16 => "passwd",
        array(
            'name' => 'passwd',
            'description' => 'Right to change passwords',
        ),
        //17 => "editconfig",
        array(
            'name' => 'editconfig',
            'description' => 'Right to edit configuration',
        ),
        //18 => "addatt", // Duplicate, removed with 2.7.3
        //array(
        //    'name' => 'addatt',
        //    'description' => 'Right to add attachments'
        //),
        //19 => "backup delatt", // Duplicate, removed with 2.7.3
        //array(
        //    'name' => 'delatt',
        //    'description' => 'Right to delete attachments'
        //),
        //20 => "backup",
        array(
            'name' => 'backup',
            'description' => 'Right to save backups',
        ),
        //21 => "restore",
        array(
            'name' => 'restore',
            'description' => 'Right to load backups',
        ),
        //22 => "delquestion",
        array(
            'name' => 'delquestion',
            'description' => 'Right to delete questions',
        ),
        //23 => 'addglossary',
        array(
            'name' => 'addglossary',
            'description' => 'Right to add glossary entries',
        ),
        //24 => 'editglossary',
        array(
            'name' => 'editglossary',
            'description' => 'Right to edit glossary entries',
        ),
        //25 => 'delglossary'
        array(
            'name' => 'delglossary',
            'description' => 'Right to delete glossary entries',
        ),
        //26 => 'changebtrevs'
        array(
            'name' => 'changebtrevs',
            'description' => 'Right to edit revisions',
        ),
        //27 => "addgroup",
        array(
            'name' => 'addgroup',
            'description' => 'Right to add group accounts',
        ),
        //28 => "editgroup",
        array(
            'name' => 'editgroup',
            'description' => 'Right to edit group accounts',
        ),
        //29 => "delgroup",
        array(
            'name' => 'delgroup',
            'description' => 'Right to delete group accounts',
        ),
        //30 => "addtranslation",
        array(
            'name' => 'addtranslation',
            'description' => 'Right to add translation',
        ),
        //31 => "edittranslation",
        array(
            'name' => 'edittranslation',
            'description' => 'Right to edit translations',
        ),
        //32 => "deltranslation",
        array(
            'name' => 'deltranslation',
            'description' => 'Right to delete translations',
        ),
        // 33 => 'approverec'
        array(
            'name' => 'approverec',
            'description' => 'Right to approve records',
        ),
        // 34 => 'addattachment'
        array(
            'name' => 'addattachment',
            'description' => 'Right to add attachments',
        ),
        // 35 => 'editattachment'
        array(
            'name' => 'editattachment',
            'description' => 'Right to edit attachments',
        ),
        // 36 => 'delattachment'
        array(
            'name' => 'delattachment',
            'description' => 'Right to delete attachments',
        ),
        // 37 => 'dlattachment'
        array(
            'name' => 'dlattachment',
            'description' => 'Right to download attachments',
        ),
        // 38 => 'reports'
        array(
            'name' => 'reports',
            'description' => 'Right to generate reports',
        ),
        // 39 => 'addfaq'
        array(
            'name' => 'addfaq',
            'description' => 'Right to add FAQs in frontend',
        ),
        // 40 => 'addquestion'
        array(
            'name' => 'addquestion',
            'description' => 'Right to add questions in frontend',
        ),
        // 41 => 'addcomment'
        array(
            'name' => 'addcomment',
            'description' => 'Right to add comments in frontend',
        ),
        // 42 => 'editinstances'
        array(
            'name' => 'editinstances',
            'description' => 'Right to edit multi-site instances',
        ),
        // 43 => 'addinstances'
        array(
            'name' => 'addinstances',
            'description' => 'Right to add multi-site instances',
        ),
        // 44 => 'delinstances'
        array(
            'name' => 'delinstances',
            'description' => 'Right to delete multi-site instances',
        ),
        // 45 => 'export'
        array(
            'name' => 'export',
            'description' => 'Right to export the complete FAQ',
        ),
    );

    /**
     * Configuration array.
     *
     * @var array
     */
<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php
    protected $_mainConfig = array(
        'main.currentVersion'                     => null,
        'main.currentApiVersion'                  => null,
        'main.language'                           => '__PHPMYFAQ_LANGUAGE__',
        'main.languageDetection'                  => 'true',
        'main.phpMyFAQToken'                      => null,
        'main.referenceURL'                       => '__PHPMYFAQ_REFERENCE_URL__',
        'main.administrationMail'                 => 'webmaster@example.org',
        'main.contactInformations'                => '',
        'main.enableAdminLog'                     => 'true',
        'main.enableRewriteRules'                 => 'false',
        'main.enableUserTracking'                 => 'true',
        'main.metaDescription'                    => 'phpMyFAQ should be the answer for all questions in life',
        'main.metaKeywords'                       => '',
        'main.metaPublisher'                      => '__PHPMYFAQ_PUBLISHER__',
        'main.send2friendText'                    => '',
        'main.titleFAQ'                           => 'phpMyFAQ Codename Poseidon',
        'main.urlValidateInterval'                => '86400',
        'main.enableWysiwygEditor'                => 'true',
        'main.enableWysiwygEditorFrontend'        => 'false',
        'main.templateSet'                        => 'default',
        'main.optionalMailAddress'                => 'false',
        'main.dateFormat'                         => 'Y-m-d H:i',
        'main.maintenanceMode'                    => 'false',
        'main.enableGravatarSupport'              => 'false',
        'main.enableRssFeeds'                     => 'true',

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
        'records.randomSort'                      => 'false',
        'records.allowCommentsForGuests'          => 'true',
        'records.allowQuestionsForGuests'         => 'true',
        'records.allowNewFaqsForGuests'           => 'true',
        'records.hideEmptyCategories'             => 'false',

        'search.useAjaxSearchOnStartpage'         => 'false',
        'search.numberSearchTerms'                => '10',
        'search.relevance'                        => 'thema,content,keywords',
        'search.enableRelevance'                  => 'false',
        'search.enableHighlighting'               => 'true',
        'search.searchForSolutionId'              => 'true',

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
        'spam.enableCaptchaCode'                  => null,
        'spam.enableSafeEmail'                    => 'true',

        'socialnetworks.enableTwitterSupport'     => 'false',
        'socialnetworks.twitterConsumerKey'       => '',
        'socialnetworks.twitterConsumerSecret'    => '',
        'socialnetworks.twitterAccessTokenKey'    => '',
=======
    protected $_mainConfig = [
        'main.currentVersion' => null,
        'main.currentApiVersion' => null,
        'main.language' => '__PHPMYFAQ_LANGUAGE__',
        'main.languageDetection' => 'true',
        'main.phpMyFAQToken' => null,
        'main.referenceURL' => '__PHPMYFAQ_REFERENCE_URL__',
        'main.administrationMail' => 'webmaster@example.org',
        'main.contactInformations' => '',
        'main.enableAdminLog' => 'true',
        'main.enableRewriteRules' => 'false',
        'main.enableUserTracking' => 'true',
        'main.metaDescription' => 'phpMyFAQ should be the answer for all questions in life',
        'main.metaKeywords' => '',
        'main.metaPublisher' => '__PHPMYFAQ_PUBLISHER__',
        'main.send2friendText' => '',
        'main.titleFAQ' => 'phpMyFAQ Codename Phobos',
        'main.urlValidateInterval' => '86400',
        'main.enableWysiwygEditor' => 'true',
        'main.enableWysiwygEditorFrontend' => 'false',
        'main.enableMarkdownEditor' => 'false',
        'main.templateSet' => 'default',
        'main.optionalMailAddress' => 'false',
        'main.dateFormat' => 'Y-m-d H:i',
        'main.maintenanceMode' => 'false',
        'main.enableGravatarSupport' => 'false',
        'main.enableRssFeeds' => 'true',
        'main.enableGzipCompression' => 'true',
        'main.enableLinkVerification' => 'true',
        'main.customPdfHeader' => '',
        'main.customPdfHFooter' => '',
        'main.enableSmartAnswering' => 'true',

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
        'records.autosaveActive' => 'false',
        'records.autosaveSecs' => '180',
        'records.randomSort' => 'false',
        'records.allowCommentsForGuests' => 'true',
        'records.allowQuestionsForGuests' => 'true',
        'records.allowNewFaqsForGuests' => 'true',
        'records.hideEmptyCategories' => 'false',
        'records.allowDownloadsForGuests' => 'false',
        'records.numberMaxStoredRevisions' => '10',

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

        'spam.checkBannedWords' => 'true',
        'spam.enableCaptchaCode' => null,
        'spam.enableSafeEmail' => 'true',
        'spam.manualActivation' => 'true',

        'socialnetworks.enableTwitterSupport' => 'false',
        'socialnetworks.twitterConsumerKey' => '',
        'socialnetworks.twitterConsumerSecret' => '',
        'socialnetworks.twitterAccessTokenKey' => '',
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php
        'socialnetworks.twitterAccessTokenSecret' => '',
        'socialnetworks.enableFacebookSupport' => 'false',
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
        'ldap.ldap_dynamic_login_attribute' => 'uid'
    ];

    /**
     * Constructor.
     *
     * @return PMF_Installer
     */
    public function __construct()
    {
        $this->_system = new PMF_System();
        $dynMainConfig = array(
            'main.currentVersion' => PMF_System::getVersion(),
            'main.currentApiVersion' => PMF_System::getApiVersion(),
            'main.phpMyFAQToken' => md5(uniqid(rand())),
            'spam.enableCaptchaCode' => (extension_loaded('gd') ? 'true' : 'false'),
        );
        $this->_mainConfig = array_merge($this->_mainConfig, $dynMainConfig);
    }

    /**
<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php
     * Check absolutely necessary stuff and die
     *
     * @return array
=======
     * Check absolutely necessary stuff and die.
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php
     */
    public function checkBasicStuff()
    {
        $errors = array();

        if (!$this->checkMinimumPhpVersion()) {
<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php
            $errors[] = sprintf(
                'Sorry, but you need PHP %s or later!',
                PMF_System::VERSION_MINIMUM_PHP
            );
        }

        if (! function_exists('date_default_timezone_set')) {
            $errors[] = 'Sorry, but setting a default timezone doesn\'t work in your environment!';
        }

        if (! $this->_system->checkDatabase()) {
            $dbError = "No supported database detected! Please install one of the following database systems and " .
                        "enable the corresponding PHP extension in php.ini:";
            $dbError .= "<ul>";
=======
            printf(
                '<p class="alert alert-danger">Sorry, but you need PHP %s or later!</p>',
                PMF_System::VERSION_MINIMUM_PHP
            );
            PMF_System::renderFooter();
        }

        if (!function_exists('date_default_timezone_set')) {
            echo '<p class="alert alert-danger">Sorry, but setting a default timezone doesn\'t work in your environment!</p>';
            PMF_System::renderFooter();
        }

        if (!$this->_system->checkDatabase()) {
            echo '<p class="alert alert-danger">No supported database detected! Please install one of the following'.
                ' database systems and enable the corresponding PHP extension in php.ini:</p>';
            echo '<ul>';
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php
            foreach ($this->_system->getSupportedDatabases() as $database) {
                $dbError .= sprintf("    <li>%s</li>\n", $database[1]);
            }
            $dbError .= "</ul>";
            $errors[] = $dbError;
        }

<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php
        if (! $this->_system->checkRequiredExtensions()) {
            $extError  = "The following extensions are missing! Please enable the PHP extension(s) in php.ini.";
            $extError .= "<ul>";
=======
        if (!$this->_system->checkRequiredExtensions()) {
            echo '<p class="alert alert-danger">The following extensions are missing! Please enable the PHP extension(s) in '.
                'php.ini.</p>';
            echo '<ul>';
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php
            foreach ($this->_system->getMissingExtensions() as $extension) {
                $extError .= sprintf("    <li>ext/%s</li>\n", $extension);
            }
            $extError .= "</ul>";
            $errors[] = $extError;
        }

<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php
        if (! $this->_system->checkRegisterGlobals()) {
            $errors[] = "Please disable register_globals!";
        }

        if (! $this->_system->checkMagicQuotesGpc()) {
            $errors[] = "Please disable magic_quotes_gpc!";
        }

        if (! $this->_system->checkphpMyFAQInstallation()) {
            $errors[] = "It seems you're already running a version of phpMyFAQ. Please use the " .
                        "<a href=\"update.php\">update script</a>.";
=======
        if (!$this->_system->checkphpMyFAQInstallation()) {
            echo '<p class="alert alert-danger">It seems you\'re already running a version of phpMyFAQ. Please use the '.
                '<a href="update.php">update script</a>.</p>';
            PMF_System::renderFooter();
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php
        }

        return $errors;
    }

    /**
     * Checks for the minimum PHP requirement and if the database credentials file is readable.
     *
     * @param string $type
     *
     * @return void
     */
    public function checkPreUpgrade($type = '')
    {
        if (!$this->checkMinimumPhpVersion()) {
            printf(
                '<p class="alert alert-danger">Sorry, but you need PHP %s or later!</p>',
                PMF_System::VERSION_MINIMUM_PHP
            );
            PMF_System::renderFooter();
        }

<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php
        if (! is_readable(PMF_ROOT_DIR . '/config/database.php')) {
            echo '<p class="alert alert-error">It seems you never run a version of phpMyFAQ.<br />' .
=======
        if (!is_readable(PMF_ROOT_DIR.'/inc/data.php') && !is_readable(PMF_ROOT_DIR.'/config/database.php')) {
            echo '<p class="alert alert-danger">It seems you never run a version of phpMyFAQ.<br>'.
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php
                'Please use the <a href="setup.php">install script</a>.</p>';
            PMF_System::renderFooter();
        }

        if ('' !== $type) {
            $databaseFound = false;
            foreach ($this->_system->getSupportedDatabases() as $database => $values) {
                if ($database === $type) {
                    $databaseFound = true;
                    break;
                }
            }
            if (!$databaseFound) {
                echo '<p class="alert alert-danger">It seems you\'re using an unsupported database version.<br>'.
                    'We found '.ucfirst($database).'<br>'.
                    'Please use the change the database type in config/database.php.</p>';
                PMF_System::renderFooter();
            }
        }
    }

    /**
     * Checks the minimum required PHP version, defined in PMF_System.
     *
     * @return bool
     */
    public function checkMinimumPhpVersion()
    {
        if (version_compare(PHP_VERSION, PMF_System::VERSION_MINIMUM_PHP, '<')) {
            return false;
        }

        return true;
    }

    /**
<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php
     * Checks if the file permissions are okay
     *
     * @return string
=======
     * Checks if the file permissions are okay.
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php
     */
    public function checkFilesystemPermissions()
    {
        $instanceSetup = new PMF_Instance_Setup();
        $instanceSetup->setRootDir(PMF_ROOT_DIR);

<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php
        $permError  = '';
        $dirs       = array('/attachments', '/config', '/data', '/images');
=======
        $dirs = array('/attachments', '/config', '/data', '/images');
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php
        $failedDirs = $instanceSetup->checkDirs($dirs);
        $numDirs = sizeof($failedDirs);

        if (1 <= $numDirs) {
            $permError = sprintf(
                'The following %s could not be created or %s not writable:<ul>',
                (1 < $numDirs) ? 'directories' : 'directory',
                (1 < $numDirs) ? 'are' : 'is'
            );
            foreach ($failedDirs as $dir) {
                $permError .= sprintf("<li>%s</li>\n", $dir);
            }
<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php
            $permError .= sprintf(
                "</ul>Please create %s manually and/or change access to chmod 755 (or greater if necessary).",
=======
            printf(
                '</ul><p class="alert alert-danger">Please create %s manually and/or change access to chmod 775 (or '.
                    'greater if necessary).</p>',
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php
                (1 < $numDirs) ? 'them' : 'it'
            );
        }

        return $permError;
    }

    /**
     * Checks some non critical settings and print some hints.
     *
<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php
     * @return array
=======
     * @todo We should return an array of messages
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php
     */
    public function checkNoncriticalSettings()
    {
        $errors = array();

        if ((@ini_get('safe_mode') == 'On' || @ini_get('safe_mode') === 1)) {
<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php
            $errors[] = "The PHP safe mode is enabled. You may have problems when phpMyFAQ tries to write in some " .
                        "directories.";
        }
        if (! extension_loaded('gd')) {
            $errors[] = "You don't have GD support enabled in your PHP installation. Please enable GD support in " .
                        "your php.ini file otherwise you can't use Captchas for spam protection.";
        }
        if (! function_exists('imagettftext')) {
            $errors[] = "You don't have Freetype support enabled in the GD extension of your PHP installation. " .
                        "Please enable Freetype support in GD extension otherwise the Captchas for spam protection " .
                        "will be quite easy to break.";
        }
        if (! extension_loaded('curl') || ! extension_loaded('openssl')) {
            $errors[] = "You don't have cURL and/or OpenSSL support enabled in your PHP installation. Please enable " .
                        "cURL and/or OpenSSL support in your php.ini file otherwise you can't use the Twitter support.";
        }
        if (! extension_loaded('fileinfo')) {
            $errors[] = "You don't have Fileinfo support enabled in your PHP installation. Please enable Fileinfo " .
                        "support in your php.ini file otherwise you can't use our backup/restore functionality.";
=======
            echo '<p class="alert alert-danger">The PHP safe mode is enabled. You may have problems when phpMyFAQ tries to write '.
                ' in some directories.</p>';
        }
        if (!extension_loaded('gd')) {
            echo '<p class="alert alert-danger">You don\'t have GD support enabled in your PHP installation. Please enable GD '.
                'support in your php.ini file otherwise you can\'t use Captchas for spam protection.</p>';
        }
        if (!function_exists('imagettftext')) {
            echo '<p class="alert alert-danger">You don\'t have Freetype support enabled in the GD extension of your PHP '.
                'installation. Please enable Freetype support in GD extension otherwise the Captchas for spam '.
                'protection will be quite easy to break.</p>';
        }
        if (!extension_loaded('curl') || !extension_loaded('openssl')) {
            echo '<p class="alert alert-danger">You don\'t have cURL and/or OpenSSL support enabled in your PHP installation. '.
                'Please enable cURL and/or OpenSSL support in your php.ini file otherwise you can\'t use the Twitter '.
                ' support or Elasticsearch.</p>';
        }
        if (!extension_loaded('fileinfo')) {
            echo '<p class="alert alert-danger">You don\'t have Fileinfo support enabled in your PHP installation. '.
                'Please enable Fileinfo support in your php.ini file otherwise you can\'t use our backup/restore '.
                'functionality.</p>';
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php
        }
        return $errors;
    }

    /**
     * Checks if we can store data via sessions. If not, e.g. an user can't
     * login into the admin section.
     *
     * @return bool
     */
    public function checkSessionSettings()
    {
        return true;
    }

    /**
     * Starts the installation.
     *
     * @param array $setup
     */
    public function startInstall(Array $setup = null)
    {
        $query = $uninst = $dbSetup = [];

        // Check table prefix
        $dbSetup['dbPrefix'] = PMF_Filter::filterInput(INPUT_POST, 'sqltblpre', FILTER_SANITIZE_STRING, '');
        if ('' !== $dbSetup['dbPrefix']) {
            PMF_Db::setTablePrefix($dbSetup['dbPrefix']);
        }

        // Check database entries
        $dbSetup['dbType'] = PMF_Filter::filterInput(INPUT_POST, 'sql_type', FILTER_SANITIZE_STRING, $setup['dbType']);
        if (!is_null($dbSetup['dbType'])) {
            $dbSetup['dbType'] = trim($dbSetup['dbType']);
<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php
            if (! file_exists(PMF_ROOT_DIR . '/setup/assets/sql/' . $dbSetup['dbType'] . '.sql.php')) {
=======
            if (!file_exists(PMF_INCLUDE_DIR.'/PMF/Instance/Database/'.ucfirst($dbSetup['dbType']).'.php')) {
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php
                printf(
                    '<p class="alert alert-danger"><strong>Error:</strong> Invalid server type: %s</p>',
                    $dbSetup['dbType']
                );
                PMF_System::renderFooter(true);
            }
        } else {
            echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please select a database type.</p>\n";
            PMF_System::renderFooter(true);
        }

        $dbSetup['dbServer'] = PMF_Filter::filterInput(INPUT_POST, 'sql_server', FILTER_SANITIZE_STRING, '');
        if (is_null($dbSetup['dbServer']) && !PMF_System::isSqlite($dbSetup['dbType'])) {
            echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add a database server.</p>\n";
            PMF_System::renderFooter(true);
        }

<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php
        $dbSetup['dbPort'] = PMF_Filter::filterInput(INPUT_POST, 'sql_port', FILTER_VALIDATE_INT);
        if (is_null($dbSetup['dbPort']) && ! PMF_System::isSqlite($dbSetup['dbType'])) {
            echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a valid database port.</p>\n";
            PMF_System::renderFooter(true);
        }

        $dbSetup['dbUser'] = PMF_Filter::filterInput(INPUT_POST, 'sql_user', FILTER_SANITIZE_STRING);
        if (is_null($dbSetup['dbUser']) && ! PMF_System::isSqlite($dbSetup['dbType'])) {
=======
        $dbSetup['dbUser'] = PMF_Filter::filterInput(INPUT_POST, 'sql_user', FILTER_SANITIZE_STRING, '');
        if (is_null($dbSetup['dbUser']) && !PMF_System::isSqlite($dbSetup['dbType'])) {
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php
            echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add a database username.</p>\n";
            PMF_System::renderFooter(true);
        }

        $dbSetup['dbPassword'] = PMF_Filter::filterInput(INPUT_POST, 'sql_password', FILTER_UNSAFE_RAW, '');
        if (is_null($dbSetup['dbPassword']) && !PMF_System::isSqlite($dbSetup['dbType'])) {
            // Password can be empty...
            $dbSetup['dbPassword'] = '';
        }

        $dbSetup['dbDatabaseName'] = PMF_Filter::filterInput(INPUT_POST, 'sql_db', FILTER_SANITIZE_STRING);
        if (is_null($dbSetup['dbDatabaseName']) && !PMF_System::isSqlite($dbSetup['dbType'])) {
            echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add a database name.</p>\n";
            PMF_System::renderFooter(true);
        }

        if (PMF_System::isSqlite($dbSetup['dbType'])) {
            $dbSetup['dbServer'] = PMF_Filter::filterInput(
                INPUT_POST,
                'sql_sqlitefile',
                FILTER_SANITIZE_STRING,
                $setup['dbServer']
            );
            if (is_null($dbSetup['dbServer'])) {
                echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add a SQLite database filename.</p>\n";
                PMF_System::renderFooter(true);
            }
        }

        // check database connection
        PMF_Db::setTablePrefix($dbSetup['dbPrefix']);
        $db = PMF_Db::factory($dbSetup['dbType']);
        try {
            $db->connect($dbSetup['dbServer'], $dbSetup['dbUser'], $dbSetup['dbPassword'], $dbSetup['dbDatabaseName']);
        } catch (PMF_Exception $e) {
            printf("<p class=\"alert alert-danger\"><strong>DB Error:</strong> %s</p>\n", $e->getMessage());
        }
        if (!$db) {
            PMF_System::renderFooter(true);
        }

        $configuration = new PMF_Configuration($db);

        //
        // Check LDAP if enabled
        //
        $ldapEnabled = PMF_Filter::filterInput(INPUT_POST, 'ldap_enabled', FILTER_SANITIZE_STRING);
        if (extension_loaded('ldap') && !is_null($ldapEnabled)) {
            $ldapSetup = [];

            // check LDAP entries
            $ldapSetup['ldapServer'] = PMF_Filter::filterInput(INPUT_POST, 'ldap_server', FILTER_SANITIZE_STRING);
            if (is_null($ldapSetup['ldapServer'])) {
                echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add a LDAP server.</p>\n";
                PMF_System::renderFooter(true);
            }

            $ldapSetup['ldapPort'] = PMF_Filter::filterInput(INPUT_POST, 'ldap_port', FILTER_VALIDATE_INT);
            if (is_null($ldapSetup['ldapPort'])) {
                echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add a LDAP port.</p>\n";
                PMF_System::renderFooter(true);
            }

            $ldapSetup['ldapBase'] = PMF_Filter::filterInput(INPUT_POST, 'ldap_base', FILTER_SANITIZE_STRING);
            if (is_null($ldapSetup['ldapBase'])) {
                echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add a LDAP base search DN.</p>\n";
                PMF_System::renderFooter(true);
            }

            // LDAP User and LDAP password are optional
            $ldapSetup['ldapUser'] = PMF_Filter::filterInput(INPUT_POST, 'ldap_user', FILTER_SANITIZE_STRING, '');
            $ldapSetup['ldapPassword'] = PMF_Filter::filterInput(INPUT_POST, 'ldap_password', FILTER_SANITIZE_STRING, '');

            // check LDAP connection
            require PMF_ROOT_DIR.'/src/PMF/Ldap.php';
            $ldap = new PMF_Ldap($configuration);
            $ldap->connect(
                $ldapSetup['ldapServer'],
                $ldapSetup['ldapPort'],
                $ldapSetup['ldapBase'],
                $ldapSetup['ldapUser'],
                $ldapSetup['ldapPassword']
            );
            if (!$ldap) {
                echo '<p class="alert alert-danger"><strong>LDAP Error:</strong> '.$ldap->error()."</p>\n";
                PMF_System::renderFooter(true);
            }
        }

        //
        // Check Elasticsearch if enabled
        //
        $esEnabled = PMF_Filter::filterInput(INPUT_POST, 'elasticsearch_enabled', FILTER_SANITIZE_STRING);
        if (!is_null($esEnabled)) {
            $esSetup = [];
            $esHostFilter = [
                'elasticsearch_server' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'flags' => FILTER_REQUIRE_ARRAY
                ]
            ];

            // ES hosts
            $esHosts = PMF_Filter::filterInputArray(INPUT_POST, $esHostFilter);
            if (is_null($esHosts)) {
                echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add at least one Elasticsearch host.</p>\n";
                PMF_System::renderFooter(true);
            }

            $esSetup['hosts'] = $esHosts['elasticsearch_server'];

            // ES Index name
            $esSetup['index'] = PMF_Filter::filterInput(INPUT_POST, 'elasticsearch_index', FILTER_SANITIZE_STRING);
            if (is_null($esSetup['index'])) {
                echo "<p class=\"alert alert-danger\"><strong>Error:</strong> Please add an Elasticsearch index name.</p>\n";
                PMF_System::renderFooter(true);
            }

            require_once PMF_INCLUDE_DIR.'/libs/react/promise/src/functions.php';

            $psr4Loader = new Psr4ClassLoader();
            $psr4Loader->addPrefix('Elasticsearch', PMF_INCLUDE_DIR.'/libs/elasticsearch/src/Elasticsearch');
            $psr4Loader->addPrefix('GuzzleHttp\\Ring\\', PMF_INCLUDE_DIR.'/libs/guzzlehttp/ringphp/src');
            $psr4Loader->addPrefix('Monolog', PMF_INCLUDE_DIR.'/libs/monolog/src/Monolog');
            $psr4Loader->addPrefix('Psr', PMF_INCLUDE_DIR.'/libs/psr/log/Psr');
            $psr4Loader->addPrefix('React\\Promise\\', PMF_INCLUDE_DIR.'/libs/react/promise/src');
            $psr4Loader->register();

            // check LDAP connection
            $esHosts = array_values($esHosts['elasticsearch_server']);
            $esClient = ClientBuilder::create()
                ->setHosts($esHosts)
                ->build();

            if (!$esClient) {
                echo '<p class="alert alert-danger"><strong>Elasticsearch Error:</strong> No connection.</p>';
                PMF_System::renderFooter(true);
            }
        } else {
            $esSetup = [];
        }

        // check loginname
        $loginname = PMF_Filter::filterInput(INPUT_POST, 'loginname', FILTER_SANITIZE_STRING, $setup['loginname']);
        if (is_null($loginname)) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> Please add a loginname for your account.</p>';
            PMF_System::renderFooter(true);
        }

        // check user entries
        $password = PMF_Filter::filterInput(INPUT_POST, 'password', FILTER_SANITIZE_STRING, $setup['password']);
        if (is_null($password)) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> Please add a password for the your account.</p>';
            PMF_System::renderFooter(true);
        }

        $password_retyped = PMF_Filter::filterInput(
            INPUT_POST,
            'password_retyped',
            FILTER_SANITIZE_STRING,
            $setup['password_retyped']
        );
        if (is_null($password_retyped)) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> Please add a retyped password.</p>';
            PMF_System::renderFooter(true);
        }

        if (strlen($password) <= 5 || strlen($password_retyped) <= 5) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> Your password and retyped password are too short.'.
                ' Please set your password and your retyped password with a minimum of 6 characters.</p>';
            PMF_System::renderFooter(true);
        }
        if ($password != $password_retyped) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> Your password and retyped password are not equal.'.
                ' Please check your password and your retyped password.</p>';
            PMF_System::renderFooter(true);
        }

        $language = PMF_Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_STRING, 'en');
        $realname = PMF_Filter::filterInput(INPUT_POST, 'realname', FILTER_SANITIZE_STRING, '');
        $email = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL, '');
        $permLevel = PMF_Filter::filterInput(INPUT_POST, 'permLevel', FILTER_SANITIZE_STRING, 'basic');

        $rootDir = isset($setup['rootDir']) ? $setup['rootDir'] : PMF_ROOT_DIR;

        $instanceSetup = new PMF_Instance_Setup();
        $instanceSetup->setRootDir($rootDir);

        // Write the DB variables in database.php
        if (!$instanceSetup->createDatabaseFile($dbSetup)) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> Setup cannot write to ./config/database.php.</p>';
            $this->_system->cleanInstallation();
            PMF_System::renderFooter(true);
        }

        // check LDAP is enabled
        if (extension_loaded('ldap') && !is_null($ldapEnabled) && count($ldapSetup)) {
            if (!$instanceSetup->createLdapFile($ldapSetup, '')) {
                echo '<p class="alert alert-danger"><strong>Error:</strong> Setup cannot write to ./config/ldap.php.</p>';
                $this->_system->cleanInstallation();
                PMF_System::renderFooter(true);
            }
        }

        // check if Elasticsearch is enabled
        if (!is_null($esEnabled) && count($esSetup)) {
            if (!$instanceSetup->createElasticsearchFile($esSetup, '')) {
                echo '<p class="alert alert-danger"><strong>Error:</strong> Setup cannot write to ./config/elasticsearch.php.</p>';
                $this->_system->cleanInstallation();
                PMF_System::renderFooter(true);
            }
        }

        // connect to the database using config/database.php
        require $rootDir.'/config/database.php';
        $db = PMF_Db::factory($dbSetup['dbType']);
        $db->connect($DB['server'], $DB['user'], $DB['password'], $DB['db']);
        if (!$db) {
            printf("<p class=\"alert alert-danger\"><strong>DB Error:</strong> %s</p>\n", $db->error());
            $this->_system->cleanInstallation();
            PMF_System::renderFooter(true);
        }

<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php
        require PMF_ROOT_DIR . '/setup/assets/sql/' . $dbSetup['dbType'] . '.sql.php'; // CREATE TABLES
        require PMF_ROOT_DIR . '/setup/assets/sql/stopwords.sql.php';  // INSERTs for stopwords
=======
        $databaseInstaller = PMF_Instance_Database::factory($configuration, $dbSetup['dbType']);
        $databaseInstaller->createTables($dbSetup['dbPrefix']);

        $stopwords = new PMF_Instance_Database_Stopwords($configuration);
        $stopwords->executeInsertQueries($dbSetup['dbPrefix']);
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php

        $this->_system->setDatabase($db);

        echo '<p>';

        // Erase any table before starting creating the required ones
        if (!PMF_System::isSqlite($dbSetup['dbType'])) {
            $this->_system->dropTables($uninst);
        }

        // Start creating the required tables
        $count = 0;
        foreach ($query as $executeQuery) {
            $result = @$db->query($executeQuery);
            if (!$result) {
                echo '<p class="alert alert-danger"><strong>Error:</strong> Please install your version of phpMyFAQ once again or send
            us a <a href=\"http://www.phpmyfaq.de\" target=\"_blank\">bug report</a>.</p>';
                printf('<p class="alert alert-danger"><strong>DB error:</strong> %s</p>', $db->error());
                printf('<code>%s</code>', htmlentities($executeQuery));
                $this->_system->dropTables($uninst);
                $this->_system->cleanInstallation();
                PMF_System::renderFooter(true);
            }
            usleep(1000);
            ++$count;
            if (!($count % 10)) {
                echo '| ';
            }
        }

        $link = new PMF_Link(null, $configuration);

        // add main configuration, add personal settings
        $this->_mainConfig['main.metaPublisher'] = $realname;
        $this->_mainConfig['main.administrationMail'] = $email;
        $this->_mainConfig['main.language'] = $language;
        $this->_mainConfig['security.permLevel'] = $permLevel;

        foreach ($this->_mainConfig as $name => $value) {
            $configuration->add($name, $value);
        }

        $configuration->update(array('main.referenceURL' => $link->getSystemUri('/setup/index.php')));
        $configuration->add('security.salt', md5($configuration->getDefaultUrl()));

        // add admin account and rights
        $admin = new PMF_User($configuration);
        if (!$admin->createUser($loginname, $password, 1)) {
            printf(
                '<p class="alert alert-danger"><strong>Fatal installation error:</strong><br>'.
                "Couldn't create the admin user: %s</p>\n",
                $admin->error()
            );
            $this->_system->cleanInstallation();
            PMF_System::renderFooter(true);
        }
        $admin->setStatus('protected');
        $adminData = array(
            'display_name' => $realname,
            'email' => $email,
        );
        $admin->setUserData($adminData);

        // add default rights
        foreach ($this->_mainRights as $right) {
            $admin->perm->grantUserRight(1, $admin->perm->addRight($right));
        }

        // Add anonymous user account
        $instanceSetup->createAnonymousUser($configuration);

        // Add master instance
        $instanceData = array(
            'url' => $link->getSystemUri($_SERVER['SCRIPT_NAME']),
            'instance' => $link->getSystemRelativeUri('setup/index.php'),
            'comment' => 'phpMyFAQ '.PMF_System::getVersion(),
        );
        $faqInstance = new PMF_Instance($configuration);
        $faqInstance->addInstance($instanceData);

        $faqInstanceMaster = new PMF_Instance_Master($configuration);
        $faqInstanceMaster->createMaster($faqInstance);

        // connect to Elasticsearch if enabled
        if (!is_null($esEnabled) && is_file($rootDir.'/config/elasticsearch.php')) {
            require $rootDir.'/config/elasticsearch.php';

            $configuration->setElasticsearchConfig($PMF_ES);

            $esClient = ClientBuilder::create()
                ->setHosts($PMF_ES['hosts'])
                ->build();

            $configuration->setElasticsearch($esClient);

            $faqInstanceElasticsearch = new PMF_Instance_Elasticsearch($configuration);
            $faqInstanceElasticsearch->createIndex();
        }

        echo '</p>';
    }

    /**
     * Cleanup all files after an installation.
     *
     * @return void
     */
    public function cleanUpFiles()
    {
        // Remove 'index.php' file
        if (@unlink(dirname($_SERVER['PATH_TRANSLATED']).'/index.php')) {
            echo "<p class=\"alert alert-success\">The file <em>./setup/index.php</em> was deleted automatically.</p>\n";
        } else {
            echo "<p class=\"alert alert-danger\">Please delete the file <em>./setup/index.php</em> manually.</p>\n";
        }
        // Remove 'update.php' file
        if (@unlink(dirname($_SERVER['PATH_TRANSLATED']).'/update.php')) {
            echo "<p class=\"alert alert-success\">The file <em>./setup/update.php</em> was deleted automatically.</p>\n";
        } else {
            echo "<p class=\"alert alert-danger\">Please delete the file <em>./setup/update.php</em> manually.</p>\n";
        }
    }
<<<<<<< HEAD:phpmyfaq/inc/PMF/Installer.php

    /**
     * Renders the <option> list with supported languages
     *
     * @param array $languageCodes
     *
     * @return string
     */
    public function renderLanguageOptions(Array $languageCodes)
    {
        $options = '';
        if ($dir = @opendir(PMF_ROOT_DIR . '/lang')) {
            while ($dat = @readdir($dir)) {
                if (substr($dat, -4) == '.php') {
                    $options .= sprintf('<option value="%s"', $dat);
                    if ($dat == "language_en.php") {
                        $options .= ' selected';
                    }
                    $options .= sprintf(
                        '>%s</option>',
                        $languageCodes[substr(strtoupper($dat), 9, 2)]
                    );
                }
            }
        } else {
            $options = '<option>English</option>';
        }

        return $options;
    }
}
=======
}
>>>>>>> 2.10:phpmyfaq/src/PMF/Installer.php
