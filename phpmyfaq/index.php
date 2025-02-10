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
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\TwoFactor;
use phpMyFAQ\User\UserAuthentication;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
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
$csrfLogoutToken = Token::getInstance()->getTokenString('logout');


//
// Service Containers
//
$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__));
try {
    $loader->load('src/services.php');
} catch (\Exception $e) {
    echo $e->getMessage();
}

$faqConfig = $container->get('phpmyfaq.configuration');

//
// Get language (default: english)
//
$Language = $container->get('phpmyfaq.language');
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

//
// Set actual template set name
//
TwigWrapper::setTemplateSetName($faqConfig->getTemplateSet());

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
$userId = Filter::filterVar($request->request->get('userid'), FILTER_VALIDATE_INT);

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
if ($token !== '' && !is_null($userId)) {
    if (strlen((string)$token) === 6 && is_numeric((string)$token)) {
        $user = new CurrentUser($faqConfig);
        $user->getUserById($userId);
        $tfa = new TwoFactor($faqConfig, $user);
        $res = $tfa->validateToken($token, $userId);
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
        $userId = $user->getUserId();
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

//
// Found a record ID?
//
$id = Filter::filterVar($request->query->get('id'), FILTER_VALIDATE_INT, 0);
if ($id !== 0) {
    $faq->getFaq($id);

    $seoEntity
        ->setType(SeoType::FAQ)
        ->setReferenceId($id);
    $seoData = $seo->get($seoEntity);

    $title = $seoData->getTitle();
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
} else {
    $title = '';
    $metaDescription = str_replace('"', '', $faqConfig->get('seo.description'));
}

//
// found a solution ID?
//
$solutionId = Filter::filterVar($request->query->get('solution_id'), FILTER_VALIDATE_INT);
if ($solutionId) {
    $faqData = $faq->getIdFromSolutionId($solutionId);
    $id = $faqData['id'];
    $lang = $faqData['lang'];

    $seoEntity
        ->setType(SeoType::FAQ)
        ->setReferenceId($id)
        ->setReferenceLanguage($lang);
    $seoData = $seo->get($seoEntity);

    $title = $seoData->getTitle();
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
}

//
// Handle the Tagging ID
//
$taggingId = Filter::filterVar($request->query->get('tagging_id'), FILTER_VALIDATE_INT);
if (!is_null($taggingId)) {
    $title = ' - ' . $oTag->getTagNameById($taggingId);
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
} else {
    if (isset($solutionId) && is_numeric($solutionId)) {
        // show the record with the solution ID
        $includeTemplate = 'faq.html';
        $includePhp = 'faq.php';
    } else {
        $includeTemplate = 'startpage.html';
        $includePhp = 'startpage.php';
    }
}

//
// Check if the FAQ should be secured
//
if ($faqConfig->get('security.enableLoginOnly')) {
    if (!$user->isLoggedIn() && ($action !== 'login' && $action !== 'password')) {
        $redirect = new RedirectResponse($faqSystem->getSystemUri($faqConfig) . 'login');
        $redirect->send();
    }
}

$categoryRelation = new Relation($faqConfig, $category);

$categoryHelper = new HelperCategory();
$categoryHelper->setCategory($category);
$categoryHelper->setConfiguration($faqConfig);
$categoryHelper->setCategoryRelation($categoryRelation);

$loginMessage = is_null($error) ? '' : '<p class="alert alert-danger">' . $error . '</p>';
$isUserHasAdminRights = $user->perm->hasPermission($user->getUserId(), PermissionType::VIEW_ADMIN_LINK->value);

//
// Twig Template variables
//
$templateVars = [
    'isMaintenanceMode' => $faqConfig->get('main.maintenanceMode'),
    'isCompletelySecured' => $faqConfig->get('security.enableLoginOnly'),
    'isDebugEnabled' => DEBUG,
    'richSnippetsEnabled' => $faqConfig->get('seo.enableRichSnippets'),
    'tplSetName' => TwigWrapper::getTemplateSetName(),
    'msgLoginUser' => $user->isLoggedIn() ? $user->getUserData('display_name') : Translation::get('msgLoginUser'),
    'isUserLoggedIn' => $user->isLoggedIn(),
    'isUserHasAdminRights' => $isUserHasAdminRights || $user->isSuperAdmin(),
    'title' => $title,
    'baseHref' => $faqSystem->getSystemUri($faqConfig),
    'customCss' => $faqConfig->getCustomCss(),
    'version' => $faqConfig->getVersion(),
    'header' => str_replace('"', '', $faqConfig->getTitle()),
    'metaDescription' => $metaDescription ?? $faqConfig->get('seo.description'),
    'metaPublisher' => $faqConfig->get('main.metaPublisher'),
    'metaLanguage' => Translation::get('metaLanguage'),
    'metaRobots' => $seo->getMetaRobots($action),
    'phpmyfaqVersion' => $faqConfig->getVersion(),
    'stylesheet' => Translation::get('direction') == 'rtl' ? 'style.rtl' : 'style',
    'currentPageUrl' => $request->getSchemeAndHttpHost() . $request->getRequestUri(),
    'action' => $action,
    'dir' => Translation::get('direction'),
    'formActionUrl' => '?' . $sids . 'action=search',
    'searchBox' => Translation::get('msgSearch'),
    'searchTerm' => Strings::htmlentities($searchTerm, ENT_QUOTES),
    'categoryId' => ($cat === 0) ? '%' : (int)$cat,
    'headerCategories' => Translation::get('msgFullCategories'),
    'msgCategory' => Translation::get('msgCategory'),
    'msgExportAllFaqs' => Translation::get('msgExportAllFaqs'),
    'languageBox' => Translation::get('msgLanguageSubmit'),
    'switchLanguages' => LanguageHelper::renderSelectLanguage($faqLangCode, true),
    'copyright' => System::getPoweredByString(true),
    'isUserRegistrationEnabled' => $faqConfig->get('security.enableRegistration'),
    'msgRegisterUser' => Translation::get('msgRegisterUser'),
    'sendPassword' => '<a href="?action=password">' . Translation::get('lostPassword') . '</a>',
    'msgFullName' => Translation::get('ad_user_loggedin') . $user->getLogin(),
    'msgLoginName' => $user->getUserData('display_name'),
    'loginHeader' => Translation::get('msgLoginUser'),
    'loginMessage' => $loginMessage,
    'writeLoginPath' => $faqSystem->getSystemUri($faqConfig) . '?' . Filter::getFilteredQueryString(),
    'faqloginaction' => $action,
    'login' => Translation::get('ad_auth_ok'),
    'username' => Translation::get('ad_auth_user'),
    'realname' => Translation::get('msgRealname'),
    'password' => Translation::get('ad_auth_passwd'),
    'rememberMe' => Translation::get('rememberMe'),
    'submitRegister' => Translation::get('submitRegister'),
    'headerChangePassword' => Translation::get('ad_passwd_cop'),
    'msgUsername' => Translation::get('ad_auth_user'),
    'msgEmail' => Translation::get('msgEmail'),
    'msgSubmit' => Translation::get('msgNewContentSubmit'),
    'loginPageMessage' => Translation::get('loginPageMessage'),
    'msgAdvancedSearch' => Translation::get('msgAdvancedSearch'),
    'currentYear' => date('Y', time()),
    'cookieConsentEnabled' =>  $faqConfig->get('layout.enableCookieConsent'),
];

$topNavigation = [
    [
        'name' => Translation::get('msgShowAllCategories'),
        'link' => './show-categories.html',
        'active' => ('show' === $action) ? 'active' : '',
    ],
    [
        'name' => Translation::get('msgAddContent'),
        'link' => './add-faq.html',
        'active' => ('add' === $action) ? 'active' : '',
    ],
    [
        'name' => Translation::get('msgQuestion'),
        'link' => './add-question.html',
        'active' => ('ask' == $action) ? 'active' : '',
    ],
    [
        'name' => Translation::get('msgOpenQuestions'),
        'link' => './open-questions.html',
        'active' => ('open-questions' == $action) ? 'active' : '',
    ],
];

$footerNavigation = [
    [
        'name' => Translation::get('faqOverview'),
        'link' => './overview.html',
        'active' => ('faq-overview' == $action) ? 'active' : '',
    ],
    [
        'name' => Translation::get('msgSitemap'),
        'link' => './sitemap/A/' . $faqLangCode . '.html',
        'active' => ('sitemap' == $action) ? 'active' : '',
    ],
    [
        'name' => Translation::get('ad_menu_glossary'),
        'link' => './glossary.html',
        'active' => ('glossary' == $action) ? 'active' : '',
    ],
    [
        'name' => Translation::get('msgContact'),
        'link' => './contact.html',
        'active' => ('contact' == $action) ? 'active' : '',
    ],
];

$templateVars = [
    ... $templateVars,
    'faqHome' => $faqConfig->getDefaultUrl(),
    'topNavigation' => $topNavigation,
    'isAskQuestionsEnabled' => $faqConfig->get('main.enableAskQuestions'),
    'isOpenQuestionsEnabled' => $faqConfig->get('main.enableAskQuestions'),
    'footerNavigation' => $footerNavigation,
    'isPrivacyLinkEnabled' => $faqConfig->get('layout.enablePrivacyLink'),
    'urlPrivacyLink' => $faqConfig->get('main.privacyURL'),
    'msgPrivacyNote' => Translation::get('msgPrivacyNote'),
    'isCookieConsentEnabled' => $faqConfig->get('layout.enableCookieConsent'),
    'cookiePreferences' => Translation::get('cookiePreferences'),
    'currentYear' => date('Y', time()),
];

//
// Show login box or logged-in user information
//
if ($user->isLoggedIn() && $user->getUserId() > 0) {
    if (
        $user->perm->hasPermission($user->getUserId(), PermissionType::VIEW_ADMIN_LINK->value) ||
        $user->isSuperAdmin()
    ) {
        $templateVars = [
            ... $templateVars,
            'msgAdmin' => Translation::get('adminSection'),
        ];
    }

    if ($user->getUserAuthSource() === 'local') {
        $templateVars = [
            ... $templateVars,
            'msgUserControlDropDown' => Translation::get('headerUserControlPanel'),
        ];
    }

    $templateVars = [
        ... $templateVars,
        'msgBookmarks' => Translation::get('msgBookmarks'),
        'msgUserRemoval' => Translation::get('ad_menu_RequestRemove'),
        'msgLogoutUser' => Translation::get('ad_menu_logout'),
        'csrfLogout' => $csrfLogoutToken,
    ];
}

if ('twofactor' === $action) {
    $includePhp = 'login.php';
}

//
// Include requested PHP file
//
require $includePhp;

//
// Check for 404 HTTP status code
//
if ($response->getStatusCode() === Response::HTTP_NOT_FOUND || $action === '404') {
    // @todo handle 404 :-)
}

$response->setContent($twigTemplate->render($templateVars));

if ('logout' === $action) {
    $response->headers->set('Cache-Control', 'no-cache, no-store, private');
    $response->headers->set('Vary', 'Accept-Language, Accept-Encoding, Cookie');
}

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

//
// Avoid automatic downloads
// and prevent browsers from interpreting files as a different MIME type than what is specified
//
if ($action !== 'attachment') {
    $response->headers->set('Content-Disposition', 'inline');
}
$response->headers->set('X-Content-Type-Options', 'nosniff');

$response->send();
