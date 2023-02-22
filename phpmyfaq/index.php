<?php

/**
 * This is the main public frontend page of phpMyFAQ. It detects the browser's
 * language, gets and sets all cookie, post and get information and includes
 * the templates we need and set all internal variables to the template
 * variables. That's all.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2001-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2001-02-12
 */

use phpMyFAQ\Attachment\AttachmentFactory;
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
use phpMyFAQ\Seo;
use phpMyFAQ\Session;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Tags;
use phpMyFAQ\Template;
use phpMyFAQ\Template\TemplateHelper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserAuthentication;
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
// Send headers and print template
//
$http = new HelperHttp();
$http->setConfiguration($faqConfig);
$http->setContentType('text/html');
$http->addHeader();
$http->startCompression();

$showCaptcha = Filter::filterInput(INPUT_GET, 'gen', FILTER_UNSAFE_RAW);

//
// Get language (default: english)
//
$Language = new Language($faqConfig);
$faqLangCode = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
$faqConfig->setLanguage($Language);

if (!Language::isASupportedLanguage($faqLangCode) && is_null($showCaptcha)) {
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
// Load plurals support for selected language
//
$plr = new Plurals();

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
$rememberMe = Filter::filterInput(INPUT_POST, 'faqrememberme', FILTER_VALIDATE_BOOLEAN);

//
// Set username via SSO
//
if ($faqConfig->get('security.ssoSupport') && isset($_SERVER['REMOTE_USER'])) {
    $faqusername = trim(Strings::htmlentities($_SERVER['REMOTE_USER']));
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
    $userAuth = new UserAuthentication($faqConfig, $user);
    $userAuth->setRememberMe($rememberMe ?? false);
    try {
        [ $user, $auth ] = $userAuth->authenticate($faqusername, $faqpassword);
    } catch (Exception $e) {
        $faqConfig->getLogger()->error('Failed login: ' . $e->getMessage());
        $action = 'login';
        $error = $e->getMessage();
    }
} else {
    // Try to authenticate with cookie information
    [ $user, $auth ] = CurrentUser::getCurrentUser($faqConfig);
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
[ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

//
// Found a session ID in _GET or _COOKIE?
//
$sidGet = Filter::filterInput(INPUT_GET, Session::PMF_GET_KEY_NAME_SESSIONID, FILTER_VALIDATE_INT);
$sidCookie = Filter::filterInput(INPUT_COOKIE, Session::PMF_COOKIE_NAME_SESSIONID, FILTER_VALIDATE_INT);
$faqSession = new Session($faqConfig);
$faqSession->setCurrentUser($user);

// Note: do not track internal calls
$internal = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $internal = (str_starts_with($_SERVER['HTTP_USER_AGENT'], 'phpMyFAQ%2F'));
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
        $_SERVER['REQUEST_TIME'] + 3600
    )
) {
    $sids = sprintf('lang=%s&amp;', $faqLangCode);
}

//
// Found an article language?
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
$faq
    ->setUser($currentUser)
    ->setGroups($currentGroups);

//
// Create a new Category object
//
$category = new Category($faqConfig, $currentGroups, true);
$category
    ->setUser($currentUser)
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
$currentPageUrl = Strings::htmlentities($faqLink->getCurrentUrl());

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
        Strings::htmlentities($faqConfig->getDefaultUrl()),
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
    $title = ' - ' . System::getPoweredByString();
    $keywords = '';
    $metaDescription = str_replace('"', '', $faqConfig->get('main.metaDescription'));
}

//
// found a solution ID?
//
$solutionId = Filter::filterInput(INPUT_GET, 'solution_id', FILTER_VALIDATE_INT);
if (!is_null($solutionId)) {
    $title = ' - ' . System::getPoweredByString();
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
            Strings::htmlentities($faqConfig->getDefaultUrl()),
            $sids,
            $faqData['category_id'],
            $id,
            $lang
        );
        $faqLink = new Link($url, $faqConfig);
        $faqLink->itemTitle = Strings::htmlentities($faqData['question']);
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
        $indexSet = match ($action) {
            'register', 'thankyou' => 'new-user.page.html',
            'password' => 'password.page.html',
            default => 'login.page.html',
        };
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

$categoryRelation = new CategoryRelation($faqConfig, $category);

$categoryHelper = new HelperCategory();
$categoryHelper->setCategory($category);
$categoryHelper->setConfiguration($faqConfig);
$categoryHelper->setCategoryRelation($categoryRelation);

$keywordsArray = array_merge(explode(',', $keywords), explode(',', $faqConfig->get('main.metaKeywords')));
$keywordsArray = array_filter($keywordsArray, 'strlen');
shuffle($keywordsArray);
$keywords = implode(',', $keywordsArray);

if (!is_null($error)) {
    $loginMessage = '<p class="alert alert-danger">' . $error . '</p>';
} else {
    $loginMessage = '';
}

$faqSeo = new Seo($faqConfig);

if ($faqConfig->get('security.enableRegistration')) {
    $template->parseBlock(
        'index',
        'enableRegistration',
        [
            'registerUser' => Translation::get('msgRegistration'),
        ]
    );
}

if ($faqConfig->isSignInWithMicrosoftActive()) {
    $template->parseBlock(
        'index',
        'useSignInWithMicrosoft',
        [
            'msgSignInWithMicrosoft' => Translation::get('msgSignInWithMicrosoft'),
        ]
    );
}


$tplMainPage = [
    'msgLoginUser' => $user->isLoggedIn() ? $user->getUserData('display_name') : Translation::get('msgLoginUser'),
    'title' => Strings::htmlspecialchars($faqConfig->getTitle() . $title),
    'baseHref' => Strings::htmlentities($faqSystem->getSystemUri($faqConfig)),
    'version' => $faqConfig->getVersion(),
    'header' => Strings::htmlentities(str_replace('"', '', $faqConfig->getTitle())),
    'metaTitle' => Strings::htmlentities(str_replace('"', '', $faqConfig->getTitle() . $title)),
    'metaDescription' => Strings::htmlentities($metaDescription ?? ''),
    'metaKeywords' => Strings::htmlentities($keywords),
    'metaPublisher' => Strings::htmlentities($faqConfig->get('main.metaPublisher')),
    'metaLanguage' => Translation::get('metaLanguage'),
    'metaRobots' => $faqSeo->getMetaRobots($action),
    'phpmyfaqVersion' => $faqConfig->getVersion(),
    'stylesheet' => Translation::get('dir') == 'rtl' ? 'style.rtl' : 'style',
    'currentPageUrl' => $currentPageUrl,
    'action' => $action,
    'dir' => Translation::get('dir'),
    'formActionUrl' => '?' . $sids . 'action=search',
    'searchBox' => Translation::get('msgSearch'),
    'searchTerm' =>Strings::htmlentities($searchTerm, ENT_QUOTES),
    'categoryId' => ($cat === 0) ? '%' : (int)$cat,
    'headerCategories' => Translation::get('msgFullCategories'),
    'msgCategory' => Translation::get('msgCategory'),
    'msgExportAllFaqs' => Translation::get('msgExportAllFaqs'),
    'languageBox' => Translation::get('msgLanguageSubmit'),
    'renderUri' => $renderUri,
    'switchLanguages' => LanguageHelper::renderSelectLanguage($faqLangCode, true),
    'copyright' => System::getPoweredByString(),
    'registerUser' => $faqConfig->get('security.enableRegistration') ? '<a href="?action=register">' .
        Translation::get('msgRegistration') . '</a>' : '',
    'sendPassword' => '<a href="?action=password">' . Translation::get('lostPassword') . '</a>',
    'msgFullName' => Translation::get('ad_user_loggedin') . $user->getLogin(),
    'msgLoginName' => Strings::htmlentities($user->getUserData('display_name')),
    'loginHeader' => Translation::get('msgLoginUser'),
    'loginMessage' => $loginMessage,
    'writeLoginPath' => Strings::htmlentities($faqSystem->getSystemUri($faqConfig)) . '?' .
        Filter::getFilteredQueryString(),
    'faqloginaction' => $action,
    'login' => Translation::get('ad_auth_ok'),
    'username' => Translation::get('ad_auth_user'),
    'password' => Translation::get('ad_auth_passwd'),
    'rememberMe' => Translation::get('rememberMe'),
    'headerChangePassword' => Translation::get('ad_passwd_cop'),
    'msgUsername' => Translation::get('ad_auth_user'),
    'msgEmail' => Translation::get('ad_entry_email'),
    'msgSubmit' => Translation::get('msgNewContentSubmit'),
    'loginPageMessage' => Translation::get('loginPageMessage'),
    'msgAdvancedSearch' => Translation::get('msgAdvancedSearch'),
    'writeTagCloudHeader' => Translation::get('msg_tags'),
    'writeTags' => $oTag->renderTagCloud(),
    'currentYear' => date("Y", time())
];

if ('main' == $action || 'show' == $action) {
    $template->parseBlock(
        'index',
        'globalSearchBox',
        [
            'formActionUrl' => '?' . $sids . 'action=search',
            'searchBox' => Translation::get('msgSearch'),
            'categoryId' => ($cat === 0) ? '%' : (int)$cat,
            'msgSearch' => sprintf(
                '<a class="help" href="?action=search">%s</a>',
                Translation::get('msgAdvancedSearch')
            ),
        ]
    );
}

if ($faqConfig->get('main.enableRewriteRules')) {
    $tplNavigation = [
        'backToHome' => '<a class="nav-link" href="./index.html">' . Translation::get('msgHome') . '</a>',
        'allCategories' => '<a class="nav-link px-2 text-white" href="./show-categories.html">' .
            Translation::get('msgShowAllCategories') . '</a>',
        'msgAddContent' => '<a class="nav-link px-2 text-white" href="./addcontent.html">' .
            Translation::get('msgAddContent') . '</a>',
        'msgQuestion' => $faqConfig->get('main.enableAskQuestions')
            ?
            '<a class="nav-link px-2 text-white" href="./ask.html">' . Translation::get('msgQuestion') . '</a>'
            :
            '',
        'msgOpenQuestions' => $faqConfig->get('main.enableAskQuestions')
            ?
            '<a class="nav-link px-2 text-white" href="./open-questions.html">' .
            Translation::get('msgOpenQuestions') . '</a>'
            :
            '',
        'msgSearch' => '<a class="nav-link" href="./search.html">' . Translation::get('msgAdvancedSearch') . '</a>',
        'msgContact' => '<a class="nav-link px-1 " href="./contact.html">' . Translation::get('msgContact') . '</a>',
        'msgGlossary' => '<a class="nav-link px-1 " href="./glossary.html">' .
            Translation::get('ad_menu_glossary') . '</a>',
        'privacyLink' => sprintf(
            '<a class="nav-link px-1 " target="_blank" href="%s">%s</a>',
            $faqConfig->get('main.privacyURL'),
            Translation::get('msgPrivacyNote')
        ),
        'faqOverview' => '<a class="nav-link px-1 " href="./overview.html">' . Translation::get('faqOverview') . '</a>',
        'showSitemap' => '<a class="nav-link px-1 " href="./sitemap/A/' . $faqLangCode . '.html">' .
            Translation::get('msgSitemap') . '</a>',
        'breadcrumbHome' => '<a href="./index.html">' . Translation::get('msgHome') . '</a>',
    ];
} else {
    $tplNavigation = [
        'backToHome' => '<a href="index.php?' . $sids . '">' . Translation::get('msgHome') . '</a>',
        'allCategories' => '<a class="nav-link" href="index.php?' . $sids . 'action=show">' .
            Translation::get('msgShowAllCategories') . '</a>',
        'msgAddContent' => '<a class="nav-link" href="index.php?' . $sids . 'action=add&cat=' . $cat . '">' .
            Translation::get('msgAddContent') . '</a>',
        'msgQuestion' => $faqConfig->get('main.enableAskQuestions')
            ?
            '<a class="nav-link" href="index.php?' . $sids . 'action=ask&category_id=' . $cat . '">' .
            Translation::get('msgQuestion') . '</a>'
            :
            '',
        'msgOpenQuestions' => $faqConfig->get('main.enableAskQuestions')
            ?
            '<a class="nav-link" href="index.php?' . $sids . 'action=open-questions">' .
            Translation::get('msgOpenQuestions') . '</a>'
            :
            '',
        'msgSearch' => '<a class="nav-link" href="index.php?' . $sids . 'action=search">' .
            Translation::get('msgAdvancedSearch') . '</a>',
        'msgContact' => '<a href="index.php?' . $sids . 'action=contact">' . Translation::get('msgContact') . '</a>',
        'msgGlossary' => '<a href="index.php?' . $sids . 'action=glossary">' .
            Translation::get('ad_menu_glossary') . '</a>',
        'privacyLink' => sprintf(
            '<a target="_blank" href="%s">%s</a>',
            $faqConfig->get('main.privacyURL'),
            Translation::get('msgPrivacyNote')
        ),
        'faqOverview' => '<a href="index.php?' . $sids . 'action=overview">' . Translation::get('faqOverview') . '</a>',
        'showSitemap' => '<a href="index.php?' . $sids . 'action=sitemap&amp;lang=' . $faqLangCode . '">' .
            Translation::get('msgSitemap') . '</a>',
        'breadcrumbHome' => '<a href="index.php?' . $sids . '">' . Translation::get('msgHome') . '</a>',
    ];
}

$tplNavigation['faqHome'] = Strings::htmlentities($faqConfig->getDefaultUrl());
$tplNavigation['activeSearch'] = ('search' == $action) ? 'active' : '';
$tplNavigation['activeAllCategories'] = ('show' == $action) ? 'active' : '';
$tplNavigation['activeAddContent'] = ('add' == $action) ? 'active' : '';
$tplNavigation['activeAddQuestion'] = ('ask' == $action) ? 'active' : '';
$tplNavigation['activeOpenQuestions'] = ('open-questions' == $action) ? 'active' : '';
$tplNavigation['activeLogin'] = ('login' == $action) ? 'active' : '';

//
// Show login box or logged-in user information
//
if ($user->getUserId() > 0) {
    if ($user->perm->hasPermission($user->getUserId(), 'viewadminlink') || $user->isSuperAdmin()) {
        $adminSection = sprintf(
            '<a class="dropdown-item" href="./admin/index.php">%s</a>',
            Translation::get('adminSection')
        );
    } else {
        $adminSection = '';
    }

    if ($faqConfig->get('ldap.ldapSupport')) {
        $userControlDropdown = '';
    } else {
        $userControlDropdown = sprintf(
            '<a class="dropdown-item" href="?action=ucp">%s</a>',
            Translation::get('headerUserControlPanel')
        );
    }

    $template->parseBlock(
        'index',
        'userloggedIn',
        [
            'msgUserControl' => $adminSection,
            'msgLoginName' => $user->getUserData('display_name'), // @deprecated
            'activeUserControl' => ('ucp' == $action) ? 'active' : '',
            'msgUserControlDropDown' => '<a class="dropdown-item" href="?action=ucp">' .
                Translation::get('headerUserControlPanel') . '</a>',
            'msgUserRemoval' => '<a class="dropdown-item" href="?action=request-removal">' .
                Translation::get('ad_menu_RequestRemove') . '</a>',
            'msgLogoutUser' => sprintf(
                '<a class="dropdown-item" href="?action=logout&csrf=%s">%s</a>',
                $user->getCsrfTokenFromSession(),
                Translation::get('ad_menu_logout'),
            )
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
                '<a class="dropdown-item" href="?action=register">' .
                Translation::get('msgRegisterUser') . '</a>'
                :
                '',
            'msgLoginUser' => sprintf($msgLoginUser, Translation::get('msgLoginUser')),
            'activeRegister' => ('register' == $action) ? 'active' : '',
            'activeLogin' => ('login' == $action) ? 'active' : '',
        ]
    );
}

$template->parse(
    'sidebar',
    [
        'msgAllCatArticles' => Translation::get('msgAllCatArticles'),
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
        Strings::htmlspecialchars($faqConfig->getDefaultUrl()),
        $category->getCategoryIdFromFaq($id),
        $id,
        $lang
    );
    $link = new Link($url, $faqConfig);
    $http->setStatus(301);
    $http->redirect($link->toString());
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
