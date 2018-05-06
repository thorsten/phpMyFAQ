<?php

/**
 * This is the main public frontend page of phpMyFAQ. It detects the browser's
 * language, gets and sets all cookie, post and get informations and includes
 * the templates we need and set all internal variables to the template
 * variables. That's all.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2001-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2001-02-12
 */

use phpMyFAQ\Attachment\Factory;
use phpMyFAQ\Auth\Ldap as AuthLdap;
use phpMyFAQ\Auth\Sso as AuthSso;
use phpMyFAQ\Category;
use phpMyFAQ\Exception;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\FaqHelper as HelperFaq;
use phpMyFAQ\Helper\HttpHelper as HelperHttp;
use phpMyFAQ\Helper\CategoryHelper as HelperCategory;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Services;
use phpMyFAQ\Session;
use phpMyFAQ\Seo;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Tags;
use phpMyFAQ\Template;
use phpMyFAQ\User\CurrentUser;

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require 'src/Bootstrap.php';

//
// Get language (default: english)
//
$Language = new Language($faqConfig);
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
// Preload English strings
require_once 'lang/language_en.php';
$faqConfig->setLanguage($Language);

$showCaptcha = Filter::filterInput(INPUT_GET, 'gen', FILTER_SANITIZE_STRING);
if (isset($LANGCODE) && Language::isASupportedLanguage($LANGCODE) && is_null($showCaptcha)) {
    // Overwrite English strings with the ones we have in the current language,
    // but don't include UTF-8 encoded files, these will break the captcha images
    if (!file_exists('lang/language_'.$LANGCODE.'.php')) {
        $LANGCODE = 'en';
    }
    require_once 'lang/language_'.$LANGCODE.'.php';
} else {
    $LANGCODE = 'en';
}

//Load plurals support for selected language
$plr = new Plurals($PMF_LANG);

//
// Initializing static string wrapper
//
Strings::init($LANGCODE);

/*
 * Initialize attachment factory
 */
Factory::init(
    $faqConfig->get('records.attachmentsStorageType'),
    $faqConfig->get('records.defaultAttachmentEncKey'),
    $faqConfig->get('records.enableAttachmentEncryption')
);

//
// Get user action
//
$action = Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING, 'main');

//
// Authenticate current user
//
$auth = $error = null;
$loginVisibility = 'hidden';

$faqusername = Filter::filterInput(INPUT_POST, 'faqusername', FILTER_SANITIZE_STRING);
$faqpassword = Filter::filterInput(INPUT_POST, 'faqpassword', FILTER_SANITIZE_STRING);
$faqaction = Filter::filterInput(INPUT_POST, 'faqloginaction', FILTER_SANITIZE_STRING);
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
            $authLdap = new AuthLdap($faqConfig);
            $user->addAuth($authLdap, 'ldap');
        } catch (Exception $e) {
            $error = $e->getMessage().'<br>';
        }
    }
    if ($faqConfig->get('security.ssoSupport')) {
        $authSso = new AuthSso($faqConfig);
        $user->addAuth($authSso, 'sso');
    }
    if ($user->login($faqusername, $faqpassword)) {
        if ($user->getStatus() != 'blocked') {
            $auth = true;
            if (empty($action)) {
                $action = $faqaction; // SSO logins don't have $faqaction
            }
        } else {
            $error = $error.$PMF_LANG['ad_auth_fail'].' ('.$faqusername.')';
            $loginVisibility = '';
            $action = 'password' === $action ? 'password' : 'login';
        }
    } else {
        // error
        $error = $error.$PMF_LANG['ad_auth_fail'];
        $loginVisibility = '';
        $action = 'password' === $action ? 'password' : 'login';
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

//
// Logout
//
if ('logout' === $action && isset($auth)) {
    $user->deleteFromSession(true);
    $auth = null;
    $action = 'main';
    $ssoLogout = $faqConfig->get('security.ssoLogoutRedirect');
    if ($faqConfig->get('security.ssoSupport') && !empty($ssoLogout)) {
        header('Location: '.$ssoLogout);
    } else {
        header('Location: '.$faqConfig->getDefaultUrl());
    }
}

//
// Get current user and group id - default: -1
//
if (!is_null($user) && $user instanceof CurrentUser) {
    $current_user = $user->getUserId();
    if ($user->perm instanceof Medium) {
        $current_groups = $user->perm->getUserGroups($current_user);
    } else {
        $current_groups = array(-1);
    }
    if (0 == count($current_groups)) {
        $current_groups = array(-1);
    }
} else {
    $current_user = -1;
    $current_groups = array(-1);
}

//
// Use mbstring extension if available and when possible
//
$validMbStrings = array('ja', 'en', 'uni');
$mbLanguage = ($PMF_LANG['metaLanguage'] != 'ja') ? 'uni' : $PMF_LANG['metaLanguage'];
if (function_exists('mb_language') && in_array($mbLanguage, $validMbStrings)) {
    mb_language($mbLanguage);
    mb_internal_encoding('utf-8');
}

//
// Found a session ID in _GET or _COOKIE?
//
$sid = null;
$sidGet = Filter::filterInput(INPUT_GET, PMF_GET_KEY_NAME_SESSIONID, FILTER_VALIDATE_INT);
$sidCookie = Filter::filterInput(INPUT_COOKIE, Session::PMF_COOKIE_NAME_SESSIONID, FILTER_VALIDATE_INT);
$faqsession = new Session($faqConfig);
// Note: do not track internal calls
$internal = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $internal = (strpos($_SERVER['HTTP_USER_AGENT'], 'phpMyFAQ%2F') === 0);
}
if (!$internal) {
    if (is_null($sidGet) && is_null($sidCookie)) {
        // Create a per-site unique SID
        try {
            $faqsession->userTracking('new_session', 0);
        } catch (Exception $e) {
            $pmfExceptions[] = $e->getMessage();
        }
    } else {
        try {
            if (!is_null($sidCookie)) {
                $faqsession->checkSessionId($sidCookie, $_SERVER['REMOTE_ADDR']);
            } else {
                $faqsession->checkSessionId($sidGet, $_SERVER['REMOTE_ADDR']);
            }
        } catch (Exception $e) {
            $pmfExceptions[] = $e->getMessage();
        }
    }
}

//
// Is user tracking activated?
//
$sids = '';
if ($faqConfig->get('main.enableUserTracking')) {
    if (isset($sid)) {
        Session::setCookie(Session::PMF_COOKIE_NAME_SESSIONID, $sid);
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
    if (!Session::setCookie(Session::PMF_COOKIE_NAME_SESSIONID, $sid, $_SERVER['REQUEST_TIME'] + Language_EXPIRED_TIME)) {
        $sids = sprintf('lang=%s&amp;', $LANGCODE);
    }
}

//
// Found a article language?
//
$lang = Filter::filterInput(INPUT_POST, 'artlang', FILTER_SANITIZE_STRING);
if (is_null($lang) && !Language::isASupportedLanguage($lang)) {
    $lang = Filter::filterInput(INPUT_GET, 'artlang', FILTER_SANITIZE_STRING);
    if (is_null($lang) && !Language::isASupportedLanguage($lang)) {
        $lang = $LANGCODE;
    }
}

//
// Create a new FAQ object
//
$faq = new Faq($faqConfig);
$faq->setUser($current_user);
$faq->setGroups($current_groups);

//
// Create a new CategoryHelper object
//
$category = new Category($faqConfig, $current_groups, true);
$category->setUser($current_user);

//
// Create a new Tags object
//
$oTag = new Tags($faqConfig);

//
// Create URL
//
$faqSystem = new System();
$faqLink = new Link($faqSystem->getSystemUri($faqConfig), $faqConfig);
$currentPageUrl = $faqLink->getCurrentUrl();

//
// Found a record ID?
//
$id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!is_null($id)) {
    $faq->getRecord($id);
    $title = ' - '.$faq->faqRecord['title'];
    $keywords = ','.$faq->faqRecord['keywords'];
    $metaDescription = str_replace('"', '', strip_tags($faq->getRecordPreview($id)));
    $url = sprintf(
        '%sindex.php?%saction=faq&cat=%d&id=%d&artlang=%s',
        $faqConfig->getDefaultUrl(),
        $sids,
        $category->getCategoryIdFromArticle($id),
        $id,
        $lang
    );
    $faqLink = new Link($url, $faqConfig);
    $faqLink->itemTitle = $faq->faqRecord['title'];
    $currentPageUrl = $faqLink->toString();
} else {
    $id = '';
    $title = ' -  powered by phpMyFAQ '.$faqConfig->get('main.currentVersion');
    $keywords = '';
    $metaDescription = str_replace('"', '', $faqConfig->get('main.metaDescription'));
}

//
// found a solution ID?
//
$solutionId = Filter::filterInput(INPUT_GET, 'solution_id', FILTER_VALIDATE_INT);
if (!is_null($solutionId)) {
    $title = ' -  powered by phpMyFAQ '.$faqConfig->get('main.currentVersion');
    $keywords = '';
    $faqData = $faq->getIdFromSolutionId($solutionId);
    if (is_array($faqData)) {
        $id = $faqData['id'];
        $lang = $faqData['lang'];
        $title = ' - '.$faq->getRecordTitle($id);
        $keywords = ','.$faq->getRecordKeywords($id);
        $metaDescription = str_replace('"', '', Utils::makeShorterText(strip_tags($faqData['content']), 12));
        $url = sprintf(
            '%sindex.php?%saction=faq&cat=%d&id=%d&artlang=%s',
            $faqConfig->getDefaultUrl(),
            $sids,
            $faqData['category_id'],
            $id,
            $lang
        );
        $faqLink = new Link($url, $faqConfig);
        $faqLink->itemTitle = $faqData['question'];
        $currentPageUrl = $faqLink->toString();
    }
}

//
// Handle the Tagging ID
//
$tag_id = Filter::filterInput(INPUT_GET, 'tagging_id', FILTER_VALIDATE_INT);
if (!is_null($tag_id)) {
    $title = ' - '.$oTag->getTagNameById($tag_id);
    $keywords = '';
}

//
// Handle the SiteMap
//
$letter = Filter::filterInput(INPUT_GET, 'letter', FILTER_SANITIZE_STRIPPED);
if (!is_null($letter) && (1 == Strings::strlen($letter))) {
    $title = ' - '.$letter.'...';
    $keywords = $letter;
}

//
// Found a category ID?
//
$cat = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
$categoryFromId = -1;
if (is_numeric($id) && $id > 0) {
    $categoryFromId = $category->getCategoryIdFromArticle($id);
}
if ($categoryFromId != -1 && $cat == 0) {
    $cat = $categoryFromId;
}
$category->transform(0);
$category->collapseAll();
if ($cat != 0) {
    $category->expandTo($cat);
}
if (isset($cat) && ($cat != 0) && ($id == '') && isset($category->categoryName[$cat]['name'])) {
    $title = ' - '.$category->categoryName[$cat]['name'];
    $metaDescription = $category->categoryName[$cat]['description'];
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
if ($action !== 'main') {
    $includeTemplate = $action.'.html';
    $includePhp = $action.'.php';
    $writeLangAdress = '?sid='.$sid;
} else {
    if (isset($solutionId) && is_numeric($solutionId)) {
        // show the record with the solution ID
        $includeTemplate = 'faq.html';
        $includePhp = 'faq.php';
    } else {
        $includeTemplate = 'startpage.html';
        $includePhp = 'startpage.php';
    }
    $writeLangAdress = '?sid='.$sid;
}

//
// Set right column
//
if (($action == 'artikel') || ($action == 'show')) {
    $rightSidebarTemplate = $action == 'artikel' ? 'catandtag.html' : 'tagcloud.html';
} else {
    $rightSidebarTemplate = 'startpage.html';
}

//
// Check if FAQ should be secured
//
if ($faqConfig->get('security.enableLoginOnly')) {
    if ($auth) {
        $indexSet = 'index.html';
    } else {
        switch ($action) {
            case 'register':
            case 'thankyou':
                $indexSet = 'indexNewUser.html';
                break;
            case 'password':
                $indexSet = 'indexPassword.html';
                break;
            default:
                $indexSet = 'indexLogin.html';
                break;
        }
    }
} else {
    $indexSet = 'index.html';
}

//
// phpMyFAQ installation is in maintenance mode
//
if ($faqConfig->get('main.maintenanceMode')) {
    $indexSet = 'indexMaintenance.html';
}

//
// Load template files and set template variables
//
$tpl = new Template(
    array(
        'index' => $indexSet,
        'writeContent' => $includeTemplate,
    ),
    $faqConfig->get('main.templateSet')
);

if ($faqConfig->get('main.enableUserTracking')) {
    $users = $faqsession->getUsersOnline();
    $totUsers = $users[0] + $users[1];
    $usersOnline = $plr->getMsg('plmsgUserOnline', $totUsers).' | '.
                   $plr->getMsg('plmsgGuestOnline', $users[0]).
                   $plr->getMsg('plmsgRegisteredOnline', $users[1]);
} else {
    $usersOnline = '';
}

$categoryHelper = new HelperCategory();
$categoryHelper->setCategory($category);
$categoryHelper->setConfiguration($faqConfig);

$keywordsArray = array_merge(explode(',', $keywords), explode(',', $faqConfig->get('main.metaKeywords')));
$keywordsArray = array_filter($keywordsArray, 'strlen');
shuffle($keywordsArray);
$keywords = implode(',', $keywordsArray);

if (!is_null($error)) {
    $loginMessage = '<p class="error">'.$error.'</p>';
} else {
    $loginMessage = '';
}

$faqSeo = new Seo($faqConfig);

$tplMainPage = array(
    'msgLoginUser' => $user->isLoggedIn() ? $user->getUserData('display_name') : $PMF_LANG['msgLoginUser'],
    'title' => Strings::htmlspecialchars($faqConfig->get('main.titleFAQ').$title),
    'baseHref' => $faqSystem->getSystemUri($faqConfig),
    'version' => $faqConfig->get('main.currentVersion'),
    'header' => Strings::htmlspecialchars(str_replace('"', '', $faqConfig->get('main.titleFAQ'))),
    'metaTitle' => Strings::htmlspecialchars(str_replace('"', '', $faqConfig->get('main.titleFAQ').$title)),
    'metaDescription' => Strings::htmlspecialchars($metaDescription),
    'metaKeywords' => Strings::htmlspecialchars($keywords),
    'metaPublisher' => $faqConfig->get('main.metaPublisher'),
    'metaLanguage' => $PMF_LANG['metaLanguage'],
    'metaCharset' => 'utf-8', // backwards compability
    'metaRobots' => $faqSeo->getMetaRobots($action),
    'phpmyfaqversion' => $faqConfig->get('main.currentVersion'),
    'stylesheet' => $PMF_LANG['dir'] == 'rtl' ? 'style.rtl' : 'style',
    'currentPageUrl' => $currentPageUrl,
    'action' => $action,
    'dir' => $PMF_LANG['dir'],
    'writeSendAdress' => '?'.$sids.'action=search',
    'searchBox' => $PMF_LANG['msgSearch'],
    'categoryId' => ($cat === 0) ? '%' : (int) $cat,
    'headerCategories' => $PMF_LANG['msgFullCategories'],
    'msgCategory' => $PMF_LANG['msgCategory'],
    'showCategories' => $categoryHelper->renderNavigation($cat),
    'topCategories' => $categoryHelper->renderMainCategories(),
    'msgExportAllFaqs' => $PMF_LANG['msgExportAllFaqs'],
    'languageBox' => $PMF_LANG['msgLanguageSubmit'],
    'writeLangAdress' => $writeLangAdress,
    'switchLanguages' => Language::selectLanguages($LANGCODE, true),
    // 'stickyRecordsHeader' => $PMF_LANG['stickyRecordsHeader'],
    'userOnline' => $usersOnline,
    'copyright' => 'powered by <a href="https://www.phpmyfaq.de" target="_blank">phpMyFAQ</a> '.
                              $faqConfig->get('main.currentVersion'),
    'registerUser' => $faqConfig->get('security.enableRegistration') ? '<a href="?action=register">'.$PMF_LANG['msgRegistration'].'</a>' : '',
    'sendPassword' => '<a href="?action=password">'.$PMF_LANG['lostPassword'].'</a>',
    'msgFullName' => $PMF_LANG['ad_user_loggedin'].$user->getLogin(),
    'msgLoginName' => $user->getUserData('display_name'),
    'loginHeader' => $PMF_LANG['msgLoginUser'],
    'loginMessage' => $loginMessage,
    'writeLoginPath' => $faqSystem->getSystemUri($faqConfig).'?'.Filter::getFilteredQueryString(),
    'faqloginaction' => $action,
    'login' => $PMF_LANG['ad_auth_ok'],
    'username' => $PMF_LANG['ad_auth_user'],
    'password' => $PMF_LANG['ad_auth_passwd'],
    'rememberMe' => $PMF_LANG['rememberMe'],
    'headerChangePassword' => $PMF_LANG['ad_passwd_cop'],
    'msgUsername' => $PMF_LANG['ad_auth_user'],
    'msgEmail' => $PMF_LANG['ad_entry_email'],
    'msgSubmit' => $PMF_LANG['msgNewContentSubmit'],
);

$tpl->parseBlock(
    'index',
    'categoryListSection',
    array(
        'showCategories' => $categoryHelper->renderNavigation($cat),
        'categoryDropDown' => $categoryHelper->renderCategoryDropDown(),
    )
);

if ('main' == $action || 'show' == $action) {
    $tpl->parseBlock(
        'index',
        'globalSearchBox',
        array(
            'writeSendAdress' => '?'.$sids.'action=search',
            'searchBox' => $PMF_LANG['msgSearch'],
            'categoryId' => ($cat === 0) ? '%' : (int) $cat,
            'msgSearch' => sprintf(
                '<a class="help" href="%sindex.php?action=search">%s</a>',
                $faqSystem->getSystemUri($faqConfig),
                $PMF_LANG['msgAdvancedSearch']
            ),
        )
    );
}

if ($faqConfig->get('main.enableRewriteRules')) {
    $tplNavigation = array(
        'msgSearch' => '<a class="nav-link" href="'.$faqSystem->getSystemUri($faqConfig).'search.html">'.$PMF_LANG['msgAdvancedSearch'].'</a>',
        'msgAddContent' => '<a class="nav-link" href="'.$faqSystem->getSystemUri($faqConfig).'addcontent.html">'.$PMF_LANG['msgAddContent'].'</a>',
        'msgQuestion' => '<a class="nav-link" href="'.$faqSystem->getSystemUri($faqConfig).'ask.html">'.$PMF_LANG['msgQuestion'].'</a>',
        'msgOpenQuestions' => '<a class="nav-link" href="'.$faqSystem->getSystemUri($faqConfig).'open.html">'.$PMF_LANG['msgOpenQuestions'].'</a>',
        'msgHelp' => '<a href="'.$faqSystem->getSystemUri($faqConfig).'help.html">'.$PMF_LANG['msgHelp'].'</a>',
        'msgContact' => '<a href="'.$faqSystem->getSystemUri($faqConfig).'contact.html">'.$PMF_LANG['msgContact'].'</a>',
        'msgGlossary' => '<a href="'.$faqSystem->getSystemUri($faqConfig).'glossary.html">'.$PMF_LANG['ad_menu_glossary'].'</a>',
        'backToHome' => '<a href="'.$faqSystem->getSystemUri($faqConfig).'index.html">'.$PMF_LANG['msgHome'].'</a>',
        'allCategories' => '<a class="nav-link" href="'.$faqSystem->getSystemUri($faqConfig).'showcat.html">'.$PMF_LANG['msgShowAllCategories'].'</a>',
        'faqOverview' => '<a href="'.$faqSystem->getSystemUri($faqConfig).'overview.html">'.$PMF_LANG['faqOverview'].'</a>',
        'showSitemap' => '<a href="'.$faqSystem->getSystemUri($faqConfig).'sitemap/A/'.$LANGCODE.'.html">'.$PMF_LANG['msgSitemap'].'</a>',
        'opensearch' => $faqSystem->getSystemUri($faqConfig).'opensearch.html',
        'msgUserRemoval' => '<a href="'.$faqSystem->getSystemUri($faqConfig).'request-removal.html">'.$PMF_LANG['msgUserRemoval'].'</a>');
} else {
    $tplNavigation = array(
        'msgSearch' => '<a class="nav-link" href="index.php?'.$sids.'action=search">'.$PMF_LANG['msgAdvancedSearch'].'</a>',
        'msgAddContent' => '<a class="nav-link" href="index.php?'.$sids.'action=add&cat='.$cat.'">'.$PMF_LANG['msgAddContent'].'</a>',
        'msgQuestion' => '<a class="nav-link" href="index.php?'.$sids.'action=ask&category_id='.$cat.'">'.$PMF_LANG['msgQuestion'].'</a>',
        'msgOpenQuestions' => '<a class="nav-link" href="index.php?'.$sids.'action=open">'.$PMF_LANG['msgOpenQuestions'].'</a>',
        'msgHelp' => '<a href="index.php?'.$sids.'action=help">'.$PMF_LANG['msgHelp'].'</a>',
        'msgContact' => '<a href="index.php?'.$sids.'action=contact">'.$PMF_LANG['msgContact'].'</a>',
        'msgGlossary' => '<a href="index.php?'.$sids.'action=glossary">'.$PMF_LANG['ad_menu_glossary'].'</a>',
        'allCategories' => '<a href="index.php?'.$sids.'action=show">'.$PMF_LANG['msgShowAllCategories'].'</a>',
        'faqOverview' => '<a href="index.php?'.$sids.'action=overview">'.$PMF_LANG['faqOverview'].'</a>',
        'backToHome' => '<a href="index.php?'.$sids.'">'.$PMF_LANG['msgHome'].'</a>',
        'showSitemap' => '<a href="index.php?'.$sids.'action=sitemap&amp;lang='.$LANGCODE.'">'.$PMF_LANG['msgSitemap'].'</a>',
        'opensearch' => $faqSystem->getSystemUri($faqConfig).'opensearch.php',
        'msgUserRemoval' => '<a href="index.php?'.$sids.'action=request-removal">'.$PMF_LANG['msgUserRemoval'].'</a>',);
}

$tplNavigation['faqHome'] = $faqConfig->getDefaultUrl();
$tplNavigation['activeSearch'] = ('search' == $action) ? 'active' : '';
$tplNavigation['activeAllCategories'] = ('show' == $action) ? 'active' : '';
$tplNavigation['activeAddContent'] = ('add' == $action) ? 'active' : '';
$tplNavigation['activeAddQuestion'] = ('ask' == $action) ? 'active' : '';
$tplNavigation['activeOpenQuestions'] = ('open' == $action) ? 'active' : '';
$tplNavigation['activeLogin'] = ('login' == $action) ? 'active' : '';

//
// Show login box or logged-in user information
//
if (isset($auth)) {
    if ($user->perm->checkRight($user->getUserId(), 'viewadminlink')) {
        $adminSection = sprintf(
            '<a class="dropdown-item" href="%s">%s</a>',
            $faqSystem->getSystemUri($faqConfig).'admin/index.php',
            $PMF_LANG['adminSection']
        );
    } else {
        $adminSection = 'xxx';
    }

    if ($faqConfig->get('ldap.ldapSupport')) {
        $userControlDropdown = '';
    } else {
        $userControlDropdown = '<a class="dropdown-item" href="?action=ucp">'.$PMF_LANG['headerUserControlPanel'].'</a>';
    }

    $tpl->parseBlock(
        'index',
        'userloggedIn',
        [
            'msgUserControl' => $adminSection,
            'msgLoginName' => $user->getUserData('display_name'), // @deprecated
            'msgUserControlDropDown' => $userControlDropdown,
            'msgUserRemoval' => '<a class="dropdown-item" href="?action=request-removal">'.$PMF_LANG['ad_menu_RequestRemove'].'</a>',
            'msgLogoutUser' => '<a class="dropdown-item" href="?action=logout">'.$PMF_LANG['ad_menu_logout'].'</a>',
            'activeUserControl' => ('ucp' == $action) ? 'active' : ''
        ]
    );
} else {
    if ($faqConfig->get('main.maintenanceMode')) {
        $msgLoginUser = '<a class="dropdown-item" href="./admin/">%s</a>';
    } else {
        $msgLoginUser = '<a class="dropdown-item" href="?action=login">%s</a>';
    }
    $tpl->parseBlock(
        'index',
        'notLoggedIn',
        array(
            'msgRegisterUser' => $faqConfig->get('security.enableRegistration') ? '<a class="dropdown-item" href="?action=register">'.$PMF_LANG['msgRegisterUser'].'</a>' : '',
            'msgLoginUser' => sprintf($msgLoginUser, $PMF_LANG['msgLoginUser']),
            'activeRegister' => ('register' == $action) ? 'active' : '',
            'activeLogin' => ('login' == $action) ? 'active' : '',
        )
    );
}

if ('artikel' == $action || 'show' == $action || is_numeric($solutionId)) {

    // We need some Links from social networks
    $faqServices = new Services($faqConfig);
    $faqServices->setCategoryId($cat);
    $faqServices->setFaqId($id);
    $faqServices->setLanguage($lang);
    $faqServices->setQuestion($faq->getRecordTitle($id));

    $faqHelper = new HelperFaq($faqConfig);
    $faqHelper->setSsl((isset($_SERVER['HTTPS']) && is_null($_SERVER['HTTPS']) ? false : true));

    $tpl->parseBlock(
        'index',
        'socialLinks',
        [
            'baseHref' => $faqSystem->getSystemUri($faqConfig),
            'writePDFTag' => $PMF_LANG['msgPDF'],
            'writePrintMsgTag' => $PMF_LANG['msgPrintArticle'],
            'writeSend2FriendMsgTag' => $PMF_LANG['msgSend2Friend'],
            'shareOnFacebook' => $faqHelper->renderFacebookShareLink($faqServices->getShareOnFacebookLink()),
            'shareOnTwitter' => $faqHelper->renderTwitterShareLink($faqServices->getShareOnTwitterLink()),
            'link_email' => $faqServices->getSuggestLink(),
            'link_pdf' => $faqServices->getPdfLink(),
            'facebookLikeButton' => $faqHelper->renderFacebookLikeButton($faqServices->getLink())
        ]
    );
}

if ($faqConfig->get('main.enableRssFeeds')) {
    $rssFeedTopTen = '<a href="feed/topten/rss.php" target="_blank"><i class="material-icons">rss_feed</i></a>';
    $rssFeedLatest = '<a href="feed/latest/rss.php" target="_blank"><i class="material-icons">rss_feed</i></a>';
} else {
    $rssFeedTopTen = '';
    $rssFeedLatest = '';
}

$tplHeaders = [
    'writeTopTenHeader' => $PMF_LANG['msgTopTen'],
    'rssFeedTopTen' => $rssFeedTopTen,
    'writeNewestHeader' => $PMF_LANG['msgLatestArticles'],
    'rssFeedLatest' => $rssFeedLatest,
    'writeTagCloudHeader' => $PMF_LANG['msg_tags'],
    'writeTags' => $oTag->printHTMLTagsCloud(),
    'msgAllCatArticles' => $PMF_LANG['msgAllCatArticles'],
    'allCatArticles' => $faq->showAllRecordsWoPaging($cat)
];

if (DEBUG) {
    $tpl->parseBlock(
        'index',
        'debugMode',
        array(
            'debugExceptions' => implode('<br>', $pmfExceptions),
            'debugQueries' => $faqConfig->getDb()->log(),
        )
    );
}

//
// Include requested PHP file
//
require $includePhp;

//
// Get main template, set main variables
//
$tpl->parse('index', array_merge($tplMainPage, $tplNavigation, $tplHeaders));
$tpl->merge('writeContent', 'index');

//
// Send headers and print template
//
$httpHeader = new HelperHttp();
$httpHeader->setConfiguration($faqConfig);
$httpHeader->setContentType('text/html');
$httpHeader->addHeader();
$httpHeader->startCompression();

echo $tpl->render();

$faqConfig->getDb()->close();
