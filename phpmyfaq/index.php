<?php
/**
 * This is the main public frontend page of phpMyFAQ. It detects the browser's
 * language, gets and sets all cookie, post and get informations and includes
 * the templates we need and set all internal variables to the template
 * variables. That's all.
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2001-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-02-12
 */

//
// Check if config/database.php exist -> if not, redirect to installer
//
if (!file_exists('config/database.php')) {
    header("Location: install/setup.php");
    exit();
}

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Autoload classes, prepend and start the PHP session
//
require_once 'inc/Init.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

//
// Get language (default: english)
//
$Language = new PMF_Language();
$LANGCODE = $Language->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('main.language'));
// Preload English strings
require_once 'lang/language_en.php';


$showCaptcha = PMF_Filter::filterInput(INPUT_GET, 'gen', FILTER_SANITIZE_STRING);
if (isset($LANGCODE) && PMF_Language::isASupportedLanguage($LANGCODE) && is_null($showCaptcha)) {
    // Overwrite English strings with the ones we have in the current language,
    // but don't include UTF-8 encoded files, these will break the captcha images
    require_once 'lang/language_'.$LANGCODE.'.php';
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
PMF_Attachment_Factory::init($faqconfig->get('main.attachmentsStorageType'),
                             $faqconfig->get('main.defaultAttachmentEncKey'),
                             $faqconfig->get('main.enableAttachmentEncryption'));

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
if ($faqconfig->get('main.ssoSupport') && isset($_SERVER['REMOTE_USER'])) {
    $faqusername = trim($_SERVER['REMOTE_USER']);
    $faqpassword = '';
}
if (!is_null($faqusername) && !is_null($faqpassword)) {
    $user = new PMF_User_CurrentUser();
    if ($faqconfig->get('main.ldapSupport')) {
        $authLdap = new PMF_Auth_AuthLdap();
        $user->addAuth($authLdap, 'ldap');
    }
    if ($faqconfig->get('main.ssoSupport')) {
        $authSso = new PMF_Auth_AuthSso();
        $user->addAuth($authSso, 'sso');
    }
    if ($user->login($faqusername, $faqpassword)) {
        if ($user->getStatus() != 'blocked') {
            $auth   = true;
            if (empty($action)) {
                $action = $faqaction; // SSO logins don't have $faqaction
            }
        } else {
            $error           = $PMF_LANG['ad_auth_fail'] . ' (' . $faqusername . ')';
            $loginVisibility = '';
            $user            = null;
            $action          = 'main';
        }
    } else {
        // error
        $error           = $PMF_LANG['ad_auth_fail'];
        $loginVisibility = '';
        $user            = null;
        $action          = 'main';
    }

} else {
    // authenticate with session information
    $user = PMF_User_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));
    if ($user) {
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
    $user->deleteFromSession();
    $user   = null;
    $auth   = null;
    $action = 'main';
    $ssoLogout = $faqconfig->get('main.ssoLogoutRedirect');
    if ($faqconfig->get('main.ssoSupport') && !empty ($ssoLogout))
        header ("Location:$ssoLogout");
}

//
// Get current user and group id - default: -1
//
if (!is_null($user) && $user instanceof PMF_User_CurrentUser) {
    $current_user   = $user->getUserId();
    if ($user->perm instanceof PMF_Perm_PermMedium) {
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
$valid_mb_strings = array('ja', 'en', 'uni');
$mbLanguage       = ($PMF_LANG['metaLanguage'] != 'ja') ? 'uni' : $PMF_LANG['metaLanguage'];
if (function_exists('mb_language') && in_array($mbLanguage, $valid_mb_strings)) {
    mb_language($mbLanguage);
    mb_internal_encoding('utf-8');
}

//
// Found a session ID in _GET or _COOKIE?
//
$sid        = null;
$sid_get    = PMF_Filter::filterInput(INPUT_GET, PMF_GET_KEY_NAME_SESSIONID, FILTER_VALIDATE_INT);
$sid_cookie = PMF_Filter::filterInput(INPUT_COOKIE, PMF_COOKIE_NAME_SESSIONID, FILTER_VALIDATE_INT);
$faqsession = new PMF_Session($db, $Language);
// Note: do not track internal calls
$internal = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $internal = (strpos($_SERVER['HTTP_USER_AGENT'], 'phpMyFAQ%2F') === 0);
}
if (!$internal) {
    if (is_null($sid_get) && is_null($sid_cookie)) {
        // Create a per-site unique SID
        $faqsession->userTracking('new_session', 0);
    } else {
        if (!is_null($sid_cookie)) {
            $faqsession->checkSessionId($sid_cookie, $_SERVER['REMOTE_ADDR']);
        } else {
            $faqsession->checkSessionId($sid_get, $_SERVER['REMOTE_ADDR']);
        }
    }
}

//
// Is user tracking activated?
//
$sids = '';
if ($faqconfig->get('main.enableUserTracking')) {
    if (isset($sid)) {
        PMF_Session::setCookie($sid);
        if (is_null($sid_cookie)) {
            $sids = sprintf('sid=%d&amp;lang=%s&amp;', $sid, $LANGCODE);
        }
    } elseif (is_null($sid_get) || is_null($sid_cookie)) {
        if (is_null($sid_cookie)) {
            if (!is_null($sid_get)) {
                $sids = sprintf('sid=%d&amp;lang=%s&amp;', $sid_get, $LANGCODE);
            }
        }
    }
} else {
    if (!setcookie(PMF_GET_KEY_NAME_LANGUAGE, $LANGCODE, $_SERVER['REQUEST_TIME'] + PMF_LANGUAGE_EXPIRED_TIME)) {
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
$faq = new PMF_Faq($current_user, $current_groups);

//
// Create a new Category object
//
$category = new PMF_Category($current_user, $current_groups);

//
// Create a new Tags object
//
$oTag = new PMF_Tags($db, $Language);

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
    $title           = ' -  powered by phpMyFAQ ' . $faqconfig->get('main.currentVersion');
    $keywords        = '';
    $metaDescription = $faqconfig->get('main.metaDescription');
}

//
// found a solution ID?
//
$solution_id = PMF_Filter::filterInput(INPUT_GET, 'solution_id', FILTER_VALIDATE_INT);
if (!is_null($solution_id)) {
    $title    = ' -  powered by phpMyFAQ ' . $faqconfig->get('main.currentVersion');
    $keywords = '';
    $faqData  = $faq->getIdFromSolutionId($solution_id);
    if (is_array($faqData)) {
        $id              = $faqData['id'];
        $lang            = $faqData['lang'];
        $title           = ' - ' . $faq->getRecordTitle($id);
        $keywords        = ',' . $faq->getRecordKeywords($id);
        $metaDescription = PMF_Utils::makeShorterText(strip_tags($faqData['content']), 12);
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
if (isset($auth)) {
    $loginTemplate = 'loggedin.tpl';
} else {
    if (isset($_SERVER['HTTPS']) || !$faqconfig->get('main.useSslForLogins')) {
        $loginTemplate = 'loginbox.tpl';
    } else {
        $loginTemplate = 'secureswitch.tpl';
    }
}

if ($action != 'main') {
    $includeTemplate = $action . '.tpl';
    $includePhp      = $action . '.php';
    $writeLangAdress = '?sid=' . $sid;
} else {
    if (isset($solution_id) && is_numeric($solution_id)) {
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
if ($faqconfig->get('main.enableLoginOnly')) {
    if ($auth) {
        $indexSet = 'index.tpl';
    } else {
        if ('register' == $action || 'thankyou' == $action) {
            $indexSet = 'indexNewUser.tpl';
        } else {
            $indexSet = 'indexLogin.tpl';
        }
    }
} else {
    $indexSet = 'index.tpl';
}

//
// Load template files and set template variables
//
$tpl = new PMF_Template(array('index'        => $indexSet,
                              'loginBox'     => $loginTemplate,
                              'rightBox'     => $rightSidebarTemplate,
                              'writeContent' => $includeTemplate),
                              $faqconfig->get('main.templateSet'));

$usersOnLine    = $faqsession->getUsersOnline();
$totUsersOnLine = $usersOnLine[0] + $usersOnLine[1];
$systemUri      = PMF_Link::getSystemUri('index.php');

$helper = PMF_Helper_Category::getInstance();
$helper->setCategory($category);


$keywordsArray = array_merge(explode(',', $keywords), explode(',', $faqconfig->get('main.metaKeywords')));
$keywordsArray = array_filter($keywordsArray, 'strlen');
shuffle($keywordsArray);
$keywords = implode(',', $keywordsArray);

$main_template_vars = array(
    'msgRegisterUser'     => '<a href="?' . $sids . 'action=register">' . $PMF_LANG['msgRegisterUser'] . '</a>',
    'msgLoginUser'        => $PMF_LANG['msgLoginUser'],
    'title'               => $faqconfig->get('main.titleFAQ').$title,
    'baseHref'            => $systemUri,
    'version'             => $faqconfig->get('main.currentVersion'),
    'header'              => str_replace('"', '', $faqconfig->get('main.titleFAQ')),
    'metaTitle'           => str_replace('"', '', $faqconfig->get('main.titleFAQ')),
    'metaDescription'     => $metaDescription,
    'metaKeywords'        => $keywords,
    'metaPublisher'       => $faqconfig->get('main.metaPublisher'),
    'metaLanguage'        => $PMF_LANG['metaLanguage'],
    'metaCharset'         => 'utf-8', // backwards compability
    'phpmyfaqversion'     => $faqconfig->get('main.currentVersion'),
    'stylesheet'          => $PMF_LANG['dir'] == 'rtl' ? 'style.rtl' : 'style',
    'action'              => $action,
    'dir'                 => $PMF_LANG['dir'],
    'msgCategory'         => $PMF_LANG['msgCategory'],
    'showCategories'      => $helper->renderCategoryNavigation($cat),
    'languageBox'         => $PMF_LANG['msgLangaugeSubmit'],
    'writeLangAdress'     => $writeLangAdress,
    'switchLanguages'     => PMF_Language::selectLanguages($LANGCODE, true),
    'userOnline'          => $plr->getMsg('plmsgUserOnline', $totUsersOnLine) . ' | ' .
                             $plr->getMsg('plmsgGuestOnline', $usersOnLine[0]) .
                             $plr->getMsg('plmsgRegisteredOnline',$usersOnLine[1]),
    'stickyRecordsHeader' => $PMF_LANG['stickyRecordsHeader'],
    'copyright'           => 'powered by <a href="http://www.phpmyfaq.de" target="_blank">phpMyFAQ</a> ' . 
                             $faqconfig->get('main.currentVersion'),
    'registerUser'        => '<a href="?action=register">' . $PMF_LANG['msgRegistration'] . '</a>',
    'sendPassword'        => '<a href="./admin/password.php">' . $PMF_LANG['lostPassword'] . '</a>',
    'loginHeader'         => $PMF_LANG['msgLoginUser'],
    'loginMessage'        => (is_null($error) ? '' : '<p class="error">' . $error . '</p>'),
    'writeLoginPath'      => '?action=login',
    'faqloginaction'      => $action,
    'login'               => $PMF_LANG['ad_auth_ok'],
    'username'            => $PMF_LANG['ad_auth_user'],
    'password'            => $PMF_LANG['ad_auth_passwd']
);

if ('main' == $action || 'show' == $action) {
    if ('main' == $action && PMF_Configuration::getInstance()->get('search.useAjaxSearchOnStartpage')) {
        $tpl->processBlock('index', 'globalSuggestBox', array(
            'ajaxlanguage'                  => $LANGCODE,
            'msgDescriptionInstantResponse' => $PMF_LANG['msgDescriptionInstantResponse'],
            'msgSearch'                     => sprintf('<a class="help" href="index.php?action=search">%s</a>',
                                                   $PMF_LANG["msgAdvancedSearch"]
                                               )
           )
        );
    } else {
        $tpl->processBlock('index', 'globalSearchBox', array(
            'writeSendAdress' => '?'.$sids.'action=search',
            'searchBox'       => $PMF_LANG['msgSearch'],
            'categoryId'      => ($cat === 0) ? '%' : (int)$cat,
            'msgSearch'       => '<a class="help" href="index.php?'.$sids.'action=search">'.$PMF_LANG["msgAdvancedSearch"].'</a>'));
    }
}
                             
$stickyRecordsParams = $faq->getStickyRecords();
if (!isset($stickyRecordsParams['error'])) {
    $tpl->processBlock('index', 'stickyRecordsList', array(
        'stickyRecordsUrl'   => $stickyRecordsParams['url'],
        'stickyRecordsTitle' => $stickyRecordsParams['title']));
}

if ($faqconfig->get('main.enableRewriteRules')) {
    $links_template_vars = array(
        "faqHome"             => $faqconfig->get('main.referenceURL'),
        "msgSearch"           => '<a href="' . $systemUri . 'search.html">'.$PMF_LANG["msgAdvancedSearch"].'</a>',
        'msgAddContent'       => '<a href="' . $systemUri . 'addcontent.html">'.$PMF_LANG["msgAddContent"].'</a>',
        "msgQuestion"         => '<a href="' . $systemUri . 'ask.html">'.$PMF_LANG["msgQuestion"].'</a>',
        "msgOpenQuestions"    => '<a href="' . $systemUri . 'open.html">'.$PMF_LANG["msgOpenQuestions"].'</a>',
        'msgHelp'             => '<a href="' . $systemUri . 'help.html">'.$PMF_LANG["msgHelp"].'</a>',
        "msgContact"          => '<a href="' . $systemUri . 'contact.html">'.$PMF_LANG["msgContact"].'</a>',
        "backToHome"          => '<a href="' . $systemUri . 'index.html">'.$PMF_LANG["msgHome"].'</a>',
        "allCategories"       => '<a href="' . $systemUri . 'showcat.html">'.$PMF_LANG["msgShowAllCategories"].'</a>',
        'showInstantResponse' => '<a href="' . $systemUri . 'instantresponse.html">'.$PMF_LANG['msgInstantResponse'].'</a>',
        'showSitemap'         => '<a href="' . $systemUri . 'sitemap/A/'.$LANGCODE.'.html">'.$PMF_LANG['msgSitemap'].'</a>',
        'opensearch'          => $systemUri . 'opensearch.html');
} else {
    $links_template_vars = array(
        "faqHome"             => $faqconfig->get('main.referenceURL'),
        "msgSearch"           => '<a href="index.php?'.$sids.'action=search">'.$PMF_LANG["msgAdvancedSearch"].'</a>',
        "msgAddContent"       => '<a href="index.php?'.$sids.'action=add">'.$PMF_LANG["msgAddContent"].'</a>',
        "msgQuestion"         => '<a href="index.php?'.$sids.'action=ask&category_id='.$cat.'">'.$PMF_LANG["msgQuestion"].'</a>',
        "msgOpenQuestions"    => '<a href="index.php?'.$sids.'action=open">'.$PMF_LANG["msgOpenQuestions"].'</a>',
        "msgHelp"             => '<a href="index.php?'.$sids.'action=help">'.$PMF_LANG["msgHelp"].'</a>',
        "msgContact"          => '<a href="index.php?'.$sids.'action=contact">'.$PMF_LANG["msgContact"].'</a>',
        "allCategories"       => '<a href="index.php?'.$sids.'action=show">'.$PMF_LANG["msgShowAllCategories"].'</a>',
        "backToHome"          => '<a href="index.php?'.$sids.'">'.$PMF_LANG["msgHome"].'</a>',
        'showInstantResponse' => '<a href="index.php?'.$sids.'action=instantresponse">'.$PMF_LANG['msgInstantResponse'].'</a>',
        'showSitemap'         => '<a href="index.php?'.$sids.'action=sitemap&amp;lang='.$LANGCODE.'">'.$PMF_LANG['msgSitemap'].'</a>',
        'opensearch'          => $systemUri . 'opensearch.php');
}

//
// Add debug info if needed
//
if (DEBUG) {
    $debug_template_vars = array(
        'debugMessages' => '<div id="debug_main"><h2>DEBUG INFORMATION:</h2>' . $db->sqllog() . '</div>'
    );
} else {
    $debug_template_vars = array(
        'debugMessages' => ''
    );
}

//
// Get main template, set main variables
//
$tpl->processTemplate('index', array_merge($main_template_vars, $links_template_vars, $debug_template_vars));

//
// Show login box or logged-in user information
//
if (isset($auth)) {
    $tpl->processTemplate(
        'loginBox',
        array(
            'loggedinas'      => $PMF_LANG['ad_user_loggedin'],
            'currentuser'     => $user->getUserData('display_name'),
            'printAdminPath'  => (in_array(true, $permission)) ? 'admin/index.php' : '#',
            'adminSection'    => $PMF_LANG['adminSection'],
            'printLogoutPath' => '?action=logout',
            'logout'          => $PMF_LANG['ad_menu_logout']
        )
    );
} else {
    if (isset($_SERVER['HTTPS']) || !$faqconfig->get('main.useSslForLogins')) {
        $tpl->processTemplate(
            'loginBox',
            array(
                'msgLoginUser'    => $PMF_LANG['msgLoginUser'],
                'writeLoginPath'  => '?action=login',
                'faqloginaction'  => $action,
                'login'           => $PMF_LANG['ad_auth_ok'],
                'username'        => $PMF_LANG['ad_auth_user'],
                'password'        => $PMF_LANG['ad_auth_passwd'],
                'msgRegisterUser' => '<a href="?' . $sids . 'action=register">' . $PMF_LANG['msgRegisterUser'] . '</a>',
                'msgLoginFailed'  => $error,
                'msgLostPassword' => $PMF_LANG['lostPassword'],
                'loginVisibility' => $loginVisibility
            )
        );
    } else {
        $tpl->processTemplate(
            'loginBox',
            array(
                'secureloginurl'  => sprintf('https://%s%s', $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']),
                'securelogintext' => $PMF_LANG['msgSecureSwitch']
            )
        );
    }
}
$tpl->includeTemplate('loginBox', 'index');

// generate top ten list
if ($faqconfig->get('records.orderingPopularFaqs') == 'visits') {
    $param = 'visits';
} else {
    $param = 'voted';
}
    
$toptenParams = $faq->getTopTen($param);
if (!isset($toptenParams['error'])) {
    $tpl->processBlock('rightBox', 'toptenList', array(
        'toptenUrl'    => $toptenParams['url'],
        'toptenTitle'  => $toptenParams['title'],
        'toptenVisits' => $toptenParams[$param])
    );
} else {
    $tpl->processBlock('rightBox', 'toptenListError', array(
        'errorMsgTopTen' => $toptenParams['error'])
    );
}

$latestEntriesParams = $faq->getLatest();
if (!isset($latestEntriesParams['error'])) {
    $tpl->processBlock('rightBox', 'latestEntriesList', array(
        'latestEntriesUrl'   => $latestEntriesParams['url'],
        'latestEntriesTitle' => $latestEntriesParams['title'],
        'latestEntriesDate'  => $latestEntriesParams['date'])
    );
} else {
    $tpl->processBlock('rightBox', 'latestEntriesListError', array(
        'errorMsgLatest' => $latestEntriesParams['error'])
    );
}

if ('artikel' == $action || 'show' == $action) {
    // We need some Links from social networks
    $faqServices = new PMF_Services();
    $faqServices->setCategoryId($cat);
    $faqServices->setFaqId($id);
    $faqServices->setLanguage($lang);
    $faqServices->setQuestion($title);

    $faqHelper = PMF_Helper_Faq::getInstance();
    $faqHelper->setSsl((is_null($_SERVER['HTTPS']) ? false : true));
    
    $tpl->processBlock(
        'rightBox', 'socialLinks', array(
            'writeDiggMsgTag'        => 'Digg it!',
            'writeFacebookMsgTag'    => 'Share on Facebook',
            'writeTwitterMsgTag'     => 'Share on Twitter',
            'writeDeliciousMsgTag'   => 'Bookmark this on Delicious',
            'writePDFTag'            => $PMF_LANG['msgPDF'],
            'writePrintMsgTag'       => $PMF_LANG['msgPrintArticle'],
            'writeSend2FriendMsgTag' => $PMF_LANG['msgSend2Friend'],
            'link_digg'              => $faqServices->getDiggLink(),
            'link_facebook'          => $faqServices->getShareOnFacebookLink(),
            'link_twitter'           => $faqServices->getShareOnTwitterLink(),
            'link_delicious'         => $faqServices->getBookmarkOnDeliciousLink(),
            'link_email'             => $faqServices->getSuggestLink(),
            'link_pdf'               => $faqServices->getPdfLink(),
            'facebookLikeButton'    => $faqHelper->renderFacebookLikeButton($faqServices->getLink()),
        )
    );
}

$tpl->processTemplate(
    'rightBox', array(
        'writeTopTenHeader'   => $PMF_LANG['msgTopTen'],
        'writeNewestHeader'   => $PMF_LANG['msgLatestArticles'],
        'writeTagCloudHeader' => $PMF_LANG['msg_tags'],
        'writeTags'           => $oTag->printHTMLTagsCloud(),
        'msgAllCatArticles'   => $PMF_LANG['msgAllCatArticles'],
        'allCatArticles'      => $faq->showAllRecordsWoPaging($cat)
    )
);

$tpl->includeTemplate('rightBox', 'index');

//
// Include requested PHP file
//
require_once $includePhp;

//
// Send headers and print template
//
header("Expires: Thu, 07 Apr 1977 14:47:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: text/html; charset=utf-8");
header("Vary: Negotiate,Accept");

if (!DEBUG) {
    ob_start('ob_gzhandler');
}
$tpl->printTemplate();

$db->dbclose();
