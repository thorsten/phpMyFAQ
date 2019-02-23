<?php

error_reporting(-1);

/**
 * The main admin backend index file.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Bastian Poettner <bastian@poettner.net>
 * @author Meikel Katzengreis <meikel@katzengreis.com>
 * @author Minoru TODA <todam@netjapan.co.jp>
 * @author Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2002-09-16
 */

use phpMyFAQ\Attachment\Factory;
use phpMyFAQ\Auth\Ldap;
use phpMyFAQ\Auth\Sso;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Logging;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Template;
use phpMyFAQ\User\CurrentUser;

define('PMF_ROOT_DIR', dirname(__DIR__));

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require PMF_ROOT_DIR.'/src/Bootstrap.php';

// get language (default: english)
$Language = new Language($faqConfig);
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
// Preload English strings
require PMF_ROOT_DIR.'/lang/language_en.php';
$faqConfig->setLanguage($Language);

if (isset($LANGCODE) && Language::isASupportedLanguage($LANGCODE)) {
    // Overwrite English strings with the ones we have in the current language
    if (!file_exists(PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php')) {
        $LANGCODE = 'en';
    }
    require PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php';
} else {
    $LANGCODE = 'en';
}

//
// Initalizing static string wrapper
//
Strings::init($LANGCODE);

//
// Set actual template set name
//
Template::setTplSetName($faqConfig->get('main.templateSet'));

//
// Initialize attachment factory
//
Factory::init(
    $faqConfig->get('records.attachmentsStorageType'),
    $faqConfig->get('records.defaultAttachmentEncKey'),
    $faqConfig->get('records.enableAttachmentEncryption')
);

//
// Create a new phpMyFAQ system object
//
$faqSystem = new System();

//
// Create a new FAQ object
//
$faq = new Faq($faqConfig);

//
// use mbstring extension if available and when possible
//
$validMbStrings = array('ja', 'en', 'uni');
$mbLanguage = ($PMF_LANG['metaLanguage'] != 'ja') ? 'uni' : $PMF_LANG['metaLanguage'];
if (function_exists('mb_language') && in_array($mbLanguage, $validMbStrings)) {
    mb_language($mbLanguage);
    mb_internal_encoding('utf-8');
}

//
// Get user action
//
$action = Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
if (is_null($action)) {
    $action = Filter::filterInput(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
}

//
// Get possible redirect action
//
$redirectAction = Filter::filterInput(INPUT_POST, 'redirect-action', FILTER_SANITIZE_STRING);
if (is_null($action) && '' !== $redirectAction && 'logout' !== $redirectAction) {
    $action = $redirectAction;
}

// authenticate current user
$auth = null;
$error = '';
$faqusername = Filter::filterInput(INPUT_POST, 'faqusername', FILTER_SANITIZE_STRING);
$faqpassword = Filter::filterInput(INPUT_POST, 'faqpassword', FILTER_SANITIZE_STRING);
$faqremember = Filter::filterInput(INPUT_POST, 'faqrememberme', FILTER_SANITIZE_STRING);

// Set username via SSO
if ($faqConfig->get('security.ssoSupport') && isset($_SERVER['REMOTE_USER'])) {
    $faqusername = trim($_SERVER['REMOTE_USER']);
    $faqpassword = '';
}

// Login via local DB or LDAP or SSO
if (!is_null($faqusername) && !is_null($faqpassword)) {
    $user = new CurrentUser($faqConfig);
    if (!is_null($faqremember) && 'rememberMe' === $faqremember) {
        $user->enableRememberMe();
    }
    if ($faqConfig->get('ldap.ldapSupport') && function_exists('ldap_connect')) {
        try {
            $authLdap = new Ldap($faqConfig);
            $user->addAuth($authLdap, 'ldap');
        } catch (Exception $e) {
            $error = $e->getMessage().'<br>';
        }
    }
    if ($faqConfig->get('security.ssoSupport')) {
        $authSso = new Sso($faqConfig);
        $user->addAuth($authSso, 'sso');
    }
    if ($user->login($faqusername, $faqpassword)) {
        // login, if user account is NOT blocked
        if ($user->getStatus() != 'blocked') {
            $auth = true;
        } else {
            $error = $error.$PMF_LANG['ad_auth_fail'];
        }
    } else {
        // error
        $logging = new Logging($faqConfig);
        $logging->logAdmin($user, 'Loginerror\nLogin: '.$faqusername.'\nErrors: '.implode(', ', $user->errors));
        $error = $error.$PMF_LANG['ad_auth_fail'];
    }
} else {
    // Try to authenticate with cookie information
    $user = CurrentUser::getFromCookie($faqConfig);
    // authenticate with session information
    if (!$user instanceof CurrentUser) {
        $user = CurrentUser::getFromSession($faqConfig);
    }
    if ($user instanceof CurrentUser) {
        $auth = true;
    } else {
        $user = new CurrentUser($faqConfig);
    }
}

// logout
if ($action == 'logout' && $auth) {
    $user->deleteFromSession(true);
    $auth = null;
    $ssoLogout = $faqConfig->get('security.ssoLogoutRedirect');
    if ($faqConfig->get('security.ssoSupport') && !empty($ssoLogout)) {
        header("Location: $ssoLogout");
    }
}

//
// Get current admin user and group id - default: -1
//
if (isset($user) && is_object($user)) {
    $currentAdminUser = $user->getUserId();
    if ($user->perm instanceof MediumPermission) {
        $currentAdminGroups = $user->perm->getUserGroups($currentAdminUser);
    } else {
        $currentAdminGroups = array(-1);
    }
    if (0 === count($currentAdminGroups)) {
        $currentAdminGroups = array(-1);
    }
}

//
// Get action from _GET and _POST first
$ajax = Filter::filterInput(INPUT_GET, 'ajax', FILTER_SANITIZE_STRING);
if (is_null($ajax)) {
    $ajax = Filter::filterInput(INPUT_POST, 'ajax', FILTER_SANITIZE_STRING);
}

// if performing AJAX operation, needs to branch before header.php
if (isset($auth) && (count($user->perm->getAllUserRights($user->getUserId())) > 0 || $user->isSuperAdmin())) {
    if (isset($action) && isset($ajax)) {
        if ('ajax' === $action) {
            switch ($ajax) {
                // Attachments
                case 'att':           require 'ajax.attachment.php'; break;
                // Link verification
                case 'verifyURL':     require 'ajax.verifyurl.php'; break;
                case 'onDemandURL':   require 'ajax.ondemandurl.php'; break;
                // Categories
                case 'categories':    require 'ajax.category.php'; break;
                // Configuration management
                case 'config_list':   require 'ajax.config_list.php'; break;
                case 'config':        require 'ajax.config.php'; break;
                case 'elasticsearch': require 'ajax.elasticsearch.php'; break;
                // Tags management
                case 'tags':          require 'ajax.tags.php'; break;
                // Comments
                case 'comment':       require 'ajax.comment.php'; break;
                // Records
                case 'records':       require 'ajax.records.php'; break;
                case 'recordSave':    require 'record.save.php'; break;
                case 'recordAdd':     require 'record.add.php'; break;
                case 'autosave':      require 'ajax.autosave.php'; break;
                case 'markdown':      require 'ajax.markdown.php'; break;
                // Search
                case 'search':        require 'ajax.search.php'; break;
                // Users
                case 'user':          require 'ajax.user.php'; break;
                // Groups
                case 'group':         require 'ajax.group.php'; break;
                // Sections
                case 'section':       require 'ajax.section.php'; break;
                // Interface translation
                case 'trans':         require 'ajax.trans.php'; break;
                // Image upload
                case 'image':         require 'ajax.image.php'; break;
            }
            exit();
        }
    }
}

// are we running a PMF export file request?
switch ($action) {
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
require 'header.php';

$numRights = count($user->perm->getAllUserRights($user->getUserId()));

// User is authenticated
if (isset($auth) && ($numRights > 0 || $user->isSuperAdmin())) {
    if (!is_null($action)) {
        // the various sections of the admin area
        switch ($action) {
            // functions for user administration
            case 'user':              require 'user.php'; break;
            case 'group':             require 'group.php'; break;
            case 'section':           require 'section.php'; break;
            // functions for content administration
            case 'viewinactive':
            case 'viewactive':
            case 'view':              require 'record.show.php'; break;
            case 'searchfaqs':        require 'record.search.php'; break;
            case 'takequestion':
            case 'editentry':
            case 'copyentry':
            case 'editpreview':       require 'record.edit.php'; break;
            case 'insertentry':       require 'record.add.php'; break;
            case 'saveentry':         require 'record.save.php'; break;
            case 'delatt':            require 'record.delatt.php'; break;
            case 'question':          require 'record.questions.php'; break;
            case 'comments':          require 'record.comments.php'; break;
            // functions for tags
            case 'tags':              require 'tags.main.php'; break;
            case 'deletetag':         require 'tags.main.php'; break;
            // news administration
            case 'news':
            case 'add-news':
            case 'edit-news':
            case 'save-news':
            case 'update-news':
            case 'delete-news':       require 'news.php'; break;
            // category administration
            case 'content':
            case 'category':
            case 'savecategory':
            case 'updatecategory':
            case 'removecategory':
            case 'changecategory':
            case 'pastecategory':     require 'category.main.php'; break;
            case 'addcategory':       require 'category.add.php'; break;
            case 'editcategory':      require 'category.edit.php'; break;
            case 'translatecategory': require 'category.translate.php'; break;
            case 'deletecategory':    require 'category.delete.php'; break;
            case 'cutcategory':       require 'category.cut.php'; break;
            case 'movecategory':      require 'category.move.php'; break;
            case 'showcategory':      require 'category.showstructure.php'; break;
            // glossary
            case 'glossary':
            case 'saveglossary':
            case 'updateglossary':
            case 'deleteglossary':    require 'glossary.main.php'; break;
            case 'addglossary':       require 'glossary.add.php'; break;
            case 'editglossary':      require 'glossary.edit.php'; break;
            // functions for password administration
            case 'passwd':            require 'pwd.change.php'; break;
            // functions for session administration
            case 'adminlog':
            case 'deleteadminlog':    require 'stat.adminlog.php'; break;
            case 'viewsessions':
            case 'clear-visits':      require 'stat.main.php'; break;
            case 'sessionbrowse':     require 'stat.browser.php'; break;
            case 'viewsession':       require 'stat.show.php'; break;
            case 'clear-statistics':
            case 'statistics':        require 'stat.ratings.php'; break;
            case 'truncatesearchterms':
            case 'searchstats':       require 'stat.search.php'; break;
            // Reports
            case 'reports':           require 'report.main.php'; break;
            case 'reportview':        require 'report.view.php'; break;
            // Config administration
            case 'config':            require 'configuration.php'; break;
            case 'system':            require 'system.php'; break;
            case 'updateinstance':
            case 'instances':         require 'instances.php'; break;
            case 'editinstance':      require 'instances.edit.php'; break;
            case 'stopwordsconfig':   require 'stopwordsconfig.main.php'; break;
            case 'elasticsearch':     require 'elasticsearch.php'; break;
            case 'meta':
            case 'meta.update'; require 'meta.php'; break;
            case 'meta.edit':         require 'meta.edit.php'; break;
            // functions for backup administration
            case 'backup':            require 'backup.main.php'; break;
            case 'restore':           require 'backup.import.php'; break;
            // functions for FAQ export
            case 'export':            require 'export.main.php'; break;
            // attachment administration 
            case 'attachments':       require 'att.main.php'; break;

            default:                  echo 'Dave, this conversation can serve no purpose anymore. Goodbye.'; break;
        }
    } else {
        require 'dashboard.php';
    }
// User is authenticated, but has no rights
} elseif (isset($auth) && $numRights === 0) {
    require 'noperm.php';
// User is NOT authenticated
} else {
    require 'loginform.php';
}

require 'footer.php';

$faqConfig->getDb()->close();
