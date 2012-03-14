<?php
/**
 * The main admin backend index file
 *
 * PHP Version 5.2.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administraion
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Bastian Poettner <bastian@poettner.net>
 * @author    Meikel Katzengreis <meikel@katzengreis.com>
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-16
 */

define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));

//
// Check if config/database.php exist -> if not, redirect to installer
//
if (!file_exists(PMF_ROOT_DIR . '/config/database.php')) {
    header("Location: ".str_replace('admin/index.php', '', $_SERVER['SCRIPT_NAME']).'install/setup.php');
    exit();
}

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Autoload classes, prepend and start the PHP session
//
require_once PMF_ROOT_DIR.'/inc/Bootstrap.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH.trim($faqConfig->get('main.phpMyFAQToken')));
session_start();

// get language (default: english)
$Language = new PMF_Language();
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
// Preload English strings
require_once (PMF_ROOT_DIR.'/lang/language_en.php');

if (isset($LANGCODE) && PMF_Language::isASupportedLanguage($LANGCODE)) {
    // Overwrite English strings with the ones we have in the current language
    require_once PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php';
} else {
    $LANGCODE = 'en';
}

//
// Initalizing static string wrapper
//
PMF_String::init($LANGCODE);

//
// Set actual template set name
//
PMF_Template::setTplSetName($faqConfig->get('main.templateSet'));

//
// Initialize attachment factory
//
PMF_Attachment_Factory::init(
    $faqConfig->get('records.attachmentsStorageType'),
    $faqConfig->get('records.defaultAttachmentEncKey'),
    $faqConfig->get('records.enableAttachmentEncryption')
);

//
// Initiazile caching
//
PMF_Cache::init($faqConfig);

//
// Create a new FAQ object
//
$faq = new PMF_Faq();

//
// use mbstring extension if available and when possible
//
$valid_mb_strings = array('ja', 'en', 'uni');
$mbLanguage = ($PMF_LANG['metaLanguage'] != 'ja') ? 'uni' : $PMF_LANG['metaLanguage'];
if (function_exists('mb_language') && in_array($mbLanguage, $valid_mb_strings)) {
    mb_language($mbLanguage);
    mb_internal_encoding('utf-8');
}

//
// Get user action
//
$action = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
if (is_null($action)) {
    $action = PMF_Filter::filterInput(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
}

// authenticate current user
$auth        = null;
$error       = '';
$faqusername = PMF_Filter::filterInput(INPUT_POST, 'faqusername', FILTER_SANITIZE_STRING);
$faqpassword = PMF_Filter::filterInput(INPUT_POST, 'faqpassword', FILTER_SANITIZE_STRING);
if (!is_null($faqusername) && !is_null($faqpassword)) {
    // login with username and password
    $user = new PMF_User_CurrentUser($faqConfig);
    if ($faqConfig->get('security.ldapSupport')) {
        $authLdap = new PMF_Auth_AuthLdap($faqConfig);
        $user->addAuth($authLdap, 'ldap');
    }
    if ($user->login($faqusername, $faqpassword)) {
        // login, if user account is NOT blocked
        if ($user->getStatus() != 'blocked') {
            $auth = true;
        } else {
            $error = $PMF_LANG['ad_auth_fail'];
            $user  = null;
        }
    } else {
        // error
        $logging = new PMF_Logging();
        $logging->logAdmin($user, 'Loginerror\nLogin: '.$faqusername.'\nErrors: ' . implode(', ', $user->errors));
        $error = $PMF_LANG['ad_auth_fail'];
        $user  = null;
    }
} else {
    // authenticate with session information
    $user = PMF_User_CurrentUser::getFromSession($faqConfig);
    if ($user) {
        $auth = true;
    } else {
        $user = null;
    }
}

// get user rights
$permission = array();
if (isset($auth)) {
    // read all rights, set them FALSE
    $allRights = $user->perm->getAllRightsData();
    foreach ($allRights as $right) {
        $permission[$right['name']] = false;
    }
    // check user rights, set them TRUE
    $allUserRights = $user->perm->getAllUserRights($user->getUserId());
    foreach ($allRights as $right) {
        if (in_array($right['right_id'], $allUserRights))
            $permission[$right['name']] = true;
    }
}

// logout
if ($action == 'logout' && $auth) {
    $user->deleteFromSession();
    $user = null;
    $auth = null;
    $ssoLogout = $faqConfig->get('security.ssoLogoutRedirect');
    if ($faqConfig->get('security.ssoSupport') && !empty ($ssoLogout))
        header ("Location:$ssoLogout");
}

//
// Get current admin user and group id - default: -1
//
if (isset($user) && is_object($user)) {
    $current_admin_user = $user->getUserId();
    if ($user->perm instanceof PMF_Perm_PermMedium) {
        $current_admin_groups = $user->perm->getUserGroups($current_admin_user);
    } else {
        $current_admin_groups = array(-1);
    }
    if (0 == count($current_admin_groups)) {
        $current_admin_groups = array(-1);
    }
}

//
// Get action from _GET and _POST first
$_ajax = PMF_Filter::filterInput(INPUT_GET, 'ajax', FILTER_SANITIZE_STRING);
if (is_null($_ajax)) {
    $_ajax = PMF_Filter::filterInput(INPUT_POST, 'ajax', FILTER_SANITIZE_STRING);
}

// if performing AJAX operation, needs to branch before header.php
if (isset($auth) && in_array(true, $permission)) {
    if (isset($action) && isset($_ajax)) {
        if ($action == 'ajax') {

            switch ($_ajax) {
            // Link verification
            case 'verifyURL':
                require_once 'ajax.verifyurl.php';
                break;
            case 'onDemandURL':
                require_once 'ajax.ondemandurl.php';
                break;

            // Configuration management
            case 'config_list':
                require_once 'ajax.config_list.php';
                break;

            case 'config':
                require_once 'ajax.config.php';
                break;
                
            // Tags management
            case 'tags_list':
                require_once 'ajax.tags_list.php';
                break;
                
            // Comments
            case 'comment':
                require 'ajax.comment.php';
                break;
            
            // Records
            case 'records':	
                require 'ajax.records.php';
                break;

            case 'recordSave':
                require 'record.save.php';
                break;

            case 'recordAdd':
                require 'record.add.php';
                break;

            // Search
            case 'search':
                require 'ajax.search.php';
                break;

            // Users
            case 'user': 
                require 'ajax.user.php';
                break;
            
            // Groups
            case 'group': 
                require 'ajax.group.php';
                break;
            
            // Interface translation
            case 'trans':
                require 'ajax.trans.php';
                break;
                
            case 'att':
                require 'ajax.attachment.php';
                break;
            }
        exit();
        }
    }
}

// are we running a PMF export file request?
switch($action) {
    case 'exportfile':
        require 'export.file.php';
        exit();
        break;
    case 'reportexport':
        require 'report.export.php';
        exit();
        break;
}

// Header of the admin page including the navigation
require_once 'header.php';

// User is authenticated
if (isset($auth) && in_array(true, $permission)) {
    if (!is_null($action)) {
        // the various sections of the admin area
        switch ($action) {
            // functions for user administration
            case 'user':                    require_once 'user.php'; break;
            case 'group':                   require_once 'group.php'; break;
            // functions for record administration
            case 'viewinactive':
            case 'viewactive':
            case 'view':                    require_once 'record.show.php'; break;
            case 'searchfaqs':              require_once 'record.search.php'; break;
            case "takequestion":
            case "editentry":
            case 'copyentry':
            case "editpreview":             require_once 'record.edit.php'; break;
            case "insertentry":             require_once 'record.add.php'; break;
            case "saveentry":               require_once 'record.save.php'; break;
            case "delentry":                require_once 'record.delete.php'; break;
            case "delatt":                  require_once 'record.delatt.php'; break;
            case "question":                require_once 'record.delquestion.php'; break;
            case 'comments':                require_once 'record.comments.php'; break;
            // news administraion
            case 'news':                    
            case 'addnews':
            case 'editnews':
            case 'savenews':
            case 'updatenews':	
            case 'deletenews':              require_once 'news.php'; break;
            // category administration
            case 'content':
            case 'category':
            case 'savecategory':
            case 'updatecategory':
            case 'removecategory':
            case 'changecategory':
            case 'pastecategory':           require_once 'category.main.php'; break;
            case "addcategory":             require_once 'category.add.php'; break;
            case "editcategory":            require_once 'category.edit.php'; break;
            case "translatecategory":       require_once 'category.translate.php'; break;
            case "deletecategory":          require_once 'category.delete.php'; break;
            case "cutcategory":             require_once 'category.cut.php'; break;
            case "movecategory":            require_once 'category.move.php'; break;
            case "showcategory":            require_once 'category.showstructure.php'; break;
            // glossary
            case 'glossary':
            case 'saveglossary':
            case 'updateglossary':
            case 'deleteglossary':          require_once 'glossary.main.php'; break;
            case 'addglossary':             require_once 'glossary.add.php'; break;
            case 'editglossary':            require_once 'glossary.edit.php'; break;
            // adminlog administration
            case 'adminlog':
            case 'deleteadminlog':          require_once 'adminlog.php'; break;
            // functions for password administration
            case "passwd":                  require_once 'pwd.change.php'; break;
            // functions for session administration
            case "viewsessions":            require_once 'stat.main.php'; break;
            case "sessionbrowse":           require_once 'stat.browser.php'; break;
            case "viewsession":             require_once 'stat.show.php'; break;
            case "statistics":              require_once 'stat.ratings.php'; break;
            case "searchstats":             require_once 'stat.search.php'; break;
            case 'reports':                 require_once 'report.main.php'; break;
            case 'reportview':              require_once 'report.view.php'; break;
            // functions for config administration
            case 'config':                  require_once 'configuration.php'; break;
            case 'linkconfig':              require_once 'linkconfig.main.php'; break;
            case 'stopwordsconfig':         require_once 'stopwordsconfig.main.php'; break;
            // functions for backup administration
            case 'backup':                  require_once 'backup.main.php'; break;
            case 'restore':                 require_once 'backup.import.php'; break;
            // functions for FAQ export
            case "export":                  require_once 'export.main.php'; break;
            // translation tools
            case "transedit":               require_once 'trans.edit.php'; break;
            case "translist":               require_once 'trans.list.php'; break;
            case "transadd":                require_once 'trans.add.php'; break;
            // attachment administration 
            case "attachments":             require_once "att.main.php"; break;
            
            default:                        print "Error"; break;
        }
    } else {
        // start page with some information about the FAQ
        $PMF_TABLE_INFO = $db->getTableStatus();
?>

            <header>
                <h2><?php print $PMF_LANG['ad_pmf_info']; ?></h2>
            </header>
            <table class="table table-striped">
            <tbody>
                <tr>
                    <td><strong><a href="?action=viewsessions"><?php print $PMF_LANG["ad_start_visits"]; ?></a></strong></td>
                    <td><?php print $PMF_TABLE_INFO[SQLPREFIX . "faqsessions"]; ?></td>
                </tr>
                <tr>
                    <td><strong><a href="?action=view"><?php print $PMF_LANG["ad_start_articles"]; ?></a></strong></td>
                    <td><?php print $PMF_TABLE_INFO[SQLPREFIX . "faqdata"]; ?></td>
                </tr>
                <tr>
                    <td><strong><a href="?action=comments"><?php print $PMF_LANG["ad_start_comments"]; ?></strong></a></td>
                    <td><?php print $PMF_TABLE_INFO[SQLPREFIX . "faqcomments"]; ?></td>
                </tr>
                <tr>
                    <td><strong><a href="?action=question"><?php print $PMF_LANG["msgOpenQuestions"]; ?></strong></a></td>
                    <td><?php print $PMF_TABLE_INFO[SQLPREFIX . "faqquestions"]; ?></td>
                </tr>
                <tr>
                    <td><strong><a href="?action=news"><?php print $PMF_LANG["msgNews"]; ?></strong></a></td>
                    <td><?php print $PMF_TABLE_INFO[SQLPREFIX . "faqnews"]; ?></td>
                </tr>
                <tr>
                    <td><strong><a href="?action=user&user_action=listallusers"><?php print $PMF_LANG['admin_mainmenu_users']; ?></strong></a></td>
                    <td><?php print $PMF_TABLE_INFO[SQLPREFIX . 'faquser'] - 1; ?></td>
                </tr>
            </tbody>
            </table>
        </section>

        <section>
            <header>
                <h3><?php print $PMF_LANG['ad_online_info']; ?></h3>
            </header>
            <p>
<?php
        $version = PMF_Filter::filterInput(INPUT_POST, 'param', FILTER_SANITIZE_STRING);        
        if (!is_null($version) && $version == 'version') {
            $json   = file_get_contents('http://www.phpmyfaq.de/json/version.php');
            $result = json_decode($json);
            if ($result instanceof stdClass) {
                $installed = $faqConfig->get('main.currentVersion');
                $available = $result->stable;
                printf(
                    '<p class="alert alert-%s">%s <a href="http://www.phpmyfaq.de" target="_blank">phpmyfaq.de</a>: <strong>phpMyFAQ %s</strong>',
                    (-1 == version_compare($installed, $available)) ? 'danger' : 'info',
                    $PMF_LANG['ad_xmlrpc_latest'],
                    $available
                );
                // Installed phpMyFAQ version is outdated
                if (-1 == version_compare($installed, $available)) {
                    print '<br />' . $PMF_LANG['ad_you_should_update'];
                }
                print '</p>';
            }
        } else {
?>
                <form action="index.php" method="post">
                    <input type="hidden" name="param" value="version" />
                    <input class="btn-primary btn-large" type="submit" value="<?php print $PMF_LANG["ad_xmlrpc_button"]; ?>" />
                </form>
<?php
        }
?>
            
            </p>
        </section>

        <section>
            <header>
                <h3><?php print $PMF_LANG['ad_system_info']; ?></h3>
            </header>
            <table class="table table-striped">
            <tbody>
                <tr>
                    <td style="width: 150px;"><strong>phpMyFAQ Version</strong></td>
                    <td>phpMyFAQ <?php print $faqConfig->get('main.currentVersion'); ?></td>
                </tr>
                <tr>
                    <td><strong>Server Software</strong></td>
                    <td><?php print $_SERVER["SERVER_SOFTWARE"]; ?></td>
                </tr>
                <tr>
                    <td><strong>PHP Version</strong></td>
                    <td>PHP <?php print phpversion(); ?></td>
                </tr>
                <tr>
                    <td><strong>Register Globals</strong></td>
                    <td><?php print ini_get('register_globals') == 1 ? 'on' : 'off'; ?></td>
                </tr>
                <tr>
                    <td><strong>Safe Mode</strong></td>
                    <td><?php print ini_get('safe_mode') == 1 ? 'on' : 'off'; ?></td>
                </tr>
                <tr>
                    <td><strong>Open Basedir</strong></td>
                    <td><?php print ini_get('open_basedir') == 1 ? 'on' : 'off'; ?></td>
                </tr>
                <tr>
                    <td><strong>DB Server</strong></td>
                    <td><?php print ucfirst($DB['type']); ?></td>
                </tr>
                <tr>
                    <td><strong>DB Client Version</strong></td>
                    <td><?php print $db->clientVersion(); ?></td>
                </tr>
                <tr>
                    <td><strong>DB Server Version</strong></td>
                    <td><?php print $db->serverVersion(); ?></td>
                </tr>
                <tr>
                    <td><strong>Webserver Interface</strong></td>
                    <td><?php print strtoupper(@php_sapi_name()); ?></td>
                </tr>
                <tr>
                    <td><strong>PHP Extensions</strong></td>
                    <td><?php print implode(', ', get_loaded_extensions()); ?></td>
                </tr>
            </tbody>
            </table>

            <div style="font-size: 5px; text-align: right; color: #f5f5f5">NOTE: Art is resistance.</div>
        </section>
<?php
    }
// User is authenticated, but has no rights
} elseif (isset($auth) && !in_array(true, $permission)) {
?>
            <header>
                <h2><?php print $PMF_LANG['ad_pmf_info']; ?></h2>
            </header>

            <p class="error"><?php print $PMF_LANG['err_NotAuth'] ?></p>

<?php
// User is NOT authenticated
} else {
?>

            <header>
                <h2>phpMyFAQ Login</h2>
            </header>
<?php
    if (isset($error) && 0 < strlen($error)) {
        $message = sprintf('<p class="alert alert-error">%s</p>', $error);
    } else {
        $message = sprintf('<p>%s</p>', $PMF_LANG['ad_auth_insert']);
    }
    if ($action == 'logout') {
        $message = sprintf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_logout']);
    }
    
    if (isset($_SERVER['HTTPS']) || !$faqConfig->get('security.useSslForLogins')) {
?>

            <?php print $message ?>

            <form class="form-horizontal" action="index.php" method="post">

                <div class="control-group">
                    <label class="control-label" for="faqusername"><?php print $PMF_LANG["ad_auth_user"]; ?></label>
                    <div class="controls">
                        <input type="text" name="faqusername" id="faqusername" required="required" autofocus="autofocus" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="faqpassword"><?php print $PMF_LANG["ad_auth_passwd"]; ?></label>
                    <div class="controls">
                        <input type="password" size="30" name="faqpassword" id="faqpassword" required="required" />
                    </div>
                </div>

                <div class="form-actions">
                    <input class="btn-primary btn-large" type="submit" value="<?php print $PMF_LANG["ad_auth_ok"]; ?>" />
                </div>
<?php
    } else {
        printf('<p><a href="https://%s%s">%s</a></p>',
            $_SERVER['HTTP_HOST'],
            $_SERVER['REQUEST_URI'],
            $PMF_LANG['msgSecureSwitch']);
    }
?>
            </form>
<?php
}
?>
    </div>
<?php

require 'footer.php';

$db->close();
