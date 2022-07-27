<?php

/**
 * This is the main public frontend page of phpMyFAQ. It detects the browser's
 * language, gets and sets all cookie, post and get information and includes
 * the templates we need and set all internal variables to the template
 * variables. That's all.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @author Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2001-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2001-02-12
 */

use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Auth\AuthLdap as AuthLdap;
use phpMyFAQ\Auth\AuthSso as AuthSso;
use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryRelation;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper as HelperCategory;
use phpMyFAQ\Helper\HttpHelper as HelperHttp;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Seo;
use phpMyFAQ\Session;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Tags;
use phpMyFAQ\Template;
use phpMyFAQ\Template\TemplateHelper;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;

//
// Define the named constant used as a check by any included PHP file
//
const IS_VALID_PHPMYFAQ = null;

//
// Bootstrapping
//
require __DIR__ . '/src/Bootstrap.php';

//
// HTTP Helper
//
$http = new HelperHttp();

//
// Get language (default: english)
//
$Language = new Language($faqConfig);
$faqLangCode = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
// Preload English strings
require_once 'lang/language_en.php';
$faqConfig->setLanguage($Language);

$showCaptcha = Filter::filterInput(INPUT_GET, 'gen', FILTER_UNSAFE_RAW);
if (isset($faqLangCode) && Language::isASupportedLanguage($faqLangCode) && is_null($showCaptcha)) {
    // Overwrite English strings with the ones we have in the current language,
    // but don't include UTF-8 encoded files, these will break the captcha images
    if (!file_exists('lang/language_' . $faqLangCode . '.php')) {
        $faqLangCode = 'en';
    }
    require_once 'lang/language_' . $faqLangCode . '.php';
} else {
    $faqLangCode = 'en';
}

//Load plurals support for selected language
$plr = new Plurals($PMF_LANG);

//
// Initializing static string wrapper
//
Strings::init($faqLangCode);

/*
 * Initialize attachment factory
 */
AttachmentFactory::init(
    $faqConfig->get('records.attachmentsStorageType'),
    $faqConfig->get('records.defaultAttachmentEncKey'),
    $faqConfig->get('records.enableAttachmentEncryption')
);

//
// Get user action
//
$action = Filter::filterInput(INPUT_GET, 'action', FILTER_UNSAFE_RAW);

//
// Authenticate current user
//
$auth = $error = null;
$loginVisibility = 'hidden';

$faqusername = Filter::filterInput(INPUT_POST, 'faqusername', FILTER_UNSAFE_RAW);
$faqpassword = Filter::filterInput(INPUT_POST, 'faqpassword', FILTER_UNSAFE_RAW, FILTER_FLAG_NO_ENCODE_QUOTES);
$faqaction = Filter::filterInput(INPUT_POST, 'faqloginaction', FILTER_UNSAFE_RAW);
$rememberMe = Filter::filterInput(INPUT_POST, 'faqrememberme', FILTER_UNSAFE_RAW);

// Set username via SSO
if ($faqConfig->get('security.ssoSupport') && isset($_SERVER['REMOTE_USER'])) {
    $faqusername = trim($_SERVER['REMOTE_USER']);
    $faqpassword = '';
}

//
// Get CSRF Token
//
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_UNSAFE_RAW);
if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
    $csrfChecked = false;
} else {
    $csrfChecked = true;
}

// Login via local DB or LDAP or SSO
if (!is_null($faqusername) && !is_null($faqpassword)) {
    $user = new CurrentUser($faqConfig);
    if (!is_null($rememberMe) && 'rememberMe' === $rememberMe) {
        $user->enableRememberMe();
    }
    if ($faqConfig->isLdapActive() && function_exists('ldap_connect')) {
        try {
            $authLdap = new AuthLdap($faqConfig);
            $user->addAuth($authLdap, 'ldap');
        } catch (Exception $e) {
            $error = $e->getMessage() . '<br>';
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
            $error = $error . $PMF_LANG['ad_auth_fail'] . ' (' . $faqusername . ')';
            $loginVisibility = '';
            $action = 'password' === $action ? 'password' : 'login';
        }
    } else {
        // error
        $error = $error . $PMF_LANG['ad_auth_fail'];
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
if ($csrfChecked && 'logout' === $action && isset($auth)) {
    $user->deleteFromSession(true);
    $auth = null;
    $action = 'main';
    $ssoLogout = $faqConfig->get('security.ssoLogoutRedirect');
    if ($faqConfig->get('security.ssoSupport') && !empty($ssoLogout)) {
        header('Location: ' . $ssoLogout);
    } else {
        header('Location: ' . $faqConfig->getDefaultUrl());
    }
}

//
// Get current user and group id - default: -1
//
if (!is_null($user) && $user instanceof CurrentUser) {
    $currentUser = $user->getUserId();
    if ($user->perm instanceof MediumPermission) {
        $currentGroups = $user->perm->getUserGroups($currentUser);
    } else {
        $currentGroups = [-1];
    }
    if (0 == count($currentGroups)) {
        $currentGroups = [-1];
    }
} else {
    $currentUser = -1;
    $currentGroups = [-1];
}

//
// Use mbstring extension if available and when possible
//
$validMbStrings = ['ja', 'en', 'uni'];
$mbLanguage = ($PMF_LANG['metaLanguage'] != 'ja') ? 'uni' : $PMF_LANG['metaLanguage'];
if (function_exists('mb_language') && in_array($mbLanguage, $validMbStrings)) {
    mb_language($mbLanguage);
    mb_internal_encoding('utf-8');
}

//
// Found a session ID in _GET or _COOKIE?
//
$sidGet = Filter::filterInput(INPUT_GET, PMF_GET_KEY_NAME_SESSIONID, FILTER_VALIDATE_INT);
$sidCookie = Filter::filterInput(INPUT_COOKIE, Session::PMF_COOKIE_NAME_SESSIONID, FILTER_VALIDATE_INT);
$faqSession = new Session($faqConfig);
$faqSession->setCurrentUser($user);

// Note: do not track internal calls
$internal = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $internal = (strpos($_SERVER['HTTP_USER_AGENT'], 'phpMyFAQ%2F') === 0);
}
if (!$internal) {
    if (is_null($sidGet) && is_null($sidCookie)) {
        // Create a per-site unique SID
        try {
            $faqSession->userTracking('new_session', 0);
        } catch (Exception $e) {
            $pmfExceptions[] = $e->getMessage();
        }
    } elseif (!is_null($sidCookie)) {
        try {
            $faqSession->checkSessionId($sidCookie, $_SERVER['REMOTE_ADDR']);
        } catch (Exception $e) {
            $pmfExceptions[] = $e->getMessage();
        }
    } else {
        try {
            $faqSession->checkSessionId($sidGet, $_SERVER['REMOTE_ADDR']);
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
    if ($faqSession->getCurrentSessionId() > 0) {
        $faqSession->setCookie(Session::PMF_COOKIE_NAME_SESSIONID, $faqSession->getCurrentSessionId());
        if (is_null($sidCookie)) {
            $sids = sprintf('sid=%d&amp;lang=%s&amp;', $faqSession->getCurrentSessionId(), $faqLangCode);
        }
    } elseif (is_null($sidGet) || is_null($sidCookie)) {
        if (is_null($sidCookie)) {
            if (!is_null($sidGet)) {
                $sids = sprintf('sid=%d&amp;lang=%s&amp;', $sidGet, $faqLangCode);
            }
        }
    }
} elseif (
    !$faqSession->setCookie(
        Session::PMF_COOKIE_NAME_SESSIONID,
        $faqSession->getCurrentSessionId(),
        $_SERVER['REQUEST_TIME'] + PMF_LANGUAGE_EXPIRED_TIME
    )
) {
    $sids = sprintf('lang=%s&amp;', $faqLangCode);
}

//
// Found a article language?
//
$lang = Filter::filterInput(INPUT_POST, 'artlang', FILTER_UNSAFE_RAW);
if (is_null($lang) && !Language::isASupportedLanguage($lang)) {
    $lang = Filter::filterInput(INPUT_GET, 'artlang', FILTER_UNSAFE_RAW);
    if (is_null($lang) && !Language::isASupportedLanguage($lang)) {
        $lang = $faqLangCode;
    }
}

//
// Sanitize language string
//
if (!Language::isASupportedLanguage($lang)) {
    $lang = $faqConfig->getDefaultLanguage();
}

//
// Found a search string?
//
$searchTerm = Filter::filterInput(INPUT_GET, 'search', FILTER_UNSAFE_RAW, '');

//
// Create a new FAQ object
//
$faq = new Faq($faqConfig);
$faq->setUser($currentUser)
    ->setGroups($currentGroups);

//
// Create a new Category object
//
$category = new Category($faqConfig, $currentGroups, true);
$category->setUser($currentUser)
    ->setGroups($currentGroups);

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
    $title = ' - ' . $faq->faqRecord['title'];
    $keywords = ',' . $faq->faqRecord['keywords'];
    $metaDescription = str_replace('"', '', strip_tags($faq->getRecordPreview($id)));
    $url = sprintf(
        '%sindex.php?%saction=faq&cat=%d&id=%d&artlang=%s',
        $faqConfig->getDefaultUrl(),
        $sids,
        $category->getCategoryIdFromFaq($id),
        $id,
        $lang
    );
    $faqLink = new Link($url, $faqConfig);
    $faqLink->itemTitle = $faq->faqRecord['title'];
    $currentPageUrl = $faqLink->toString(true);
} else {
    $id = '';
    $title = ' - powered by phpMyFAQ ' . $faqConfig->getVersion();
    $keywords = '';
    $metaDescription = str_replace('"', '', $faqConfig->get('main.metaDescription'));
}

//
// found a solution ID?
//
$solutionId = Filter::filterInput(INPUT_GET, 'solution_id', FILTER_VALIDATE_INT);
if (!is_null($solutionId)) {
    $title = ' -  powered by phpMyFAQ ' . $faqConfig->getVersion();
    $keywords = '';
    $faqData = $faq->getIdFromSolutionId($solutionId);
    if (is_array($faqData)) {
        $id = $faqData['id'];
        $lang = $faqData['lang'];
        $title = ' - ' . $faq->getRecordTitle($id);
        $keywords = ',' . $faq->getRecordKeywords($id);
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
        $currentPageUrl = $faqLink->toString(true);
    }
}

//
// Handle the Tagging ID
//
$tag_id = Filter::filterInput(INPUT_GET, 'tagging_id', FILTER_VALIDATE_INT);
if (!is_null($tag_id)) {
    $title = ' - ' . $oTag->getTagNameById($tag_id);
    $keywords = '';
}

//
// Handle the SiteMap
//
$letter = Filter::filterInput(INPUT_GET, 'letter', FILTER_UNSAFE_RAW);
if (!is_null($letter) && (1 == Strings::strlen($letter))) {
    $title = ' - ' . $letter . '...';
    $keywords = $letter;
}

//
// Found a category ID?
//
$cat = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
$categoryFromId = -1;
if (is_numeric($id) && $id > 0) {
    $categoryFromId = $category->getCategoryIdFromFaq($id);
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
    $title = ' - ' . $category->categoryName[$cat]['name'];
    $metaDescription = $category->categoryName[$cat]['description'];
}

//
// Found an action request?
//
if (!isset(Link::$allowedActionParameters[$action])) {
    $action = 'main';
}

//
// Select the template for the requested page
//
if ($action !== 'main') {
    $includeTemplate = $action . '.html';
    $includePhp = $action . '.php';
    $renderUri = '?sid=' . $faqSession->getCurrentSessionId();
} else {
    if (isset($solutionId) && is_numeric($solutionId)) {
        // show the record with the solution ID
        $includeTemplate = 'faq.html';
        $includePhp = 'faq.php';
    } else {
        $includeTemplate = 'startpage.html';
        $includePhp = 'startpage.php';
    }
    $renderUri = '?sid=' . $faqSession->getCurrentSessionId();
}

//
// Set sidebar column
//
if (($action === 'faq') || ($action === 'show') || ($action === 'main')) {
    $sidebarTemplate = 'sidebar-tagcloud.html';
} else {
    $sidebarTemplate = 'sidebar-empty.html';
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
                $indexSet = 'new-user.page.html';
                break;
            case 'password':
                $indexSet = 'password.page.html';
                break;
            default:
                $indexSet = 'login.page.html';
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
    $indexSet = 'maintenance.page.html';
}

//
// Load template files and set template variables
//
$template = new Template(
    [
        'index' => $indexSet,
        'sidebar' => $sidebarTemplate,
        'mainPageContent' => $includeTemplate,
    ],
    new TemplateHelper($faqConfig),
    $faqConfig->get('main.templateSet')
);

$categoryRelation = new CategoryRelation($faqConfig);

$categoryHelper = new HelperCategory();
$categoryHelper->setCategory($category);
$categoryHelper->setConfiguration($faqConfig);
$categoryHelper->setCategoryRelation($categoryRelation);

$keywordsArray = array_merge(explode(',', $keywords), explode(',', $faqConfig->get('main.metaKeywords')));
$keywordsArray = array_filter($keywordsArray, 'strlen');
shuffle($keywordsArray);
$keywords = implode(',', $keywordsArray);

if (!is_null($error)) {
    $loginMessage = '<p class="error">' . $error . '</p>';
} else {
    $loginMessage = '';
}

$faqSeo = new Seo($faqConfig);

$tplMainPage = [
    'msgLoginUser' => $user->isLoggedIn() ? $user->getUserData('display_name') : $PMF_LANG['msgLoginUser'],
    'title' => Strings::htmlspecialchars($faqConfig->getTitle() . $title),
    'baseHref' => $faqSystem->getSystemUri($faqConfig),
    'version' => $faqConfig->getVersion(),
    'header' => Strings::htmlspecialchars(str_replace('"', '', $faqConfig->getTitle())),
    'metaTitle' => Strings::htmlspecialchars(str_replace('"', '', $faqConfig->getTitle() . $title)),
    'metaDescription' => Strings::htmlspecialchars($metaDescription ?? ''),
    'metaKeywords' => Strings::htmlspecialchars($keywords),
    'metaPublisher' => $faqConfig->get('main.metaPublisher'),
    'metaLanguage' => $PMF_LANG['metaLanguage'],
    'metaRobots' => $faqSeo->getMetaRobots($action),
    'phpmyfaqversion' => $faqConfig->getVersion(),
    'stylesheet' => $PMF_LANG['dir'] == 'rtl' ? 'style.rtl' : 'style',
    'currentPageUrl' => $currentPageUrl,
    'action' => $action,
    'dir' => $PMF_LANG['dir'],
    'writeSendAdress' => '?' . $sids . 'action=search',
    'searchBox' => $PMF_LANG['msgSearch'],
    'searchTerm' => Strings::htmlspecialchars($searchTerm),
    'categoryId' => ($cat === 0) ? '%' : (int)$cat,
    'headerCategories' => $PMF_LANG['msgFullCategories'],
    'msgCategory' => $PMF_LANG['msgCategory'],
    'showCategories' => $categoryHelper->renderNavigation($cat),
    'topCategories' => $categoryHelper->renderMainCategories(),
    'msgExportAllFaqs' => $PMF_LANG['msgExportAllFaqs'],
    'languageBox' => $PMF_LANG['msgLanguageSubmit'],
    'renderUri' => $renderUri,
    'switchLanguages' => LanguageHelper::renderSelectLanguage($faqLangCode, true),
    'copyright' => 'powered with ❤️ and ☕️ by <a href="https://www.phpmyfaq.de" target="_blank">phpMyFAQ</a> ' .
        $faqConfig->getVersion(),
    'registerUser' => $faqConfig->get('security.enableRegistration') ? '<a href="?action=register">' .
        $PMF_LANG['msgRegistration'] . '</a>' : '',
    'sendPassword' => '<a href="?action=password">' . $PMF_LANG['lostPassword'] . '</a>',
    'msgFullName' => $PMF_LANG['ad_user_loggedin'] . $user->getLogin(),
    'msgLoginName' => $user->getUserData('display_name'),
    'loginHeader' => $PMF_LANG['msgLoginUser'],
    'loginMessage' => $loginMessage,
    'writeLoginPath' => $faqSystem->getSystemUri($faqConfig) . '?' . Filter::getFilteredQueryString(),
    'faqloginaction' => $action,
    'login' => $PMF_LANG['ad_auth_ok'],
    'username' => $PMF_LANG['ad_auth_user'],
    'password' => $PMF_LANG['ad_auth_passwd'],
    'rememberMe' => $PMF_LANG['rememberMe'],
    'headerChangePassword' => $PMF_LANG['ad_passwd_cop'],
    'msgUsername' => $PMF_LANG['ad_auth_user'],
    'msgEmail' => $PMF_LANG['ad_entry_email'],
    'msgSubmit' => $PMF_LANG['msgNewContentSubmit'],
    'loginPageMessage' => $PMF_LANG['loginPageMessage'],
    'msgAdvancedSearch' => $PMF_LANG['msgAdvancedSearch']
];

$template->parseBlock(
    'index',
    'categoryListSection',
    [
        'showCategories' => $categoryHelper->renderNavigation($cat),
        'categoryDropDown' => $categoryHelper->renderCategoryDropDown(),
    ]
);

if ('main' == $action || 'show' == $action) {
    $template->parseBlock(
        'index',
        'globalSearchBox',
        [
            'writeSendAdress' => '?' . $sids . 'action=search',
            'searchBox' => $PMF_LANG['msgSearch'],
            'categoryId' => ($cat === 0) ? '%' : (int)$cat,
            'msgSearch' => sprintf(
                '<a class="help" href="./index.php?action=search">%s</a>',
                $PMF_LANG['msgAdvancedSearch']
            ),
        ]
    );
}

if ($faqConfig->get('main.enableRewriteRules')) {
    $tplNavigation = [
        'msgSearch' => '<a class="nav-link" href="./search.html">' . $PMF_LANG['msgAdvancedSearch'] . '</a>',
        'msgAddContent' => '<a class="nav-link" href="' . $faqSystem->getSystemUri($faqConfig) . 'addcontent.html">' .
            $PMF_LANG['msgAddContent'] . '</a>',
        'msgQuestion' => '<a class="nav-link" href="./ask.html">' . $PMF_LANG['msgQuestion'] . '</a>',
        'msgOpenQuestions' => '<a class="nav-link" href="./open-questions.html">' . $PMF_LANG['msgOpenQuestions'] .
            '</a>',
        'msgContact' => '<a href="./contact.html">' . $PMF_LANG['msgContact'] . '</a>',
        'msgGlossary' => '<a href="./glossary.html">' . $PMF_LANG['ad_menu_glossary'] . '</a>',
        'privacyLink' => sprintf(
            '<a target="_blank" href="%s">%s</a>',
            $faqConfig->get('main.privacyURL'),
            $PMF_LANG['msgPrivacyNote']
        ),
        'backToHome' => '<a href="./index.html">' . $PMF_LANG['msgHome'] . '</a>',
        'allCategories' => '<a class="nav-link" href="./showcat.html">' . $PMF_LANG['msgShowAllCategories'] . '</a>',
        'faqOverview' => '<a href="./overview.html">' . $PMF_LANG['faqOverview'] . '</a>',
        'showSitemap' => '<a href="./sitemap/A/' . $faqLangCode . '.html">' . $PMF_LANG['msgSitemap'] . '</a>',
        'msgUserRemoval' => '<a href="./request-removal.html">' . $PMF_LANG['msgUserRemoval'] . '</a>'
    ];
} else {
    $tplNavigation = [
        'msgSearch' => '<a class="nav-link" href="index.php?' . $sids . 'action=search">' .
            $PMF_LANG['msgAdvancedSearch'] . '</a>',
        'msgAddContent' => '<a class="nav-link" href="index.php?' . $sids . 'action=add&cat=' . $cat . '">' .
            $PMF_LANG['msgAddContent'] . '</a>',
        'msgQuestion' => '<a class="nav-link" href="index.php?' . $sids . 'action=ask&category_id=' . $cat . '">' .
            $PMF_LANG['msgQuestion'] . '</a>',
        'msgOpenQuestions' => '<a class="nav-link" href="index.php?' . $sids . 'action=open-questions">' .
            $PMF_LANG['msgOpenQuestions'] . '</a>',
        'msgContact' => '<a href="index.php?' . $sids . 'action=contact">' . $PMF_LANG['msgContact'] . '</a>',
        'msgGlossary' => '<a href="index.php?' . $sids . 'action=glossary">' . $PMF_LANG['ad_menu_glossary'] . '</a>',
        'privacyLink' => sprintf(
            '<a target="_blank" href="%s">%s</a>',
            $faqConfig->get('main.privacyURL'),
            $PMF_LANG['msgPrivacyNote']
        ),
        'allCategories' => '<a class="nav-link" href="index.php?' . $sids . 'action=show">' .
            $PMF_LANG['msgShowAllCategories'] . '</a>',
        'faqOverview' => '<a href="index.php?' . $sids . 'action=overview">' . $PMF_LANG['faqOverview'] . '</a>',
        'backToHome' => '<a href="index.php?' . $sids . '">' . $PMF_LANG['msgHome'] . '</a>',
        'showSitemap' => '<a href="index.php?' . $sids . 'action=sitemap&amp;lang=' . $faqLangCode . '">' .
            $PMF_LANG['msgSitemap'] . '</a>',
        'msgUserRemoval' => '<a href="index.php?' . $sids . 'action=request-removal">' . $PMF_LANG['msgUserRemoval'] .
            '</a>',
    ];
}

$tplNavigation['faqHome'] = $faqConfig->getDefaultUrl();
$tplNavigation['activeSearch'] = ('search' == $action) ? 'active' : '';
$tplNavigation['activeAllCategories'] = ('show' == $action) ? 'active' : '';
$tplNavigation['activeAddContent'] = ('add' == $action) ? 'active' : '';
$tplNavigation['activeAddQuestion'] = ('ask' == $action) ? 'active' : '';
$tplNavigation['activeOpenQuestions'] = ('open-questions' == $action) ? 'active' : '';
$tplNavigation['activeLogin'] = ('login' == $action) ? 'active' : '';

//
// Show login box or logged-in user information
//
if (isset($auth)) {
    if ($user->perm->hasPermission($user->getUserId(), 'viewadminlink') || $user->isSuperAdmin()) {
        $adminSection = sprintf(
            '<a class="dropdown-item" href="./admin/index.php">%s</a>',
            $PMF_LANG['adminSection']
        );
    } else {
        $adminSection = '';
    }

    $template->parseBlock(
        'index',
        'userloggedIn',
        [
            'msgUserControl' => $adminSection,
            'msgLoginName' => $user->getUserData('display_name'), // @deprecated
            'msgUserControlDropDown' => '<a class="dropdown-item" href="?action=ucp">' .
                $PMF_LANG['headerUserControlPanel'] . '</a>',
            'msgUserRemoval' => '<a class="dropdown-item" href="?action=request-removal">' .
                $PMF_LANG['ad_menu_RequestRemove'] . '</a>',
            'msgLogoutUser' => sprintf(
                '<a class="dropdown-item" href="?action=logout&csrf=%s">%s</a>',
                $user->getCsrfTokenFromSession(),
                $PMF_LANG['ad_menu_logout'],
            ),
            'activeUserControl' => ('ucp' == $action) ? 'active' : ''
        ]
    );
} else {
    if ($faqConfig->get('main.maintenanceMode')) {
        $msgLoginUser = '<a class="dropdown-item" href="./admin/">%s</a>';
    } else {
        $msgLoginUser = '<a class="dropdown-item" href="?action=login">%s</a>';
    }
    $template->parseBlock(
        'index',
        'notLoggedIn',
        [
            'msgRegisterUser' => $faqConfig->get('security.enableRegistration')
                ?
                '<a class="dropdown-item" href="?action=register">' . $PMF_LANG['msgRegisterUser'] . '</a>'
                :
                '',
            'msgLoginUser' => sprintf($msgLoginUser, $PMF_LANG['msgLoginUser']),
            'activeRegister' => ('register' == $action) ? 'active' : '',
            'activeLogin' => ('login' == $action) ? 'active' : '',
        ]
    );
}

$template->parse(
    'sidebar',
    [
        'writeTagCloudHeader' => $PMF_LANG['msg_tags'],
        'writeTags' => $oTag->renderTagCloud(),
        'msgAllCatArticles' => $PMF_LANG['msgAllCatArticles'],
        'allCatArticles' => $faq->getRecordsWithoutPagingByCategoryId($cat)
    ]
);

if (DEBUG) {
    $template->parseBlock(
        'index',
        'debugMode',
        [
            'debugQueries' => $faqConfig->getDb()->log(),
        ]
    );
}

//
// Redirect old "action=artikel" URLs via 301 to new location
//
if ('artikel' === $action) {
    $url = sprintf(
        '%sindex.php?action=faq&cat=%d&id=%d&artlang=%s',
        $faqConfig->getDefaultUrl(),
        $category->getCategoryIdFromFaq($id),
        $id,
        $lang
    );
    $http->setStatus(301);
    $http->redirect($url);
    exit();
}

//
// Include requested PHP file
//
require $includePhp;

//
// Get main template, set main variables
//
$template->parse('index', array_merge($tplMainPage, $tplNavigation));
$template->merge('sidebar', 'index');
$template->merge('mainPageContent', 'index');

//
// Send headers and print template
//
$http->setConfiguration($faqConfig);
$http->setContentType('text/html');
$http->addHeader();
$http->startCompression();

//
// Check for 404 HTTP status code
//
if ($http->getStatusCode() === 404 || $action === '404') {
    $template = new Template(
        [
            'index' => '404.html',
            'mainPageContent' => ''
        ],
        new TemplateHelper($faqConfig),
        $faqConfig->get('main.templateSet')
    );
    $template->parse('index', array_merge($tplMainPage, $tplNavigation));
}

$template->render();

$faqConfig->getDb()->close();
