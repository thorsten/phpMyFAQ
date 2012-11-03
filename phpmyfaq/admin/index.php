<?php
/**
 * The main admin backend index file
 *
 * PHP Version 5.3
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

define('PMF_ROOT_DIR', dirname(__DIR__));

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
// Bootstrap phpMyFAQ and start the PHP session
//
require_once PMF_ROOT_DIR.'/inc/Bootstrap.php';
PMF_Init::cleanRequest();
session_name(PMF_Session::PMF_COOKIE_NAME_AUTH);
session_start();

// get language (default: english)
$Language = new PMF_Language($faqConfig);
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
// Preload English strings
require_once (PMF_ROOT_DIR.'/lang/language_en.php');
$faqConfig->setLanguage($Language);

if (isset($LANGCODE) && PMF_Language::isASupportedLanguage($LANGCODE)) {
    // Overwrite English strings with the ones we have in the current language
    if (! file_exists(PMF_ROOT_DIR . '/lang/language_' . $LANGCODE . '.php')) {
        $LANGCODE = 'en';
    }
    require_once PMF_ROOT_DIR . '/lang/language_' . $LANGCODE . '.php';
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
$faq = new PMF_Faq($faqConfig);

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
$faqremember = PMF_Filter::filterInput(INPUT_POST, 'faqrememberme', FILTER_SANITIZE_STRING);

// Set username via SSO
if ($faqConfig->get('security.ssoSupport') && isset($_SERVER['REMOTE_USER'])) {
    $faqusername = trim($_SERVER['REMOTE_USER']);
    $faqpassword = '';
}

// Login via local DB or LDAP or SSO
if (!is_null($faqusername) && !is_null($faqpassword)) {
    $user = new PMF_User_CurrentUser($faqConfig);
    if (!is_null($faqremember) && 'rememberMe' === $faqremember) {
        $user->enableRememberMe();
    }
    if ($faqConfig->get('security.ldapSupport')) {
        $authLdap = new PMF_Auth_Ldap($faqConfig);
        $user->addAuth($authLdap, 'ldap');
    }
    if ($faqConfig->get('security.ssoSupport')) {
        $authSso = new PMF_Auth_Sso($faqConfig);
        $user->addAuth($authSso, 'sso');
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
        $logging = new PMF_Logging($faqConfig);
        $logging->logAdmin($user, 'Loginerror\nLogin: '.$faqusername.'\nErrors: ' . implode(', ', $user->errors));
        $error = $PMF_LANG['ad_auth_fail'];
        $user  = null;
    }
} else {
    // Try to authenticate with cookie information
    $user = PMF_User_CurrentUser::getFromCookie($faqConfig);
    // authenticate with session information
    if (! $user instanceof PMF_User_CurrentUser) {
        $user = PMF_User_CurrentUser::getFromSession($faqConfig);
    }
    if ($user instanceof PMF_User_CurrentUser) {
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
    if ($faqConfig->get('security.ssoSupport') && !empty ($ssoLogout)) {
        header ("Location: $ssoLogout");
    }
}

//
// Get current admin user and group id - default: -1
//
if (isset($user) && is_object($user)) {
    $currentAdminUser = $user->getUserId();
    if ($user->perm instanceof PMF_Perm_Medium) {
        $currentAdminGroups = $user->perm->getUserGroups($currentAdminUser);
    } else {
        $currentAdminGroups = array(-1);
    }
    if (0 == count($currentAdminGroups)) {
        $currentAdminGroups = array(-1);
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

			case 'autosave':
				require 'ajax.autosave.php';
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
            case "question":                require_once 'record.questions.php'; break;
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
            case 'updateinstance':
            case 'instances':               require_once 'instances.php'; break;
            case 'editinstance':            require_once 'instances.edit.php'; break;
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
        $faqTableInfo = $faqConfig->getDb()->getTableStatus();
        $faqSystem = new PMF_System();
?>

            <header>
                <h2><?php print $PMF_LANG['ad_pmf_info']; ?></h2>
            </header>

            <table class="table table-striped">
            <tbody>
                <tr>
                    <td><strong><a href="?action=config"><?php print $PMF_LANG['msgMode']; ?></a></strong></td>
                    <td>
                        <?php if ($faqConfig->get('main.maintenanceMode')): ?>
                        <span class="label label-important"><?php print $PMF_LANG['msgMaintenanceMode']; ?></span>
                        <?php else: ?>
                        <span class="label label-success"><?php print $PMF_LANG['msgOnlineMode']; ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><a href="?action=viewsessions"><?php print $PMF_LANG["ad_start_visits"]; ?></a></strong></td>
                    <td><?php print $faqTableInfo[PMF_Db::getTablePrefix() . "faqsessions"]; ?></td>
                </tr>
                <tr>
                    <td><strong><a href="?action=view"><?php print $PMF_LANG["ad_start_articles"]; ?></a></strong></td>
                    <td><?php print $faqTableInfo[PMF_Db::getTablePrefix() . "faqdata"]; ?></td>
                </tr>
                <tr>
                    <td><strong><a href="?action=comments"><?php print $PMF_LANG["ad_start_comments"]; ?></strong></a></td>
                    <td><?php print $faqTableInfo[PMF_Db::getTablePrefix() . "faqcomments"]; ?></td>
                </tr>
                <tr>
                    <td><strong><a href="?action=question"><?php print $PMF_LANG["msgOpenQuestions"]; ?></strong></a></td>
                    <td><?php print $faqTableInfo[PMF_Db::getTablePrefix() . "faqquestions"]; ?></td>
                </tr>
                <tr>
                    <td><strong><a href="?action=news"><?php print $PMF_LANG["msgNews"]; ?></strong></a></td>
                    <td><?php print $faqTableInfo[PMF_Db::getTablePrefix() . "faqnews"]; ?></td>
                </tr>
                <tr>
                    <td><strong><a href="?action=user&user_action=listallusers"><?php print $PMF_LANG['admin_mainmenu_users']; ?></strong></a></td>
                    <td><?php print $faqTableInfo[PMF_Db::getTablePrefix() . 'faquser'] - 1; ?></td>
                </tr>
            </tbody>
            </table>
        </section>

        <section class="row-fluid">
            <div class="span5">
                <header>
                    <h3><?php print $PMF_LANG['ad_online_info']; ?></h3>
                </header>
<?php
        $version = PMF_Filter::filterInput(INPUT_POST, 'param', FILTER_SANITIZE_STRING);
        if (!is_null($version) && $version == 'version') {
            $json   = file_get_contents('http://www.phpmyfaq.de/api/version');
            $result = json_decode($json);
            if ($result instanceof stdClass) {
                $installed = $faqConfig->get('main.currentVersion');
                $available = $result->stable;
                printf(
                    '<p class="alert alert-%s">%s <a href="http://www.phpmyfaq.de" target="_blank">phpmyfaq.de</a>:<br/><strong>phpMyFAQ %s</strong>',
                    (-1 == version_compare($installed, $available)) ? 'danger' : 'info',
                    $PMF_LANG['ad_xmlrpc_latest'],
                    $available
                );
                // Installed phpMyFAQ version is outdated
                if (-1 == version_compare($installed, $available)) {
                    print '<br />' . $PMF_LANG['ad_you_should_update'];
                }
            }
        } else {
?>
                <p>
                    <form action="index.php" method="post">
                        <input type="hidden" name="param" value="version" />
                        <button class="btn btn-primary" type="submit">
                            <i class="icon-check icon-white"></i> <?php print $PMF_LANG["ad_xmlrpc_button"]; ?>
                        </button>
                    </form>
                </p>
<?php
        }
?>
                </p>
            </div>
            <div class="span5">
                <header>
                    <h3><?php print $PMF_LANG['ad_online_verification'] ?></h3>
                </header>
<?php
        $getJson = PMF_Filter::filterInput(INPUT_POST, 'getJson', FILTER_SANITIZE_STRING);
        if (!is_null($getJson) && 'verify' === $getJson) {

            $faqSystem    = new PMF_System();
            $localHashes  = $faqSystem->createHashes();
            $remoteHashes = file_get_contents(
                'http://www.phpmyfaq.de/api/verify/' . $faqConfig->get('main.currentVersion')
            );

            if (!is_array($remoteHashes)) {
                echo '<p class="alert alert-danger">phpMyFAQ version mismatch - no verification possible.</p>';
            } else {

                $diff = array_diff(
                    json_decode($localHashes, true),
                    json_decode($remoteHashes, true)
                );

                if (0 !== count($diff)) {
                    printf('<p class="alert alert-danger">%s</p>', $PMF_LANG["ad_verification_notokay"]);
                    print '<ul>';
                    foreach ($diff as $file => $hash) {
                        if ('created' === $file) {
                            continue;
                        }
                        printf(
                            '<li><span class="pmf-popover" data-original-title="SHA-1" data-content="%s">%s</span></li>',
                            $hash,
                            $file
                        );
                    }
                    print '</ul>';
                } else {
                    printf('<p class="alert alert-success">%s</p>', $PMF_LANG["ad_verification_okay"]);
                }
            }

        } else {
?>
                <p>
                    <form action="index.php" method="post">
                        <input type="hidden" name="getJson" value="verify" />
                        <button class="btn btn-primary" type="submit">
                            <i class="icon-certificate icon-white"></i> <?php print $PMF_LANG["ad_verification_button"] ?>
                        </button>
                    </form>
                </p>
<?php
        }
?>
                <script>$(function(){ $('span[class="pmf-popover"]').popover();});</script>
            </div>
        </section>

        <section class="row-fluid">
            <header>
                <h3><?php print $PMF_LANG['ad_system_info']; ?></h3>
            </header>
            <div class="pmf-system-information">
                <table class="table table-striped">
                <tbody>
                    <?php
                    $systemInformation = array(
                        'phpMyFAQ Version'    => $faqSystem->getVersion(),
                        'Server Software'     => $_SERVER['SERVER_SOFTWARE'],
                        'PHP Version'         => PHP_VERSION,
                        'Register Globals'    => ini_get('register_globals') == 1 ? 'on' : 'off',
                        'safe Mode'           => ini_get('safe_mode') == 1 ? 'on' : 'off',
                        'Open Basedir'        => ini_get('open_basedir') == 1 ? 'on' : 'off',
                        'DB Server'           => PMF_Db::getType(),
                        'DB Client Version'   => $faqConfig->getDb()->clientVersion(),
                        'DB Server Version'   => $faqConfig->getDb()->serverVersion(),
                        'Webserver Interface' => strtoupper(@php_sapi_name()),
                        'PHP Extensions'      => implode(', ', get_loaded_extensions())
                    );
                    foreach ($systemInformation as $name => $info): ?>
                    <tr>
                        <td class="span3"><strong><?php print $name ?></strong></td>
                        <td><?php print $info ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </div>

            <p>phpMyFAQ uses <a href="http://glyphicons.com/">Glyphicons</a>.</p>

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
        $message = sprintf(
            '<p class="alert alert-error">%s%s</p>',
            '<a class="close" data-dismiss="alert" href="#">&times;</a>',
            $error
        );
    } else {
        $message = sprintf('<p>%s</p>', $PMF_LANG['ad_auth_insert']);
    }
    if ($action == 'logout') {
        $message = sprintf(
            '<p class="alert alert-success">%s%s</p>',
            '<a class="close" data-dismiss="alert" href="#">&times;</a>',
            $PMF_LANG['ad_logout']
        );
    }
    
    if (isset($_SERVER['HTTPS']) || !$faqConfig->get('security.useSslForLogins')) {
?>

            <?php print $message ?>

            <form class="form-horizontal" action="index.php" method="post">

                <div class="control-group">
                    <label class="control-label" for="faqusername"><?php print $PMF_LANG["ad_auth_user"]; ?></label>
                    <div class="controls">
                        <input type="text" name="faqusername" id="faqusername" required="required" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="faqpassword"><?php print $PMF_LANG["ad_auth_passwd"]; ?></label>
                    <div class="controls">
                        <input type="password" name="faqpassword" id="faqpassword" required="required" />
                    </div>
                </div>

                <div class="control-group">
                    <div class="controls">
                        <label class="checkbox">
                            <input type="checkbox" id="faqrememberme" name="faqrememberme" value="rememberMe">
                            <?php print $PMF_LANG['rememberMe'] ?>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">
                        <?php print $PMF_LANG["ad_auth_ok"]; ?>
                    </button>
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

$faqConfig->getDb()->close();
