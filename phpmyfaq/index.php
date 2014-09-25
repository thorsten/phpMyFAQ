<?php
/**
 * This is the main public frontend page of phpMyFAQ. It detects the browser's
 * language, gets and sets all cookie, post and get informations and includes
 * the templates we need and set all internal variables to the template
 * variables. That's all.
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2001-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2001-02-12
 */

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require 'inc/Bootstrap.php';

//
// Get language (default: english)
//
$Language = new PMF_Language($faqConfig);
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
// Preload English strings
require_once 'lang/language_en.php';
$faqConfig->setLanguage($Language);

$showCaptcha = PMF_Filter::filterInput(INPUT_GET, 'gen', FILTER_SANITIZE_STRING);
if (isset($LANGCODE) && PMF_Language::isASupportedLanguage($LANGCODE) && is_null($showCaptcha)) {
    // Overwrite English strings with the ones we have in the current language,
    // but don't include UTF-8 encoded files, these will break the captcha images
    if (! file_exists('lang/language_' . $LANGCODE . '.php')) {
        $LANGCODE = 'en';
    }
    require_once 'lang/language_' . $LANGCODE . '.php';
} else {
    $LANGCODE = 'en';
}

//Load plurals support for selected language
$plr = new PMF_Language_Plurals($PMF_LANG);

//
// Initalizing static string wrapper
//
PMF_String::init($LANGCODE);

/**
 * Initialize attachment factory
 */
PMF_Attachment_Factory::init(
    $faqConfig->get('records.attachmentsStorageType'),
    $faqConfig->get('records.defaultAttachmentEncKey'),
    $faqConfig->get('records.enableAttachmentEncryption')
);

//
// Get user action
//
$action = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING, 'main');

//
// Authenticate current user
//
$auth = $error = null;
$loginVisibility = 'hidden';

$faqusername = PMF_Filter::filterInput(INPUT_POST, 'faqusername', FILTER_SANITIZE_STRING);
$faqpassword = PMF_Filter::filterInput(INPUT_POST, 'faqpassword', FILTER_SANITIZE_STRING);
$faqaction   = PMF_Filter::filterInput(INPUT_POST, 'faqloginaction', FILTER_SANITIZE_STRING);
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
    if ($faqConfig->get('security.ldapSupport') && function_exists('ldap_connect')) {
        $authLdap = new PMF_Auth_Ldap($faqConfig);
        $user->addAuth($authLdap, 'ldap');
    }
    if ($faqConfig->get('security.ssoSupport')) {
        $authSso = new PMF_Auth_Sso($faqConfig);
        $user->addAuth($authSso, 'sso');
    }
    if ($user->login($faqusername, $faqpassword)) {
        if ($user->getStatus() != 'blocked') {
            $auth = true;
            if (empty($action)) {
                $action = $faqaction; // SSO logins don't have $faqaction
            }
        } else {
            $error           = $PMF_LANG['ad_auth_fail'] . ' (' . $faqusername . ')';
            $loginVisibility = '';
            $user            = null;
            $action          = 'password' === $action ? 'password' : 'login';
        }
    } else {
        // error
        $error           = $PMF_LANG['ad_auth_fail'];
        $loginVisibility = '';
        $user            = null;
        $action          = 'password' === $action ? 'password' : 'login';
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

//
// Get current user rights
//
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

//
// Logout
//
if ('logout' === $action && isset($auth)) {
    $user->deleteFromSession(true);
    $user = $auth = null;
    $action = 'main';
    $ssoLogout = $faqConfig->get('security.ssoLogoutRedirect');
    if ($faqConfig->get('security.ssoSupport') && !empty ($ssoLogout)) {
        header('Location: ' . $ssoLogout);
    } else {
        header('Location: ' . $faqConfig->get('main.referenceURL'));
    }
}

//
// Get current user and group id - default: -1
//
if (!is_null($user) && $user instanceof PMF_User_CurrentUser) {
    $current_user   = $user->getUserId();
    if ($user->perm instanceof PMF_Perm_Medium) {
        $current_groups = $user->perm->getUserGroups($current_user);
    } else {
        $current_groups = array(-1);
    }
    if (0 == count($current_groups)) {
        $current_groups = array(-1);
    }
} else {
    $current_user   = -1;
    $current_groups = array(-1);
}

//
// Use mbstring extension if available and when possible
//
$validMbStrings = array('ja', 'en', 'uni');
$mbLanguage       = ($PMF_LANG['metaLanguage'] != 'ja') ? 'uni' : $PMF_LANG['metaLanguage'];
if (function_exists('mb_language') && in_array($mbLanguage, $validMbStrings)) {
    mb_language($mbLanguage);
    mb_internal_encoding('utf-8');
}

//
// Found a session ID in _GET or _COOKIE?
//
$sid        = null;
$sidGet     = PMF_Filter::filterInput(INPUT_GET, PMF_GET_KEY_NAME_SESSIONID, FILTER_VALIDATE_INT);
$sidCookie  = PMF_Filter::filterInput(INPUT_COOKIE, PMF_Session::PMF_COOKIE_NAME_SESSIONID, FILTER_VALIDATE_INT);
$faqsession = new PMF_Session($faqConfig);
// Note: do not track internal calls
$internal = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $internal = (strpos($_SERVER['HTTP_USER_AGENT'], 'phpMyFAQ%2F') === 0);
}
if (!$internal) {
    if (is_null($sidGet) && is_null($sidCookie)) {
        // Create a per-site unique SID
        $faqsession->userTracking('new_session', 0);
    } else {
        if (!is_null($sidCookie)) {
            $faqsession->checkSessionId($sidCookie, $_SERVER['REMOTE_ADDR']);
        } else {
            $faqsession->checkSessionId($sidGet, $_SERVER['REMOTE_ADDR']);
        }
    }
}

//
// Is user tracking activated?
//
$sids = '';
if ($faqConfig->get('main.enableUserTracking')) {
    if (isset($sid)) {
        PMF_Session::setCookie(PMF_Session::PMF_COOKIE_NAME_SESSIONID, $sid);
        if (is_null($sidCookie)) {
            $sids = sprintf('sid=%d&amp;lang=%s&amp;', $sid, $LANGCODE);
        }
    } elseif (is_null($sidGet) || is_null($sidCookie)) {
        if (is_null($sidCookie)) {
            if (!is_null($sidGet)) {
                $sids = sprintf('sid=%d&amp;lang=%s&amp;', $sidGet, $LANGCODE);
            }
        }
    }
} else {
    if (!PMF_Session::setCookie(PMF_Session::PMF_COOKIE_NAME_SESSIONID, $sid, $_SERVER['REQUEST_TIME'] + PMF_LANGUAGE_EXPIRED_TIME)) {
        $sids = sprintf('lang=%s&amp;', $LANGCODE);
    }
}

//
// Found a article language?
//
$lang = PMF_Filter::filterInput(INPUT_POST, 'artlang', FILTER_SANITIZE_STRING);
if (is_null($lang) && !PMF_Language::isASupportedLanguage($lang) ) {
    $lang = PMF_Filter::filterInput(INPUT_GET, 'artlang', FILTER_SANITIZE_STRING);
    if (is_null($lang) && !PMF_Language::isASupportedLanguage($lang) ) {
        $lang = $LANGCODE;
    }
}

//
// Create a new FAQ object
//
$faq = new PMF_Faq($faqConfig);
$faq->setUser($current_user);
$faq->setGroups($current_groups);

//
// Create a new Category object
//
$category = new PMF_Category($faqConfig, $current_groups, true);
$category->setUser($current_user);

//
// Create a new Tags object
//
$oTag = new PMF_Tags($faqConfig);

//
// Found a record ID?
//
$id = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!is_null($id)) {
    $title           = ' - ' . $faq->getRecordTitle($id);
    $keywords        = ',' . $faq->getRecordKeywords($id);
    $metaDescription = $faq->getRecordPreview($id);
} else {
    $id              = '';
    $title           = ' -  powered by phpMyFAQ ' . $faqConfig->get('main.currentVersion');
    $keywords        = '';
    $metaDescription = $faqConfig->get('main.metaDescription');
}

//
// found a solution ID?
//
$solutionId = PMF_Filter::filterInput(INPUT_GET, 'solution_id', FILTER_VALIDATE_INT);
if (! is_null($solutionId)) {
    $title    = ' -  powered by phpMyFAQ ' . $faqConfig->get('main.currentVersion');
    $keywords = '';
    $faqData  = $faq->getIdFromSolutionId($solutionId);
    if (is_array($faqData)) {
        $id              = $faqData['id'];
        $lang            = $faqData['lang'];
        $title           = ' - ' . $faq->getRecordTitle($id);
        $keywords        = ',' . $faq->getRecordKeywords($id);
        $metaDescription = str_replace('"', '', PMF_Utils::makeShorterText(strip_tags($faqData['content']), 12));
    }
}

//
// Handle the Tagging ID
//
$tag_id = PMF_Filter::filterInput(INPUT_GET, 'tagging_id', FILTER_VALIDATE_INT);
if (!is_null($tag_id)) {
    $title    = ' - ' . $oTag->getTagNameById($tag_id);
    $keywords = '';
}

//
// Handle the SiteMap
//
$letter = PMF_Filter::filterInput(INPUT_GET, 'letter', FILTER_SANITIZE_STRIPPED);
if (!is_null($letter) && (1 == PMF_String::strlen($letter))) {
    $title    = ' - ' . $letter . '...';
    $keywords = $letter;
}

//
// Found a category ID?
//
$cat         = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
$cat_from_id = -1;
if (is_numeric($id) && $id > 0) {
    $cat_from_id = $category->getCategoryIdFromArticle($id);
}
if ($cat_from_id != -1 && $cat == 0) {
    $cat = $cat_from_id;
}
$category->transform(0);
$category->collapseAll();
if ($cat != 0) {
    $category->expandTo($cat);
}
if (isset($cat) && ($cat != 0) && ($id == '') && isset($category->categoryName[$cat]['name'])) {
    $title = ' - '.$category->categoryName[$cat]['name'];
}

//
// Found an action request?
//
if (!isset($allowedVariables[$action])) {
    $action = 'main';
}

//
// Select the template for the requested page
//
if ($action != 'main') {
    $includeTemplate = $action . '.tpl';
    $includePhp      = $action . '.php';
    $writeLangAdress = '?sid=' . $sid;
} else {
    if (isset($solutionId) && is_numeric($solutionId)) {
        // show the record with the solution ID
        $includeTemplate = 'artikel.tpl';
        $includePhp      = 'artikel.php';
    } else {
        $includeTemplate = 'main.tpl';
        $includePhp      = 'main.php';
    }
    $writeLangAdress = '?sid=' . $sid;
}

//
// Set right column
//
// Check in any tags with at least one entry exist
//
$hasTags = $oTag->existTagRelations();
if ($hasTags && (($action == 'artikel') || ($action == 'show'))) {
    $rightSidebarTemplate = $action == 'artikel' ? 'catandtag.tpl' : 'tagcloud.tpl';
} else {
    $rightSidebarTemplate = 'startpage.tpl';
}


//
// Check if FAQ should be secured
//
if ($faqConfig->get('security.enableLoginOnly')) {
    if ($auth) {
        $indexSet = 'index.tpl';
    } else {
        switch($action) {
            case 'register':
            case 'thankyou':
                $indexSet = 'indexNewUser.tpl';
                break;
            case 'password':
                $indexSet = 'indexPassword.tpl';
                break;
            default:
                $indexSet = 'indexLogin.tpl';
                break;
        }
    }
} else {
    $indexSet = 'index.tpl';
}


//
// phpMyFAQ installtion is in maintenance mode
//
if ($faqConfig->get('main.maintenanceMode')) {
    $indexSet = 'indexMaintenance.tpl';
}


//
// Load template files and set template variables
//
$tpl = new PMF_Template(
    array(
        'index'        => $indexSet,
        'rightBox'     => $rightSidebarTemplate,
        'writeContent' => $includeTemplate
    ),
    $faqConfig->get('main.templateSet')
);

if ($faqConfig->get('main.enableUserTracking')) {
    $users       = $faqsession->getUsersOnline();
    $totUsers    = $users[0] + $users[1];
    $usersOnline = $plr->getMsg('plmsgUserOnline', $totUsers) . ' | ' .
                   $plr->getMsg('plmsgGuestOnline', $users[0]) .
                   $plr->getMsg('plmsgRegisteredOnline',$users[1]);
} else {
    $usersOnline = '';
}

$faqSystem = new PMF_System();

$categoryHelper = new PMF_Helper_Category();
$categoryHelper->setCategory($category);


$keywordsArray = array_merge(explode(',', $keywords), explode(',', $faqConfig->get('main.metaKeywords')));
$keywordsArray = array_filter($keywordsArray, 'strlen');
shuffle($keywordsArray);
$keywords = implode(',', $keywordsArray);

$faqLink        = new PMF_Link($faqSystem->getSystemUri($faqConfig), $faqConfig);
$currentPageUrl = $faqLink->getCurrentUrl();

if (is_null($error)) {
    $loginMessage = '<p>' . $PMF_LANG['ad_auth_insert'] . '</p>';
} else {
    $loginMessage = '<p class="error">' . $error . '</p>';
}

$tplMainPage = array(
    'msgLoginUser'         => $PMF_LANG['msgLoginUser'],
    'title'                => $faqConfig->get('main.titleFAQ') . $title,
    'baseHref'             => $faqSystem->getSystemUri($faqConfig),
    'version'              => $faqConfig->get('main.currentVersion'),
    'header'               => str_replace('"', '', $faqConfig->get('main.titleFAQ')),
    'metaTitle'            => str_replace('"', '', $faqConfig->get('main.titleFAQ') . $title),
    'metaDescription'      => $metaDescription,
    'metaKeywords'         => $keywords,
    'metaPublisher'        => $faqConfig->get('main.metaPublisher'),
    'metaLanguage'         => $PMF_LANG['metaLanguage'],
    'metaCharset'          => 'utf-8', // backwards compability
    'phpmyfaqversion'      => $faqConfig->get('main.currentVersion'),
    'stylesheet'           => $PMF_LANG['dir'] == 'rtl' ? 'style.rtl' : 'style',
    'currentPageUrl'       => preg_match( '/(\S+\/content\/\S+.html)\?\S*/', $currentPageUrl, $canonical ) === 1 ? $canonical[1] : $currentPageUrl,
    'action'               => $action,
    'dir'                  => $PMF_LANG['dir'],
    'msgCategory'          => $PMF_LANG['msgCategory'],
    'showCategories'       => $categoryHelper->renderNavigation($cat),
    'topCategories'        => $categoryHelper->renderMainCategories(),
    'msgExportAllFaqs'     => $PMF_LANG['msgExportAllFaqs'],
    'languageBox'          => $PMF_LANG['msgLangaugeSubmit'],
    'writeLangAdress'      => $writeLangAdress,
    'switchLanguages'      => PMF_Language::selectLanguages($LANGCODE, true),
    'userOnline'           => $usersOnline,
    'copyright'            => 'powered by <a href="http://www.phpmyfaq.de" target="_blank">phpMyFAQ</a> ' .
                              $faqConfig->get('main.currentVersion'),
    'registerUser'         => '<a href="?action=register">' . $PMF_LANG['msgRegistration'] . '</a>',
    'sendPassword'         => '<a href="?action=password">' . $PMF_LANG['lostPassword'] . '</a>',
    'loginHeader'          => $PMF_LANG['msgLoginUser'],
    'loginMessage'         => $loginMessage,
    'writeLoginPath'       => $faqSystem->getSystemUri($faqConfig) . '?' . PMF_Filter::getFilteredQueryString(),
    'faqloginaction'       => $action,
    'login'                => $PMF_LANG['ad_auth_ok'],
    'username'             => $PMF_LANG['ad_auth_user'],
    'password'             => $PMF_LANG['ad_auth_passwd'],
    'rememberMe'           => $PMF_LANG['rememberMe'],
    'headerChangePassword' => $PMF_LANG['ad_passwd_cop'],
    'msgUsername'          => $PMF_LANG['ad_auth_user'],
    'msgEmail'             => $PMF_LANG['ad_entry_email'],
    'msgSubmit'            => $PMF_LANG['msgNewContentSubmit']
);

if ('main' == $action || 'show' == $action) {
    if ('main' == $action && $faqConfig->get('search.useAjaxSearchOnStartpage')) {
        $tpl->parseBlock(
            'index',
            'globalSuggestBox',
            array(
                'ajaxlanguage'                  => $LANGCODE,
                'msgDescriptionInstantResponse' => $PMF_LANG['msgDescriptionInstantResponse'],
                'msgSearch'                     => sprintf(
                    '<a class="help" href="%sindex.php?action=search">%s</a>',
                    $faqSystem->getSystemUri($faqConfig),
                    $PMF_LANG["msgAdvancedSearch"]
                 )
            )
        );
    } else {
        $tpl->parseBlock(
            'index',
            'globalSearchBox',
            array(
                'writeSendAdress' => '?'.$sids.'action=search',
                'searchBox'       => $PMF_LANG['msgSearch'],
                'categoryId'      => ($cat === 0) ? '%' : (int)$cat,
                'msgSearch'       => sprintf(
                    '<a class="help" href="%sindex.php?action=search">%s</a>',
                    $faqSystem->getSystemUri($faqConfig),
                    $PMF_LANG["msgAdvancedSearch"]
                )
            )
        );
    }
}

$stickyRecordsParams = $faq->getStickyRecords();
if (!isset($stickyRecordsParams['error'])) {
    $tpl->parseBlock(
        'index',
        'stickyFaqs',
        array(
            'stickyRecordsHeader' => $PMF_LANG['stickyRecordsHeader'],
            'stickyRecordsList'   => $stickyRecordsParams['html']
        )
    );
}

if ($faqConfig->get('main.enableRewriteRules')) {
    $tplNavigation = array(
        "msgSearch"           => '<a href="' . $faqSystem->getSystemUri($faqConfig) . 'search.html">'.$PMF_LANG["msgAdvancedSearch"].'</a>',
        'msgAddContent'       => '<a href="' . $faqSystem->getSystemUri($faqConfig) . 'addcontent.html">'.$PMF_LANG["msgAddContent"].'</a>',
        "msgQuestion"         => '<a href="' . $faqSystem->getSystemUri($faqConfig) . 'ask.html">'.$PMF_LANG["msgQuestion"].'</a>',
        "msgOpenQuestions"    => '<a href="' . $faqSystem->getSystemUri($faqConfig) . 'open.html">'.$PMF_LANG["msgOpenQuestions"].'</a>',
        'msgHelp'             => '<a href="' . $faqSystem->getSystemUri($faqConfig) . 'help.html">'.$PMF_LANG["msgHelp"].'</a>',
        "msgContact"          => '<a href="' . $faqSystem->getSystemUri($faqConfig) . 'contact.html">'.$PMF_LANG["msgContact"].'</a>',
        'msgGlossary'         => '<a href="' . $faqSystem->getSystemUri($faqConfig) . 'glossary.html">' . $PMF_LANG['ad_menu_glossary'] . '</a>',
        "backToHome"          => '<a href="' . $faqSystem->getSystemUri($faqConfig) . 'index.html">'.$PMF_LANG["msgHome"].'</a>',
        "allCategories"       => '<a href="' . $faqSystem->getSystemUri($faqConfig) . 'showcat.html">'.$PMF_LANG["msgShowAllCategories"].'</a>',
        'showInstantResponse' => '<a href="' . $faqSystem->getSystemUri($faqConfig) . 'instantresponse.html">'.$PMF_LANG['msgInstantResponse'].'</a>',
        'showSitemap'         => '<a href="' . $faqSystem->getSystemUri($faqConfig) . 'sitemap/A/'.$LANGCODE.'.html">'.$PMF_LANG['msgSitemap'].'</a>',
        'opensearch'          => $faqSystem->getSystemUri($faqConfig) . 'opensearch.html');
} else {
    $tplNavigation = array(
        "msgSearch"           => '<a href="index.php?'.$sids.'action=search">'.$PMF_LANG["msgAdvancedSearch"].'</a>',
        "msgAddContent"       => '<a href="index.php?'.$sids.'action=add&cat='.$cat.'">'.$PMF_LANG["msgAddContent"].'</a>',
        "msgQuestion"         => '<a href="index.php?'.$sids.'action=ask&category_id='.$cat.'">'.$PMF_LANG["msgQuestion"].'</a>',
        "msgOpenQuestions"    => '<a href="index.php?'.$sids.'action=open">'.$PMF_LANG["msgOpenQuestions"].'</a>',
        "msgHelp"             => '<a href="index.php?'.$sids.'action=help">'.$PMF_LANG["msgHelp"].'</a>',
        "msgContact"          => '<a href="index.php?'.$sids.'action=contact">'.$PMF_LANG["msgContact"].'</a>',
        'msgGlossary'         => '<a href="index.php?'.$sids.'action=glossary">' . $PMF_LANG['ad_menu_glossary'] . '</a>',
        "allCategories"       => '<a href="index.php?'.$sids.'action=show">'.$PMF_LANG["msgShowAllCategories"].'</a>',
        "backToHome"          => '<a href="index.php?'.$sids.'">'.$PMF_LANG["msgHome"].'</a>',
        'showInstantResponse' => '<a href="index.php?'.$sids.'action=instantresponse">'.$PMF_LANG['msgInstantResponse'].'</a>',
        'showSitemap'         => '<a href="index.php?'.$sids.'action=sitemap&amp;lang='.$LANGCODE.'">'.$PMF_LANG['msgSitemap'].'</a>',
        'opensearch'          => $faqSystem->getSystemUri($faqConfig) . 'opensearch.php');
}

$tplNavigation['faqHome']             = $faqConfig->get('main.referenceURL');
$tplNavigation['activeQuickfind']     = ('instantresponse' == $action) ? 'active' : '';
$tplNavigation['activeAddContent']    = ('add' == $action) ? 'active' : '';
$tplNavigation['activeAddQuestion']   = ('ask' == $action) ? 'active' : '';
$tplNavigation['activeOpenQuestions'] = ('open' == $action) ? 'active' : '';

//
// Add debug info if needed
//
if (DEBUG) {
    $tplDebug = array(
        'debugMessages' => '<div id="debug_main"><h2>DEBUG INFORMATION:</h2>' . $faqConfig->getDb()->log() . '</div>'
    );
} else {
    $tplDebug = array(
        'debugMessages' => ''
    );
}

//
// Show login box or logged-in user information
//
if (isset($auth)) {
    if (in_array(true, $permission)) {
        $adminSection = sprintf(
            '<a href="%s">%s</a>',
            $faqSystem->getSystemUri($faqConfig) . 'admin/index.php',
            $PMF_LANG['adminSection']
        );
    } else {
        $adminSection = sprintf(
            '<a href="%s">%s</a>',
            $faqSystem->getSystemUri($faqConfig) . 'index.php?action=ucp',
            $PMF_LANG['headerUserControlPanel']
        );
    }

    $tpl->parseBlock(
        'index',
        'userloggedIn',
        array(
            'msgUserControl'         => $adminSection,
            'msgFullName'            => $PMF_LANG['ad_user_loggedin'] . $user->getLogin(),
            'msgLoginName'           => $user->getUserData('display_name'),
            'msgUserControlDropDown' => '<a href="?action=ucp">' . $PMF_LANG['headerUserControlPanel'] . '</a>',
            'msgLogoutUser'          => '<a href="?action=logout">' . $PMF_LANG['ad_menu_logout'] . '</a>',
            'activeUserControl'      => ('ucp' == $action) ? 'active' : ''
        )
    );

} else {

    if ($faqConfig->get('main.maintenanceMode')) {
        $msgLoginUser = '<a href="./admin/">%s</a>';
    } else {
        $msgLoginUser = '<a href="?action=login">%s</a>';
    }
    $tpl->parseBlock(
        'index',
        'notLoggedIn',
        array(
            'msgRegisterUser' => '<a href="?action=register">' . $PMF_LANG['msgRegisterUser'] . '</a>',
            'msgLoginUser'    => sprintf($msgLoginUser, $PMF_LANG['msgLoginUser']),
            'activeRegister'  => ('register' == $action) ? 'active' : '',
            'activeLogin'     => ('login' == $action) ? 'active' : ''
        )
    );
}

//
// Get main template, set main variables
//
$tpl->parse('index', array_merge($tplMainPage, $tplNavigation, $tplDebug));

// generate top ten list
if ($faqConfig->get('records.orderingPopularFaqs') == 'visits') {
    $param = 'visits';
} else {
    $param = 'voted';
}

$toptenParams = $faq->getTopTen($param);
if (!isset($toptenParams['error'])) {
    $tpl->parseBlock(
        'rightBox',
        'toptenList',
        array(
            'toptenUrl'    => $toptenParams['url'],
            'toptenTitle'  => $toptenParams['title'],
            'toptenVisits' => $toptenParams[$param]
        )
    );
} else {
    $tpl->parseBlock(
        'rightBox',
        'toptenListError',
        array(
            'errorMsgTopTen' => $toptenParams['error']
        )
    );
}

$latestEntriesParams = $faq->getLatest();
if (!isset($latestEntriesParams['error'])) {
    $tpl->parseBlock(
        'rightBox',
        'latestEntriesList',
        array(
            'latestEntriesUrl'   => $latestEntriesParams['url'],
            'latestEntriesTitle' => $latestEntriesParams['title'],
            'latestEntriesDate'  => $latestEntriesParams['date']
        )
    );
} else {
    $tpl->parseBlock('rightBox', 'latestEntriesListError', array(
        'errorMsgLatest' => $latestEntriesParams['error'])
    );
}

if ('artikel' == $action || 'show' == $action || is_numeric($solutionId)) {
    // We need some Links from social networks
    $faqServices = new PMF_Services($faqConfig);
    $faqServices->setCategoryId($cat);
    $faqServices->setFaqId($id);
    $faqServices->setLanguage($lang);
    $faqServices->setQuestion($faq->getRecordTitle($id));

    $faqHelper = new PMF_Helper_Faq($faqConfig);
    $faqHelper->setSsl((isset($_SERVER['HTTPS']) && is_null($_SERVER['HTTPS']) ? false : true));

    $tpl->parseBlock(
        'rightBox',
        'socialLinks',
        array(
            'baseHref'               => $faqSystem->getSystemUri($faqConfig),
            'writeDiggMsgTag'        => 'Digg it!',
            'writeFacebookMsgTag'    => 'Share on Facebook',
            'writeTwitterMsgTag'     => 'Share on Twitter',
            'writePDFTag'            => $PMF_LANG['msgPDF'],
            'writePrintMsgTag'       => $PMF_LANG['msgPrintArticle'],
            'writeSend2FriendMsgTag' => $PMF_LANG['msgSend2Friend'],
            'link_digg'              => $faqServices->getDiggLink(),
            'link_facebook'          => $faqServices->getShareOnFacebookLink(),
            'link_twitter'           => $faqServices->getShareOnTwitterLink(),
            'link_email'             => $faqServices->getSuggestLink(),
            'link_pdf'               => $faqServices->getPdfLink(),
            'facebookLikeButton'     => $faqHelper->renderFacebookLikeButton($faqServices->getLink())
        )
    );
}

$tpl->parse(
    'rightBox',
    array(
        'writeTopTenHeader'   => $PMF_LANG['msgTopTen'],
        'writeNewestHeader'   => $PMF_LANG['msgLatestArticles'],
        'writeTagCloudHeader' => $PMF_LANG['msg_tags'],
        'writeTags'           => $oTag->printHTMLTagsCloud(),
        'msgAllCatArticles'   => $PMF_LANG['msgAllCatArticles'],
        'allCatArticles'      => $faq->showAllRecordsWoPaging($cat)
    )
);

$tpl->merge('rightBox', 'index');

//
// Include requested PHP file
//
require_once $includePhp;

//
// Send headers and print template
//
$httpHeader = new PMF_Helper_Http();
$httpHeader->setContentType('text/html');
$httpHeader->addHeader();

if (!DEBUG) {
    ob_start('ob_gzhandler');
}

echo $tpl->render();

$faqConfig->getDb()->close();
