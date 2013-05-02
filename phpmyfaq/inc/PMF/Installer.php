<?php
/**
 * The Installer class installs phpMyFAQ. Classy.
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Installer
 * @author    Florian Anderiasch <florian@phpmyfaq.net>
 * @copyright 2002-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-08-27
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Installer
 *
 * @category  phpMyFAQ
 * @package   Installer
 * @author    Florian Anderiasch <florian@phpmyfaq.net>
 * @copyright 2002-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-08-27
 */

class PMF_Installer
{
    /**
     * PMF_System object
     *
     * @var PMF_System
     */
    protected $_system;

    /**
     * Array with user rights
     * @var array
     */
    protected $_mainRights = array(
        //1 => "adduser",
        array(
            'name' => 'adduser',
            'description' => 'Right to add user accounts'
        ),
        //2 => "edituser",
        array(
            'name' => 'edituser',
            'description' => 'Right to edit user accounts'
        ),
        //3 => "deluser",
        array(
            'name' => 'deluser',
            'description' => 'Right to delete user accounts'
        ),
        //4 => "addbt",
        array(
            'name' => 'addbt',
            'description' => 'Right to add faq entries'
        ),
        //5 => "editbt",
        array(
            'name' => 'editbt',
            'description' => 'Right to edit faq entries'
        ),
        //6 => "delbt",
        array(
            'name' => 'delbt',
            'description' => 'Right to delete faq entries'
        ),
        //7 => "viewlog",
        array(
            'name' => 'viewlog',
            'description' => 'Right to view logfiles'
        ),
        //8 => "adminlog",
        array(
            'name' => 'adminlog',
            'description' => 'Right to view admin log'
        ),
        //9 => "delcomment",
        array(
            'name' => 'delcomment',
            'description' => 'Right to delete comments'
        ),
        //10 => "addnews",
        array(
            'name' => 'addnews',
            'description' => 'Right to add news'
        ),
        //11 => "editnews",
        array(
            'name' => 'editnews',
            'description' => 'Right to edit news'
        ),
        //12 => "delnews",
        array(
            'name' => 'delnews',
            'description' => 'Right to delete news'
        ),
        //13 => "addcateg",
        array(
            'name' => 'addcateg',
            'description' => 'Right to add categories'
        ),
        //14 => "editcateg",
        array(
            'name' => 'editcateg',
            'description' => 'Right to edit categories'
        ),
        //15 => "delcateg",
        array(
            'name' => 'delcateg',
            'description' => 'Right to delete categories'
        ),
        //16 => "passwd",
        array(
            'name' => 'passwd',
            'description' => 'Right to change passwords'
        ),
        //17 => "editconfig",
        array(
            'name' => 'editconfig',
            'description' => 'Right to edit configuration'
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
            'description' => 'Right to save backups'
        ),
        //21 => "restore",
        array(
            'name' => 'restore',
            'description' => 'Right to load backups'
        ),
        //22 => "delquestion",
        array(
            'name' => 'delquestion',
            'description' => 'Right to delete questions'
        ),
        //23 => 'addglossary',
        array(
            'name' => 'addglossary',
            'description' => 'Right to add glossary entries'
        ),
        //24 => 'editglossary',
        array(
            'name' => 'editglossary',
            'description' => 'Right to edit glossary entries'
        ),
        //25 => 'delglossary'
        array(
            'name' => 'delglossary',
            'description' => 'Right to delete glossary entries'
        ),
        //26 => 'changebtrevs'
        array(
            'name' => 'changebtrevs',
            'description' => 'Right to edit revisions'
        ),
        //27 => "addgroup",
        array(
            'name' => 'addgroup',
            'description' => 'Right to add group accounts'
        ),
        //28 => "editgroup",
        array(
            'name' => 'editgroup',
            'description' => 'Right to edit group accounts'
        ),
        //29 => "delgroup",
        array(
            'name' => 'delgroup',
            'description' => 'Right to delete group accounts'
        ),
        //30 => "addtranslation",
        array(
            'name' => 'addtranslation',
            'description' => 'Right to add translation'
        ),
        //31 => "edittranslation",
        array(
            'name' => 'edittranslation',
            'description' => 'Right to edit translations'
        ),
        //32 => "deltranslation",
        array(
            'name' => 'deltranslation',
            'description' => 'Right to delete translations'
        ),
        // 33 => 'approverec'
        array(
            'name' => 'approverec',
            'description' => 'Right to approve records'
        ),
        // 34 => 'addattachment'
        array(
            'name' => 'addattachment',
            'description' => 'Right to add attachments'
        ),
        // 35 => 'editattachment'
        array(
            'name' => 'editattachment',
            'description' => 'Right to edit attachments'
        ),
        // 36 => 'delattachment'
        array(
            'name' => 'delattachment',
            'description' => 'Right to delete attachments'
        ),
        // 37 => 'dlattachment'
        array(
            'name' => 'dlattachment',
            'description' => 'Right to download attachments'
        ),
        // 38 => 'dlattachment'
        array(
            'name' => 'reports',
            'description' => 'Right to generate reports'
        ),
        // 39 => 'addfaq'
        array(
            'name' => 'addfaq',
            'description' => 'Right to add FAQs in frontend'
        ),
        // 40 => 'addquestion'
        array(
            'name' => 'addquestion',
            'description' => 'Right to add questions in frontend'
        ),
        // 41 => 'addcomment'
        array(
            'name' => 'addcomment',
            'description' => 'Right to add comments in frontend'
        ),
        // 42 => 'editinstances'
        array(
            'name' => 'editinstances',
            'description' => 'Right to edit multi-site instances'
        ),
        // 43 => 'addinstances'
        array(
            'name' => 'addinstances',
            'description' => 'Right to add multi-site instances'
        ),
        // 44 => 'delinstances'
        array(
            'name' => 'delinstances',
            'description' => 'Right to delete multi-site instances'
        ),
        // 45 => 'export'
        array(
            'name' => 'export',
            'description' => 'Right to export the complete FAQ'
        ),
    );

    /**
     * Configuration array
     *
     * @var array
     */
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
        'main.titleFAQ'                           => 'phpMyFAQ Codename Proteus',
        'main.urlValidateInterval'                => '86400',
        'main.enableWysiwygEditor'                => 'true',
        'main.enableWysiwygEditorFrontend'        => 'false',
        'main.templateSet'                        => 'default',
        'main.optionalMailAddress'                => 'false',
        'main.dateFormat'                         => 'Y-m-d H:i',
        'main.maintenanceMode'                    => 'false',
        'main.enableGravatarSupport'              => 'false',

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
        'spam.enableCaptchaCode'                  => null,
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

    /**
     * Constructor
     *
     * @return PMF_Installer
     */
    public function __construct()
    {
        $this->_system = new PMF_System();
        $dynMainConfig = array(
            'main.currentVersion'    => PMF_System::getVersion(),
            'main.currentApiVersion' => PMF_System::getApiVersion(),
            'main.phpMyFAQToken'     => md5(uniqid(rand())),
            'spam.enableCaptchaCode' => (extension_loaded('gd') ? 'true' : 'false'),
        );
        $this->_mainConfig = array_merge($this->_mainConfig, $dynMainConfig);
    }

    /**
     * Check absolutely necessary stuff and die
     *
     * @return void
     */
    public function checkBasicStuff()
    {
        if (!$this->checkMinimumPhpVersion()) {
            printf('<p class="alert alert-error">Sorry, but you need PHP %s or later!</p>', PMF_System::VERSION_MINIMUM_PHP);
            PMF_System::renderFooter();
        }

        if (! function_exists('date_default_timezone_set')) {
            echo '<p class="alert alert-error">Sorry, but setting a default timezone doesn\'t work in your environment!</p>';
            PMF_System::renderFooter();
        }

        if (! $this->_system->checkDatabase()) {
            echo '<p class="alert alert-error">No supported database detected! Please install one of the following' .
                ' database systems and enable the corresponding PHP extension in php.ini:</p>';
            echo '<ul>';
            foreach ($this->_system->getSupportedDatabases() as $database) {
                printf('    <li>%s</li>', $database[1]);
            }
            echo '</ul>';
            PMF_System::renderFooter();
        }

        if (! $this->_system->checkRequiredExtensions()) {
            echo '<p class="alert alert-error">The following extensions are missing! Please enable the PHP extension(s) in ' .
                'php.ini.</p>';
            echo '<ul>';
            foreach ($this->_system->getMissingExtensions() as $extension) {
                printf('    <li>ext/%s</li>', $extension);
            }
            echo '</ul>';
            PMF_System::renderFooter();
        }

        if (! $this->_system->checkRegisterGlobals()) {
            echo '<p class="alert alert-error">Please disable register_globals!</p>';
            PMF_System::renderFooter();
        }

        if (! $this->_system->checkMagicQuotesGpc()) {
            echo '<p class="alert alert-error">Please disable magic_quotes_gpc!</p>';
            PMF_System::renderFooter();
        }

        if (! $this->_system->checkphpMyFAQInstallation()) {
            echo '<p class="alert alert-error">It seems you\'re already running a version of phpMyFAQ. Please use the ' .
                '<a href="update.php">update script</a>.</p>';
            PMF_System::renderFooter();
        }
    }

    /**
     * Checks for the minimum PHP requirement and if the database credentials file is readable
     *
     * @return void
     */
    public function checkPreUpgrade()
    {
        if (! $this->checkMinimumPhpVersion()) {
            printf(
                '<p class="alert alert-error">Sorry, but you need PHP %s or later!</p>',
                PMF_System::VERSION_MINIMUM_PHP
            );
            PMF_System::renderFooter();
        }

        if (! is_readable(PMF_ROOT_DIR . '/config/database.php')) {
            echo '<p class="alert alert-error">It seems you never run a version of phpMyFAQ.<br />' .
                'Please use the <a href="setup.php">install script</a>.</p>';
            PMF_System::renderFooter();
        }

        if (! $this->_system->checkRegisterGlobals()) {
            echo '<p class="alert alert-error">Please disable register_globals!</p>';
            PMF_System::renderFooter();
        }

        if (! $this->_system->checkMagicQuotesGpc()) {
            echo '<p class="alert alert-error">Please disable magic_quotes_gpc!</p>';
            PMF_System::renderFooter();
        }
    }

    /**
     * Checks the minimum required PHP version, defined in PMF_System
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
     * Checks if the file permissions are okay
     *
     * @return void
     */
    public function checkFilesystemPermissions()
    {
        $instanceSetup = new PMF_Instance_Setup();
        $instanceSetup->setRootDir(PMF_ROOT_DIR);

        $dirs       = array('/attachments', '/config', '/data', '/images');
        $failedDirs = $instanceSetup->checkDirs($dirs);
        $numDirs    = sizeof($failedDirs);

        if (1 <= $numDirs) {
            printf(
                '<p class="alert alert-error">The following %s could not be created or %s not writable:</p><ul>',
                (1 < $numDirs) ? 'directories' : 'directory',
                (1 < $numDirs) ? 'are' : 'is'
            );
            foreach ($failedDirs as $dir) {
                echo "<li>$dir</li>\n";
            }
            printf(
                '</ul><p class="alert alert-error">Please create %s manually and/or change access to chmod 755 (or ' .
                    'greater if necessary).</p>',
                (1 < $numDirs) ? 'them' : 'it'
            );
            PMF_System::renderFooter();
        }
    }

    /**
     * Checks some non critical settings and print some hints
     *
     * @todo We should return an array of messages
     * @return void
     */
    public function checkNoncriticalSettings()
    {
        if ((@ini_get('safe_mode') == 'On' || @ini_get('safe_mode') === 1)) {
            echo '<p class="alert alert-error">The PHP safe mode is enabled. You may have problems when phpMyFAQ tries to write ' .
                ' in some directories.</p>';
        }
        if (! extension_loaded('gd')) {
            echo '<p class="alert alert-error">You don\'t have GD support enabled in your PHP installation. Please enable GD ' .
                'support in your php.ini file otherwise you can\'t use Captchas for spam protection.</p>';
        }
        if (! function_exists('imagettftext')) {
            echo '<p class="alert alert-error">You don\'t have Freetype support enabled in the GD extension of your PHP ' .
                'installation. Please enable Freetype support in GD extension otherwise the Captchas for spam ' .
                'protection will be quite easy to break.</p>';
        }
        if (! extension_loaded('curl') || ! extension_loaded('openssl')) {
            echo '<p class="alert alert-error">You don\'t have cURL and/or OpenSSL support enabled in your PHP installation. ' .
                'Please enable cURL and/or OpenSSL support in your php.ini file otherwise you can\'t use the Twitter ' .
                ' support.</p>';
        }
    }

    /**
     * Checks if we can store data via sessions. If not, e.g. an user can't
     * login into the admin section
     *
     * @return bool
     */
    public function checkSessionSettings()
    {
        return true;
    }

    /**
     * Starts the installation
     *
     * @param array $DB
     */
    public function startInstall(Array $DB = null)
    {
        $query = $uninst = $dbSetup = array();

        // Check table prefix
        $dbSetup['dbPrefix'] = $sqltblpre = PMF_Filter::filterInput(INPUT_POST, 'sqltblpre', FILTER_SANITIZE_STRING, '');
        if ('' !== $dbSetup['dbPrefix']) {
            PMF_Db::setTablePrefix($dbSetup['dbPrefix']);
        }

        // Check database entries
        $dbSetup['dbType'] = PMF_Filter::filterInput(INPUT_POST, 'sql_type', FILTER_SANITIZE_STRING);
        if (!is_null($dbSetup['dbType'])) {
            $dbSetup['dbType'] = trim($dbSetup['dbType']);
            if (! file_exists(PMF_ROOT_DIR . '/install/' . $dbSetup['dbType'] . '.sql.php')) {
                printf(
                    '<p class="alert alert-error"><strong>Error:</strong> Invalid server type: %s</p>',
                    $dbSetup['dbType']
                );
                PMF_System::renderFooter(true);
            }
        } else {
            echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please select a database type.</p>\n";
            PMF_System::renderFooter(true);
        }

        $dbSetup['dbServer'] = PMF_Filter::filterInput(INPUT_POST, 'sql_server', FILTER_SANITIZE_STRING);
        if (is_null($dbSetup['dbServer']) && ! PMF_System::isSqlite($dbSetup['dbType'])) {
            echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a database server.</p>\n";
            PMF_System::renderFooter(true);
        }

        $dbSetup['dbPort'] = PMF_Filter::filterInput(INPUT_POST, 'sql_port', FILTER_VALIDATE_INT);
        if (is_null($dbSetup['dbPort']) && ! PMF_System::isSqlite($dbSetup['dbType'])) {
            echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a valid database port.</p>\n";
            PMF_System::renderFooter(true);
        }

        $dbSetup['dbUser'] = PMF_Filter::filterInput(INPUT_POST, 'sql_user', FILTER_SANITIZE_STRING);
        if (is_null($dbSetup['dbUser']) && ! PMF_System::isSqlite($dbSetup['dbType'])) {
            echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a database username.</p>\n";
            PMF_System::renderFooter(true);
        }

        $dbSetup['dbPassword'] = PMF_Filter::filterInput(INPUT_POST, 'sql_passwort', FILTER_UNSAFE_RAW);
        if (is_null($dbSetup['dbPassword']) && ! PMF_System::isSqlite($dbSetup['dbType'])) {
            // Password can be empty...
            $dbSetup['dbPassword'] = '';
        }

        $dbSetup['dbDatabaseName'] = PMF_Filter::filterInput(INPUT_POST, 'sql_db', FILTER_SANITIZE_STRING);
        if (is_null($dbSetup['dbDatabaseName']) && ! PMF_System::isSqlite($dbSetup['dbType'])) {
            echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a database name.</p>\n";
            PMF_System::renderFooter(true);
        }

        if (PMF_System::isSqlite($dbSetup['dbType'])) {
            $dbSetup['dbServer'] = PMF_Filter::filterInput(INPUT_POST, 'sql_sqlitefile', FILTER_SANITIZE_STRING);
            if (is_null($dbSetup['dbServer'])) {
                echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a SQLite database filename.</p>\n";
                PMF_System::renderFooter(true);
            }
        }

        // check database connection
        PMF_Db::setTablePrefix($dbSetup['dbPrefix']);
        $db = PMF_Db::factory($dbSetup['dbType']);
        $db->connect($dbSetup['dbServer'], $dbSetup['dbUser'], $dbSetup['dbPassword'], $dbSetup['dbDatabaseName']);
        if (!$db) {
            printf("<p class=\"alert alert-error\"><strong>DB Error:</strong> %s</p>\n", $db->error());
            PMF_System::renderFooter(true);
        }

        $configuration = new PMF_Configuration($db);

        // check LDAP if available
        $ldapEnabled = PMF_Filter::filterInput(INPUT_POST, 'ldap_enabled', FILTER_SANITIZE_STRING);
        if (extension_loaded('ldap') && !is_null($ldapEnabled)) {

            $ldapSetup = array();

            // check LDAP entries
            $ldapSetup['ldapServer'] = PMF_Filter::filterInput(INPUT_POST, 'ldap_server', FILTER_SANITIZE_STRING);
            if (is_null($ldapSetup['ldapServer'])) {
                echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a LDAP server.</p>\n";
                PMF_System::renderFooter(true);
            }

            $ldapSetup['ldapPort'] = PMF_Filter::filterInput(INPUT_POST, 'ldap_port', FILTER_VALIDATE_INT);
            if (is_null($ldapSetup['ldapPort'])) {
                echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a LDAP port.</p>\n";
                PMF_System::renderFooter(true);
            }

            $ldapSetup['ldapBase'] = PMF_Filter::filterInput(INPUT_POST, 'ldap_base', FILTER_SANITIZE_STRING);
            if (is_null($ldapSetup['ldapBase'])) {
                echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a LDAP base search DN.</p>\n";
                PMF_System::renderFooter(true);
            }

            // LDAP User and LDAP password are optional
            $ldapSetup['ldapUser']     = PMF_Filter::filterInput(INPUT_POST, 'ldap_user', FILTER_SANITIZE_STRING, '');
            $ldapSetup['ldapPassword'] = PMF_Filter::filterInput(INPUT_POST, 'ldap_password', FILTER_SANITIZE_STRING, '');

            // check LDAP connection
            require PMF_ROOT_DIR . "/inc/PMF/Ldap.php";
            $ldap = new PMF_Ldap($configuration);
            $ldap->connect(
                $ldapSetup['ldapServer'],
                $ldapSetup['ldapPort'],
                $ldapSetup['ldapBase'],
                $ldapSetup['ldapUser'],
                $ldapSetup['ldapPassword']
            );
            if (!$ldap) {
                echo "<p class=\"alert alert-error\"><strong>LDAP Error:</strong> " . $ldap->error() . "</p>\n";
                PMF_System::renderFooter(true);
            }
        }

        // check loginname
        $loginname = PMF_Filter::filterInput(INPUT_POST, 'loginname', FILTER_SANITIZE_STRING);
        if (is_null($loginname)) {
            echo '<p class="alert alert-error"><strong>Error:</strong> Please add a loginname for your account.</p>';
            PMF_System::renderFooter(true);
        }

        // check user entries
        $password = PMF_Filter::filterInput(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        if (is_null($password)) {
            echo '<p class="alert alert-error"><strong>Error:</strong> Please add a password for the your account.</p>';
            PMF_System::renderFooter(true);
        }

        $password_retyped = PMF_Filter::filterInput(INPUT_POST, 'password_retyped', FILTER_SANITIZE_STRING);
        if (is_null($password_retyped)) {
            echo '<p class="alert alert-error"><strong>Error:</strong> Please add a retyped password.</p>';
            PMF_System::renderFooter(true);
        }

        if (strlen($password) <= 5 || strlen($password_retyped) <= 5) {
            echo '<p class="alert alert-error"><strong>Error:</strong> Your password and retyped password are too short.' .
                ' Please set your password and your retyped password with a minimum of 6 characters.</p>';
            PMF_System::renderFooter(true);
        }
        if ($password != $password_retyped) {
            echo '<p class="alert alert-error"><strong>Error:</strong> Your password and retyped password are not equal.' .
                ' Please check your password and your retyped password.</p>';
            PMF_System::renderFooter(true);
        }

        $language  = PMF_Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_STRING, 'en');
        $realname  = PMF_Filter::filterInput(INPUT_POST, 'realname', FILTER_SANITIZE_STRING, '');
        $email     = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL, '');
        $permLevel = PMF_Filter::filterInput(INPUT_POST, 'permLevel', FILTER_SANITIZE_STRING, 'basic');

        $instanceSetup = new PMF_Instance_Setup();
        $instanceSetup->setRootDir(PMF_ROOT_DIR);

        // Write the DB variables in database.php
        if (! $instanceSetup->createDatabaseFile($dbSetup)) {
            echo "<p class=\"alert alert-error\"><strong>Error:</strong> Setup cannot write to ./config/database.php.</p>";
            $this->_system->cleanInstallation();
            PMF_System::renderFooter(true);
        }

        // check LDAP if available
        if (extension_loaded('ldap') && !is_null($ldapEnabled)) {
            if (! $instanceSetup->createLdapFile($ldapSetup, '')) {
                echo "<p class=\"alert alert-error\"><strong>Error:</strong> Setup cannot write to ./config/ldap.php.</p>";
                $this->_system->cleanInstallation();
                PMF_System::renderFooter(true);
            }
        }

        // connect to the database using config/database.php
        require PMF_ROOT_DIR . '/config/database.php';
        $db = PMF_Db::factory($dbSetup['dbType']);
        $db->connect($DB['server'], $DB['user'], $DB['password'], $DB['db']);
        if (!$db) {
            echo "<p class=\"alert alert-error\"><strong>DB Error:</strong> ".$db->error()."</p>\n";
            $this->_system->cleanInstallation();
            PMF_System::renderFooter(true);
        }

        require PMF_ROOT_DIR . '/install/' . $dbSetup['dbType'] . '.sql.php'; // CREATE TABLES
        require PMF_ROOT_DIR . '/install/stopwords.sql.php';  // INSERTs for stopwords

        $this->_system->setDatabase($db);

        echo '<p>';

        // Erase any table before starting creating the required ones
        if (! PMF_System::isSqlite($dbSetup['dbType'])) {
            $this->_system->dropTables($uninst);
        }

        // Start creating the required tables
        $count = 0;
        foreach ($query as $executeQuery) {
            $result = @$db->query($executeQuery);
            if (!$result) {
                echo '<p class="alert alert-error"><strong>Error:</strong> Please install your version of phpMyFAQ once again or send
            us a <a href=\"http://www.phpmyfaq.de\" target=\"_blank\">bug report</a>.</p>';
                printf('<p class="alert alert-error"><strong>DB error:</strong> %s</p>', $db->error());
                printf('<code>%s</code>', htmlentities($executeQuery));
                $this->_system->dropTables($uninst);
                $this->_system->cleanInstallation();
                PMF_System::renderFooter(true);
            }
            usleep(2500);
            $count++;
            if (!($count % 10)) {
                echo '| ';
            }
        }

        $link = new PMF_Link(null, $configuration);

        // add main configuration, add personal settings
        $this->_mainConfig['main.metaPublisher']      = $realname;
        $this->_mainConfig['main.administrationMail'] = $email;
        $this->_mainConfig['main.language']           = $language;
        $this->_mainConfig['security.permLevel']      = $permLevel;

        foreach ($this->_mainConfig as $name => $value) {
            $configuration->add($name, $value);
        }

        $configuration->update(array('main.referenceURL' => $link->getSystemUri('/install/setup.php')));
        $configuration->add('security.salt', md5($configuration->get('main.referenceURL')));

        // add admin account and rights
        $admin = new PMF_User($configuration);
        if (! $admin->createUser($loginname, $password, 1)) {
            echo "<p class=\"alert alert-error\"><strong>Fatal installation error:</strong> " .
                "Couldn't create the admin user.</p>\n";
            $this->_system->cleanInstallation();
            PMF_System::renderFooter(true);
        }
        $admin->setStatus('protected');
        $adminData = array(
            'display_name' => $realname,
            'email'        => $email
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
            'url'      => $link->getSystemUri($_SERVER['SCRIPT_NAME']),
            'instance' => $link->getSystemRelativeUri('install/setup.php'),
            'comment'  => 'phpMyFAQ ' . PMF_System::getVersion()
        );
        $faqInstance = new PMF_Instance($configuration);
        $faqInstance->addInstance($instanceData);

        $faqInstanceMaster = new PMF_Instance_Master($configuration);
        $faqInstanceMaster->createMaster($faqInstance);

        echo '</p>';
    }

    /**
     * Cleanup all files after an installation
     *
     * @return void
     */
    public function cleanUpFiles()
    {
        // Remove 'setup.php' file
        if (@unlink(basename($_SERVER['SCRIPT_NAME']))) {
            echo "<p class=\"alert alert-success\">The file <em>./install/setup.php</em> was deleted automatically.</p>\n";
        } else {
            echo "<p class=\"alert alert-error\">Please delete the file <em>./install/setup.php</em> manually.</p>\n";
        }
        // Remove 'update.php' file
        if (@unlink(dirname($_SERVER['PATH_TRANSLATED']) . '/update.php')) {
            echo "<p class=\"alert alert-success\">The file <em>./install/update.php</em> was deleted automatically.</p>\n";
        } else {
            echo "<p class=\"alert alert-error\">Please delete the file <em>./install/update.php</em> manually.</p>\n";
        }
    }

    /**
     * Echos the questionnaire data
     *
     * @return void
     */
    public function printDataList()
    {
        $q = new PMF_Questionnaire_Data($this->_mainConfig);
        $options = $q->get();
        echo '<dl>' . PHP_EOL;
        array_walk($options, 'data_printer');
        printf(
            '</dl><input type="hidden" name="systemdata" value="%s" />',
            PMF_String::htmlspecialchars(serialize($q->get()), ENT_QUOTES)
        );
    }
}