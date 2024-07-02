<?php

/**
 * This is the main public frontend page of phpMyFAQ. It detects the browser's
 * language, gets and sets all cookies, post and get information and includes
 * the templates we need and set all internal variables to the template
 * variables. That's all.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL wasn't distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2001-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2001-02-12
 */

use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Category;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\SeoType;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper as HelperCategory;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Link;
use phpMyFAQ\Seo;
use phpMyFAQ\Session;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Tags;
use phpMyFAQ\Template;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\TwoFactor;
use phpMyFAQ\User\UserAuthentication;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//
// Define the named constant used as a check by any included PHP file
//
const IS_VALID_PHPMYFAQ = null;

//
// Bootstrapping
//
require __DIR__ . '/src/Bootstrap.php';

//
// Create Request + Response
//
$request = Request::createFromGlobals();
$response = new Response();
$response->headers->set('Content-Type', 'text/html');

$faqConfig = Configuration::getConfigurationInstance();

//
// Get language (default: english)
//
$Language = new Language($faqConfig);
$faqLangCode = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
$faqConfig->setLanguage($Language);

if (!Language::isASupportedLanguage($faqLangCode)) {
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
} catch (Exception $exception) {
    echo '<strong>Error:</strong> ' . $exception->getMessage();
}

//
// Initializing static string wrapper
//
Strings::init($faqLangCode);

/*
 * Initialize attachment factory
 */
AttachmentFactory::init(
    $faqConfig->get('records.defaultAttachmentEncKey'),
    $faqConfig->get('records.enableAttachmentEncryption')
);

//
// Get user action
//
$action = Filter::filterVar($request->query->get('action'), FILTER_SANITIZE_SPECIAL_CHARS);

//
// Authenticate current user
//
$error = null;
$loginVisibility = 'hidden';

$faqusername = Filter::filterVar($request->request->get('faqusername'), FILTER_SANITIZE_SPECIAL_CHARS);
$faqpassword = Filter::filterVar(
    $request->request->get('faqpassword'),
    FILTER_SANITIZE_SPECIAL_CHARS,
    FILTER_FLAG_NO_ENCODE_QUOTES
);
$faqaction = Filter::filterVar($request->request->get('faqloginaction'), FILTER_SANITIZE_SPECIAL_CHARS);
$rememberMe = Filter::filterVar($request->request->get('faqrememberme'), FILTER_VALIDATE_BOOLEAN);
$token = Filter::filterVar($request->request->get('token'), FILTER_SANITIZE_SPECIAL_CHARS);
$userid = Filter::filterVar($request->request->get('userid'), FILTER_VALIDATE_INT);

//
// Set username via SSO
//
if ($faqConfig->get('security.ssoSupport') && $request->server->get('REMOTE_USER') !== null) {
    $faqusername = trim(Strings::htmlentities($request->server->get('REMOTE_USER')));
    $faqpassword = '';
}

//
// Get CSRF Token
//
$csrfToken = Filter::filterVar($request->query->get('csrf'), FILTER_SANITIZE_SPECIAL_CHARS);
if ($csrfToken !== '' && Token::getInstance()->verifyToken('logout', $csrfToken)) {
    $csrfChecked = true;
} else {
    $csrfChecked = false;
}

//
// Validating token from 2FA if given; else: returns error message
//
if ($token !== '' && !is_null($userid)) {
    if (strlen((string)$token) === 6 && is_numeric((string)$token)) {
        $user = new CurrentUser($faqConfig);
        $user->getUserById($userid);
        $tfa = new TwoFactor($faqConfig);
        $res = $tfa->validateToken($token, $userid);
        if (!$res) {
            $error = Translation::get('msgTwofactorErrorToken');
            $action = 'twofactor';
        } else {
            $user->twoFactorSuccess();
            $redirect = new RedirectResponse($faqConfig->getDefaultUrl());
            $redirect->send();
        }
    } else {
        $error = Translation::get('msgTwofactorErrorToken');
        $action = 'twofactor';
    }
}

if (!isset($user)) {
    $user = new CurrentUser($faqConfig);
}

// Login via local DB or LDAP or SSO
if ($faqusername !== '' && ($faqpassword !== '' || $faqConfig->get('security.ssoSupport'))) {
    $userAuth = new UserAuthentication($faqConfig, $user);
    $userAuth->setRememberMe($rememberMe ?? false);
    try {
        $user = $userAuth->authenticate($faqusername, $faqpassword);
        $userid = $user->getUserId();
    } catch (Exception $e) {
        $faqConfig->getLogger()->error('Failed login: ' . $e->getMessage());
        $action = 'login';
        $error = $e->getMessage();
    }
} else {
    // Try to authenticate with cookie information
    try {
        $user = CurrentUser::getCurrentUser($faqConfig);
    } catch (Exception $e) {
        $faqConfig->getLogger()->error('Failed to authenticate via cookie: ' . $e->getMessage());
        $error = $e->getMessage();
    }
}

if (isset($userAuth) && $userAuth instanceof UserAuthentication) {
    if ($userAuth->hasTwoFactorAuthentication()) {
        $action = 'twofactor';
    }
}

//
// Logout
//
if ($csrfChecked && 'logout' === $action && $user->isLoggedIn()) {
    $user->deleteFromSession(true);
    $action = 'main';
    $ssoLogout = $faqConfig->get('security.ssoLogoutRedirect');

    if ($faqConfig->get('security.ssoSupport') && !empty($ssoLogout)) {
        $redirect = new RedirectResponse($ssoLogout);
        $redirect->send();
    }

    if ($faqConfig->isSignInWithMicrosoftActive() && $user->getUserAuthSource() === 'azure') {
        $redirect = new RedirectResponse($faqConfig->getDefaultUrl() . 'services/azure/logout.php');
        $redirect->send();
    }

    $redirect = new RedirectResponse($faqConfig->getDefaultUrl());
    $redirect->send();
}

//
// Get current user and group id - default: -1
//
[$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($user);

//
// Found a session ID in _GET or _COOKIE?
//
$sidGet = Filter::filterVar($request->query->get(Session::PMF_GET_KEY_NAME_SESSIONID), FILTER_VALIDATE_INT);
$sidCookie = Filter::filterVar($request->cookies->get(Session::PMF_COOKIE_NAME_SESSIONID), FILTER_VALIDATE_INT);
$faqSession = new Session($faqConfig);
$faqSession->setCurrentUser($user);

// Note: do not track internal calls
$internal = false;

if ($request->headers->get('user-agent') !== null) {
    $internal = (str_starts_with($request->headers->get('user-agent'), 'phpMyFAQ%2F'));
}

if (!$internal) {
    if (is_null($sidGet) && is_null($sidCookie)) {
        // Create a per-site unique SID
        $faqSession->userTracking('new_session', 0);
    } elseif (!is_null($sidCookie)) {
        $faqSession->checkSessionId($sidCookie, $request->getClientIp());
    } else {
        $faqSession->checkSessionId($sidGet, $request->getClientIp());
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
        if (is_null($sidCookie) && !is_null($sidGet)) {
            $sids = sprintf('sid=%d&amp;lang=%s&amp;', $sidGet, $faqLangCode);
        }
    }
} elseif (
    !$faqSession->setCookie(
        Session::PMF_COOKIE_NAME_SESSIONID,
        $faqSession->getCurrentSessionId(),
        $request->server->get('REQUEST_TIME') + 3600
    )
) {
    $sids = sprintf('lang=%s&amp;', $faqLangCode);
}

//
// Found an article language?
//
$lang = Filter::filterVar($request->request->get('artlang'), FILTER_SANITIZE_SPECIAL_CHARS);
if ($lang !== '' && !Language::isASupportedLanguage($lang)) {
    $lang = Filter::filterVar($request->query->get('artlang'), FILTER_SANITIZE_SPECIAL_CHARS);
    if ($lang !== '' && !Language::isASupportedLanguage($lang)) {
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
$searchTerm = Filter::filterVar($request->query->get('search'), FILTER_SANITIZE_SPECIAL_CHARS, '');

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
// Create new SEO objects
//
$seo = new Seo($faqConfig);
$seoEntity = new SeoEntity();
$seoEntity->setReferenceLanguage($lang);

//
// Create URL
//
$faqSystem = new System();
$faqLink = new Link($faqSystem->getSystemUri($faqConfig), $faqConfig);
$currentPageUrl = Strings::htmlspecialchars($faqLink->getCurrentUrl());

//
// Found a record ID?
//
$id = Filter::filterVar($request->query->get('id'), FILTER_VALIDATE_INT, 0);
if ($id !== 0) {
    $faq->getRecord($id);

    $seoEntity
        ->setType(SeoType::FAQ)
        ->setReferenceId($id);
    $seoData = $seo->get($seoEntity);

    $title = $seoData->getTitle();
    $keywords = ',' . $faq->faqRecord['keywords'];
    $metaDescription = str_replace('"', '', $seoData->getDescription() ?? '');
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
    $title = '';
    $keywords = '';
    $metaDescription = str_replace('"', '', $faqConfig->get('seo.description'));
}

//
// found a solution ID?
//
$solutionId = Filter::filterVar($request->query->get('solution_id'), FILTER_VALIDATE_INT);
if ($solutionId) {
    $keywords = '';
    $faqData = $faq->getIdFromSolutionId($solutionId);
    $id = $faqData['id'];
    $lang = $faqData['lang'];

    $seoEntity
        ->setType(SeoType::FAQ)
        ->setReferenceId($id)
        ->setReferenceLanguage($lang);
    $seoData = $seo->get($seoEntity);

    $title = $seoData->getTitle();
    $keywords = ',' . $faq->getKeywords($id);
    $metaDescription = str_replace('"', '', $seoData->getDescription());
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

//
// Handle the Tagging ID
//
$taggingId = Filter::filterVar($request->query->get('tagging_id'), FILTER_VALIDATE_INT);
if (!is_null($taggingId)) {
    $title = ' - ' . $oTag->getTagNameById($taggingId);
    $keywords = '';
}

//
// Handle the SiteMap
//
$letter = Filter::filterVar($request->query->get('letter'), FILTER_SANITIZE_SPECIAL_CHARS);
if (!is_null($letter) && (1 == Strings::strlen($letter))) {
    $title = ' - ' . $letter . '...';
    $keywords = $letter;
}

//
// Found a category ID?
//
$cat = Filter::filterVar($request->query->get('cat'), FILTER_VALIDATE_INT, 0);
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

if (isset($cat) && ($cat !== 0) && ($id === 0) && isset($category->categoryName[$cat]['name'])) {
    $seoEntity
        ->setType(SeoType::CATEGORY)
        ->setReferenceId($cat);
    $seoData = $seo->get($seoEntity);
    $title = $seoData->getTitle() ?? $category->categoryName[$cat]['name'];
    $metaDescription = $seoData->getDescription() ?? $category->categoryName[$cat]['description'];
}

//
// Glossary
//
if ('glossary' === $action) {
    $title = $faqConfig->get('seo.glossary.title');
    $metaDescription = $faqConfig->get('seo.glossary.description');
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
// Check if the FAQ should be secured
//
if ($faqConfig->get('security.enableLoginOnly')) {
    if ($user->isLoggedIn()) {
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
    $faqConfig->get('main.templateSet')
);

$categoryRelation = new Relation($faqConfig, $category);

$categoryHelper = new HelperCategory();
$categoryHelper->setCategory($category);
$categoryHelper->setConfiguration($faqConfig);
$categoryHelper->setCategoryRelation($categoryRelation);

$keywordsArray = array_merge(explode(',', (string)$keywords), explode(',', (string)$faqConfig->get('main.metaKeywords')));
$keywordsArray = array_filter($keywordsArray, 'strlen');
shuffle($keywordsArray);
$keywords = implode(',', $keywordsArray);

$loginMessage = is_null($error) ? '' : '<p class="alert alert-danger">' . $error . '</p>';


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
    'title' => $title,
    'baseHref' => Strings::htmlspecialchars($faqSystem->getSystemUri($faqConfig)),
    'version' => $faqConfig->getVersion(),
    'header' => Strings::htmlentities(str_replace('"', '', $faqConfig->getTitle())),
    'metaDescription' => Strings::htmlspecialchars($metaDescription ?? $faqConfig->get('seo.description')),
    'metaKeywords' => Strings::htmlentities($keywords),
    'metaPublisher' => Strings::htmlentities($faqConfig->get('main.metaPublisher')),
    'metaLanguage' => Translation::get('metaLanguage'),
    'metaRobots' => $seo->getMetaRobots($action),
    'phpmyfaqVersion' => $faqConfig->getVersion(),
    'stylesheet' => Translation::get('dir') == 'rtl' ? 'style.rtl' : 'style',
    'currentPageUrl' => $currentPageUrl,
    'action' => $action,
    'dir' => Translation::get('dir'),
    'formActionUrl' => '?' . $sids . 'action=search',
    'searchBox' => Translation::get('msgSearch'),
    'searchTerm' => Strings::htmlentities($searchTerm, ENT_QUOTES),
    'categoryId' => ($cat === 0) ? '%' : (int)$cat,
    'headerCategories' => Translation::get('msgFullCategories'),
    'msgCategory' => Translation::get('msgCategory'),
    'msgExportAllFaqs' => Translation::get('msgExportAllFaqs'),
    'languageBox' => Translation::get('msgLanguageSubmit'),
    'renderUri' => $renderUri,
    'switchLanguages' => LanguageHelper::renderSelectLanguage($faqLangCode, true),
    'copyright' => System::getPoweredByString(true),
    'registerUser' => $faqConfig->get('security.enableRegistration') ? '<a href="user/register">' .
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
    'realname' => Translation::get('ad_user_realname'),
    'password' => Translation::get('ad_auth_passwd'),
    'rememberMe' => Translation::get('rememberMe'),
    'submitRegister' => Translation::get('submitRegister'),
    'headerChangePassword' => Translation::get('ad_passwd_cop'),
    'msgUsername' => Translation::get('ad_auth_user'),
    'msgEmail' => Translation::get('ad_entry_email'),
    'msgSubmit' => Translation::get('msgNewContentSubmit'),
    'loginPageMessage' => Translation::get('loginPageMessage'),
    'msgAdvancedSearch' => Translation::get('msgAdvancedSearch'),
    'writeTagCloudHeader' => Translation::get('msg_tags'),
    'writeTags' => $oTag->renderTagCloud(),
    'currentYear' => date('Y', time())
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

if ($faqConfig->get('main.enablePrivacyLink')) {
    $privacyLink = sprintf(
        '<a class="pmf-nav-link-footer" target="_blank" href="%s">%s</a>',
        Strings::htmlentities($faqConfig->get('main.privacyURL')),
        Translation::get('msgPrivacyNote')
    );
} else {
    $privacyLink = '';
}

$tplNavigation = [
    'backToHome' => '<a class="nav-link" href="./index.html">' . Translation::get('msgHome') . '</a>',
    'allCategories' => '<a class="pmf-nav-link" href="./show-categories.html">' .
        Translation::get('msgShowAllCategories') . '</a>',
    'msgAddContent' => '<a class="pmf-nav-link" href="./add-faq.html">' .
        Translation::get('msgAddContent') . '</a>',
    'msgQuestion' => $faqConfig->get('main.enableAskQuestions')
        ?
        '<a class="pmf-nav-link" href="./add-question.html">' . Translation::get('msgQuestion') . '</a>'
        :
        '',
    'msgOpenQuestions' => $faqConfig->get('main.enableAskQuestions')
        ?
        '<a class="pmf-nav-link" href="./open-questions.html">' .
        Translation::get('msgOpenQuestions') . '</a>'
        :
        '',
    'msgSearch' => '<a class="nav-link" href="./search.html">' . Translation::get('msgAdvancedSearch') . '</a>',
    'msgContact' => '<a class="pmf-nav-link-footer" href="./contact.html">' . Translation::get('msgContact') .
        '</a>',
    'msgGlossary' => '<a class="pmf-nav-link-footer" href="./glossary.html">' .
        Translation::get('ad_menu_glossary') . '</a>',
    'privacyLink' => $privacyLink,
    'cookiePreferences' => '<a id="showCookieConsent" class="pmf-nav-link-footer">'
        . Translation::get('cookiePreferences') . '</a>',
    'faqOverview' => '<a class="pmf-nav-link-footer" href="./overview.html">' .
        Translation::get('faqOverview') . '</a>',
    'showSitemap' => '<a class="pmf-nav-link-footer" href="./sitemap/A/' . $faqLangCode . '.html">' .
        Translation::get('msgSitemap') . '</a>',
    'breadcrumbHome' => '<a href="./index.html">' . Translation::get('msgHome') . '</a>',
];

$tplNavigation['faqHome'] = Strings::htmlspecialchars($faqConfig->getDefaultUrl());
$tplNavigation['activeSearch'] = ('search' == $action) ? 'active' : '';
$tplNavigation['activeAllCategories'] = ('show' == $action) ? 'active' : '';
$tplNavigation['activeAddContent'] = ('add' == $action) ? 'active' : '';
$tplNavigation['activeAddQuestion'] = ('ask' == $action) ? 'active' : '';
$tplNavigation['activeOpenQuestions'] = ('open-questions' == $action) ? 'active' : '';
$tplNavigation['activeLogin'] = ('login' == $action) ? 'active' : '';

//
// Show login box or logged-in user information
//
if ($user->isLoggedIn() && $user->getUserId() > 0) {
    if ($user->perm->hasPermission($user->getUserId(), PermissionType::VIEW_ADMIN_LINK->value) || $user->isSuperAdmin()) {
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
            'msgAdmin' => $adminSection,
            'activeUserControl' => ('ucp' == $action) ? 'active' : '',
            'msgUserControlDropDown' => sprintf(
                '<a class="dropdown-item" href="user/ucp">%s</a>',
                Translation::get('headerUserControlPanel')
            ),
            'msgBookmarks' => sprintf(
                '<a class="dropdown-item" href="user/bookmarks">%s</a>',
                Translation::get('msgBookmarks')
            ),
            'msgUserRemoval' => sprintf(
                '<a class="dropdown-item" href="user/request-removal">%s</a>',
                Translation::get('ad_menu_RequestRemove')
            ),
            'msgLogoutUser' => sprintf(
                '<a class="dropdown-item" href="user/logout?csrf=%s">%s</a>',
                Token::getInstance()->getTokenString('logout'),
                Translation::get('ad_menu_logout'),
            )
        ]
    );
} else {
    if ($faqConfig->get('main.maintenanceMode')) {
        $msgLoginUser = '<a class="dropdown-item" href="./admin/">%s</a>';
    } else {
        $msgLoginUser = '<a class="dropdown-item" href="./login">%s</a>';
    }

    $template->parseBlock(
        'index',
        'notLoggedIn',
        [
            'msgRegisterUser' => $faqConfig->get('security.enableRegistration')
                ?
                '<a class="dropdown-item" href="user/register">' .
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
    $template->parseBlock('index', 'debugMode');
}

if ($faqConfig->get('main.enableCookieConsent')) {
    $template->parseBlock('index', 'cookieConsentEnabled');
}

if ('twofactor' === $action) {
    $includePhp = 'login.php';
}

//
// Include requested PHP file
//
require $includePhp;

//
// Get the main template, set main variables
//
$template->parse('index', [...$tplMainPage, ...$tplNavigation]);
$template->merge('sidebar', 'index');
$template->merge('mainPageContent', 'index');

//
// Check for 404 HTTP status code
//
if ($response->getStatusCode() === Response::HTTP_NOT_FOUND || $action === '404') {
    $template = new Template(
        [
            'index' => '404.html',
            'mainPageContent' => '',
        ],
        $faqConfig->get('main.templateSet')
    );
    $template->parse('index', [...$tplMainPage, ...$tplNavigation]);
}

$response->setContent($template->render());
$response->setCache([
    'must_revalidate' => false,
    'no_cache' => false,
    'no_store' => false,
    'no_transform' => false,
    'public' => true,
    'private' => false,
    'proxy_revalidate' => false,
    'max_age' => 600,
    's_maxage' => 600,
    'stale_if_error' => 86400,
    'stale_while_revalidate' => 60,
    'immutable' => true,
    'last_modified' => new DateTime()
]);

$response->send();
