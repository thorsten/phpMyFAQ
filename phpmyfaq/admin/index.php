<?php

/**
 * The main admin backend index file.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Bastian Poettner <bastian@poettner.net>
 * @author    Meikel Katzengreis <meikel@katzengreis.com>
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */

use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\AdminLog;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Template;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\TwoFactor;
use phpMyFAQ\User\UserAuthentication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

define('PMF_ROOT_DIR', dirname(__DIR__));

//
// Define the named constant used as a check by any included PHP file
//
const IS_VALID_PHPMYFAQ = null;

//
// Bootstrapping
//
require PMF_ROOT_DIR . '/src/Bootstrap.php';

//
// Create Response & Request
//
$response = new Response();
$request = Request::createFromGlobals();

// get language (default: english)
$Language = new Language($faqConfig);
$faqLangCode = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
// Preload English strings
require PMF_ROOT_DIR . '/lang/language_en.php';
$faqConfig->setLanguage($Language);

if (isset($faqLangCode) && Language::isASupportedLanguage($faqLangCode)) {
    // Overwrite English strings with the ones we have in the current language
    if (!file_exists(PMF_ROOT_DIR . '/lang/language_' . $faqLangCode . '.php')) {
        $faqLangCode = 'en';
    }
    require PMF_ROOT_DIR . '/lang/language_' . $faqLangCode . '.php';
} else {
    $faqLangCode = 'en';
}

//
// Set translation class
//
try {
    Translation::create()
        ->setLanguagesDir(PMF_LANGUAGE_DIR)
        ->setDefaultLanguage('en')
        ->setCurrentLanguage($faqLangCode)
        ->setMultiByteLanguage();
} catch (Exception $e) {
    echo '<strong>Error:</strong> ' . $e->getMessage();
}

//
// Initializing static string wrapper
//
Strings::init($faqLangCode);

//
// Set actual template set name
//
Template::setTplSetName($faqConfig->get('main.templateSet'));

//
// Initialize attachment factory
//
AttachmentFactory::init(
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
// Get user action
//
$action = Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
if (is_null($action)) {
    $action = Filter::filterInput(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
}
if (!is_null($action)) {
    $action = Strings::htmlentities($action);
}

//
// Get possible redirect action
//
$redirectAction = Filter::filterInput(INPUT_POST, 'redirect-action', FILTER_SANITIZE_SPECIAL_CHARS);
if (is_null($action) && '' !== $redirectAction && 'logout' !== $redirectAction) {
    $action = $redirectAction;
}

//
// Authenticate current user
//
$error = '';
$faqusername = Filter::filterInput(INPUT_POST, 'faqusername', FILTER_SANITIZE_SPECIAL_CHARS);
$faqpassword = Filter::filterInput(INPUT_POST, 'faqpassword', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
$faqremember = Filter::filterInput(INPUT_POST, 'faqrememberme', FILTER_SANITIZE_SPECIAL_CHARS);

$token = Filter::filterInput(INPUT_POST, 'token', FILTER_SANITIZE_SPECIAL_CHARS);
$userid = Filter::filterInput(INPUT_POST, 'userid', FILTER_VALIDATE_INT);

//
// Logging user in if twofactor is enabled and token is given and validated, if not: returns error message
//
if (!is_null($token) && !is_null($userid)) {
    $user = new CurrentUser($faqConfig);
    $user->getUserById($userid);
    if (strlen((string) $token) === 6 && is_numeric((string) $token)) {
        $tfa = new TwoFactor($faqConfig);
        $res = $tfa->validateToken($token, $userid);
        if (!$res) {
            $error = Translation::get('msgTwofactorErrorToken');
            $action = 'twofactor';
        } else {
            $user->twoFactorSuccess();
            require 'header.php';
            require 'dashboard.php';
            exit();
        }
    } else {
        $error = Translation::get('msgTwofactorErrorToken');
        $action = 'twofactor';
    }
}

if (!isset($user)) {
    $user = new CurrentUser($faqConfig);
}

//
// Set username via SSO
//
if ($faqConfig->get('security.ssoSupport') && $request->server->get('REMOTE_USER') !== null) {
    $faqusername = trim((string) $request->server->get('REMOTE_USER'));
    $faqpassword = '';
}

//
// Login via local DB or LDAP or SSO
//
if (!is_null($faqusername) && !is_null($faqpassword)) {
    $userAuth = new UserAuthentication($faqConfig, $user);
    $userAuth->setRememberMe($faqremember ?? false);
    try {
        $user = $userAuth->authenticate($faqusername, $faqpassword);
        $userid = $user->getUserId();
        if ($userAuth->hasTwoFactorAuthentication()) {
            $action = 'twofactor';
        }
    } catch (Exception $e) {
        $logging = new AdminLog($faqConfig);
        $logging->log($user, 'Login-error\nLogin: ' . $faqusername . '\nErrors: ' . implode(', ', $user->errors));
        $action = 'login';
        $error = $e->getMessage();
    }
} else {
    // Try to authenticate with cookie information
    $user = CurrentUser::getCurrentUser($faqConfig);
}

//
// Logout
//
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);
if (
    $csrfToken &&
    Token::getInstance()->verifyToken('logout', $csrfToken) &&
    $action === 'logout' &&
    $user->isLoggedIn()
) {
    $user->deleteFromSession(true);
    $ssoLogout = $faqConfig->get('security.ssoLogoutRedirect');
    if ($faqConfig->get('security.ssoSupport') && !empty($ssoLogout)) {
        $response->isRedirect($ssoLogout);
        $response->send();
        exit();
    }
}
//
// Get current admin user and group id - default: -1
//
[ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($user);

//
// Get action from _GET and _POST first
//
$ajax = Filter::filterInput(INPUT_GET, 'ajax', FILTER_SANITIZE_SPECIAL_CHARS);
if (is_null($ajax)) {
    $ajax = Filter::filterInput(INPUT_POST, 'ajax', FILTER_SANITIZE_SPECIAL_CHARS);
}

// if performing AJAX operation, needs to branch before header.php
if (
    $user->isLoggedIn() &&
    ((is_countable($user->perm->getAllUserRights($user->getUserId())) ? count($user->perm->getAllUserRights($user->getUserId())) : 0) > 0 ||
        $user->isSuperAdmin())
) {
    if (isset($action) && isset($ajax)) {
        if ('ajax' === $action) {
            switch ($ajax) {
                // Attachments
                case 'att':
                    require 'api/attachment.php';
                    break;
                // Dashboard
                case 'dashboard':
                    require 'api/dashboard.php';
                    break;
                // Categories
                case 'categories':
                    require 'api/category.php';
                    break;
                // Configuration management
                case 'configuration-list':
                    require 'api/configuration-list.php';
                    break;
                case 'config':
                    require 'api/configuration.php';
                    break;
                case 'elasticsearch':
                    require 'api/elasticsearch.php';
                    break;
                // Tags management
                case 'tags':
                    require 'api/tags.php';
                    break;
                // Comments
                case 'comment':
                    require 'api/comment.php';
                    break;
                // Records
                case 'records':
                    require 'api/faqs.php';
                    break;
                case 'markdown':
                    require 'api/markdown.php';
                    break;
                // Search
                case 'search':
                    require 'api/search.php';
                    break;
                // Users
                case 'user':
                    require 'api/user.php';
                    break;
                // Groups
                case 'group':
                    require 'api/group.php';
                    break;
                // Image upload
                case 'image':
                    require 'api/image.php';
                    break;
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
    case 'reportexport':
        require 'report.export.php';
        exit();
}

// Header of the admin page including the navigation
require 'header.php';


if ($action === 'twofactor') {
    require 'twofactor.php';
    exit();
}

$numRights = is_countable($user->perm->getAllUserRights($user->getUserId())) ? count($user->perm->getAllUserRights($user->getUserId())) : 0;

// User is authenticated
if ($user->isLoggedIn() && $user->getUserId() > 0 && ($numRights > 0 || $user->isSuperAdmin())) {
    if (!is_null($action)) {
        // the various sections of the admin area
        switch ($action) {
            // functions for user administration
            case 'user':
                require 'user.php';
                break;
            case 'group':
                require 'group.php';
                break;
            // functions for content administration
            case 'faqs-overview':
                require 'faqs.overview.php';
                break;
            case 'viewinactive':
            case 'viewactive':
            case 'view':
                require 'record.show.php';
                break;
            case 'searchfaqs':
                require 'record.search.php';
                break;
            case 'takequestion':
            case 'editentry':
            case 'copyentry':
            case 'editpreview':
                require 'record.edit.php';
                break;
            case 'insertentry':
                require 'record.add.php';
                break;
            case 'saveentry':
                require 'record.save.php';
                break;
            case 'delatt':
                require 'record.delatt.php';
                break;
            case 'question':
                require 'record.questions.php';
                break;
            case 'comments':
                require 'record.comments.php';
                break;
            // functions for tags
            case 'tags':
            case 'delete-tag':
                require 'tags.php';
                break;
            // news administration
            case 'news':
            case 'add-news':
            case 'edit-news':
            case 'save-news':
            case 'update-news':
            case 'delete-news':
                require 'news.php';
                break;
            // category administration
            case 'content':
            case 'category':
            case 'savecategory':
            case 'updatecategory':
            case 'checkIfCategoryExists':
            case 'removecategory':
            case 'changecategory':
            case 'pastecategory':
                require 'category.main.php';
                break;
            case 'addcategory':
                require 'category.add.php';
                break;
            case 'editcategory':
                require 'category.edit.php';
                break;
            case 'translatecategory':
                require 'category.translate.php';
                break;
            case 'deletecategory':
                require 'category.delete.php';
                break;
            case 'cutcategory':
                require 'category.cut.php';
                break;
            case 'movecategory':
                require 'category.move.php';
                break;
            case 'showcategory':
                require 'category.showstructure.php';
                break;
            // glossary
            case 'glossary':
            case 'saveglossary':
            case 'updateglossary':
            case 'deleteglossary':
                require 'glossary.main.php';
                break;
            case 'addglossary':
                require 'glossary.add.php';
                break;
            case 'editglossary':
                require 'glossary.edit.php';
                break;
            // functions for password administration
            case 'passwd':
                require 'pwd.change.php';
                break;
            // functions for session administration
            case 'adminlog':
            case 'deleteadminlog':
                require 'stat.adminlog.php';
                break;
            case 'viewsessions':
            case 'clear-visits':
                require 'stat.main.php';
                break;
            case 'sessionbrowse':
                require 'stat.browser.php';
                break;
            case 'viewsession':
                require 'statistics.show.php';
                break;
            case 'clear-statistics':
            case 'statistics':
                require 'stat.ratings.php';
                break;
            case 'truncatesearchterms':
            case 'searchstats':
                require 'stat.search.php';
                break;
            // Reports
            case 'reports':
                require 'report.main.php';
                break;
            case 'reportview':
                require 'report.view.php';
                break;
            // Config administration
            case 'config':
                require 'configuration.php';
                break;
            case 'system':
                require 'system.php';
                break;
            case 'update-instance':
            case 'instances':
                require 'instances.php';
                break;
            case 'edit-instance':
                require 'instances.edit.php';
                break;
            case 'stopwordsconfig':
                require 'stopwords.php';
                break;
            case 'elasticsearch':
                require 'elasticsearch.php';
                break;
            case 'meta':
            case 'meta.update':
                require 'template-metadata.php';
                break;
            case 'meta.edit':
                require 'template-metadata.edit.php';
                break;
            // functions for backup administration
            case 'backup':
                require 'backup.main.php';
                break;
            case 'restore':
                require 'backup.import.php';
                break;
            // functions for FAQ export
            case 'export':
                require 'export.main.php';
                break;
            // attachment administration
            case 'attachments':
                require 'attachments.php';
                break;

            default:
                echo 'Dave, this conversation can serve no purpose anymore. Goodbye.';
                break;
        }
    } else {
        require 'dashboard.php';
    }
// User is authenticated but has no rights
} elseif ($user->isLoggedIn() && $numRights === 0) {
    require 'noperm.php';
// User is NOT authenticated
} else {
    require 'login.php';
}

require 'footer.php';

$faqConfig->getDb()->close();
