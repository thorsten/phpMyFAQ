<?php

/**
 * This is the main public frontend page of phpMyFAQ. It detects the browser's
 * language, gets and sets all cookies, posts and gets information and includes
 * the templates we need and sets all internal variables to the template
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
 * @copyright 2001-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2001-02-12
 */

use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Category;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Controller\PageNotFoundController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\SeoType;
use phpMyFAQ\Environment;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper as HelperCategory;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Link;
use phpMyFAQ\Seo;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TwigWrapper;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\TwoFactor;
use phpMyFAQ\User\UserAuthentication;
use phpMyFAQ\User\UserSession;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

//
// Define the named constant used as a check by any included PHP file
//
const IS_VALID_PHPMYFAQ = null;

//
// Bootstrapping
//
require __DIR__ . '/src/Bootstrap.php';

//
// Service Containers
//
$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__));
try {
    $loader->load('src/services.php');
} catch (\Exception $exception) {
    echo $exception->getMessage();
}

$faqConfig = $container->get('phpmyfaq.configuration');

//
// Create Request + Response
//
$request = Request::createFromGlobals();
$response = new Response();
$response->headers->set('Content-Type', 'text/html');
$csrfLogoutToken = Token::getInstance($container->get('session'))->getTokenString('logout');

//
// Get language (default: English)
//
$Language = $container->get('phpmyfaq.language');
$faqLangCode = $faqConfig->get('main.languageDetection')
    ? $Language->setLanguageWithDetection($faqConfig->get('main.language'))
    : $Language->setLanguageFromConfiguration($faqConfig->get('main.language'));
$faqConfig->setLanguage($Language);

if (!Language::isASupportedLanguage($faqLangCode)) {
    $faqLangCode = 'en';
}

//
// Set a translation class
//
try {
    Translation::create()
        ->setTranslationsDir(PMF_LANGUAGE_DIR)
        ->setDefaultLanguage('en')
        ->setCurrentLanguage($faqLangCode)
        ->setMultiByteLanguage();
} catch (Exception $exception) {
    echo '<strong>Error:</strong> ' . $exception->getMessage();
}

//
// Initializing a static string wrapper
//
Strings::init($faqLangCode);

//
// Try Symfony Router first
//
try {
    // Load routes
    $routes = require __DIR__ . '/src/public-routes.php';

    // Create URL matcher
    $context = new RequestContext();
    $context->fromRequest($request);
    $matcher = new UrlMatcher($routes, $context);

    // Try to match the current route
    $parameters = $matcher->match($request->getPathInfo());

    // Extract controller and method
    $controllerCallable = $parameters['_controller'];
    unset($parameters['_controller'], $parameters['_route'], $parameters['_methods']);

    // Instantiate controller and call method
    if (is_array($controllerCallable)) {
        [$controllerClass, $method] = $controllerCallable;
        $controller = new $controllerClass();
        $routeResponse = $controller->$method($request, ...$parameters);
    } else {
        $routeResponse = $controllerCallable($request, ...$parameters);
    }

    // Send response and exit
    $routeResponse->send();
    exit;
} catch (ResourceNotFoundException $e) {
    // No route matched - continue with legacy logic below
}

//
// Set actual template set name
//
TwigWrapper::setTemplateSetName($faqConfig->getTemplateSet());

/*
 * Initialize attachment factory
 */
AttachmentFactory::init(
    $faqConfig->get('records.defaultAttachmentEncKey'),
    $faqConfig->get('records.enableAttachmentEncryption'),
);

//
// Get user action
//
$action = Filter::filterVar($request->query->get('action'), FILTER_SANITIZE_SPECIAL_CHARS);

//
// Authenticate the current user
//
$error = null;
$loginVisibility = 'hidden';

$faqusername = Filter::filterVar($request->request->get('faqusername'), FILTER_SANITIZE_SPECIAL_CHARS);
$faqpassword = Filter::filterVar(
    $request->request->get('faqpassword'),
    FILTER_SANITIZE_SPECIAL_CHARS,
    FILTER_FLAG_NO_ENCODE_QUOTES,
);
$faqaction = Filter::filterVar($request->request->get('faqloginaction'), FILTER_SANITIZE_SPECIAL_CHARS);
$rememberMe = Filter::filterVar($request->request->get('faqrememberme'), FILTER_VALIDATE_BOOLEAN);
$token = Filter::filterVar($request->request->get('token'), FILTER_SANITIZE_SPECIAL_CHARS);
$userId = (int) Filter::filterVar($request->request->get('userid'), FILTER_VALIDATE_INT);

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
if ($csrfToken !== '' && Token::getInstance($container->get('session'))->verifyToken('logout', $csrfToken)) {
    $csrfChecked = true;
} else {
    $csrfChecked = false;
}

//
// Validating token from 2FA if given; else: returns an error message
//
if ($token !== '' && !is_null($userId)) {
    if (strlen((string) $token) === 6 && is_numeric((string) $token)) {
        $user = new CurrentUser($faqConfig);
        $user->getUserById($userId);
        $tfa = new TwoFactor($faqConfig, $user);
        $res = $tfa->validateToken($token, $userId);
        if (!$res) {
            $error = Translation::get(key: 'msgTwofactorErrorToken');
            $action = 'twofactor';
        } else {
            $user->twoFactorSuccess();
            $redirect = new RedirectResponse($faqConfig->getDefaultUrl());
            $redirect->send();
        }
    } else {
        $error = Translation::get(key: 'msgTwofactorErrorToken');
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

if (isset($userAuth) && $userAuth instanceof UserAuthentication && $userAuth->hasTwoFactorAuthentication()) {
    $action = 'twofactor';
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
$sidGet = Filter::filterVar($request->query->get(UserSession::KEY_NAME_SESSION_ID), FILTER_VALIDATE_INT);
$sidCookie = Filter::filterVar($request->cookies->get(UserSession::COOKIE_NAME_SESSION_ID), FILTER_VALIDATE_INT);
$faqSession = new UserSession($faqConfig);
$faqSession->setCurrentUser($user);

// Note: do not track internal calls
$internal = false;

if ($request->headers->get('user-agent') !== null) {
    $internal = str_starts_with($request->headers->get('user-agent'), 'phpMyFAQ%2F');
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
        $faqSession->setCookie(UserSession::COOKIE_NAME_SESSION_ID, $faqSession->getCurrentSessionId());
        if (is_null($sidCookie)) {
            $sids = sprintf('sid=%d&lang=%s&', $faqSession->getCurrentSessionId(), $faqLangCode);
        }
    } elseif (is_null($sidGet) || is_null($sidCookie)) {
        if (is_null($sidCookie) && !is_null($sidGet)) {
            $sids = sprintf('sid=%d&lang=%s&', $sidGet, $faqLangCode);
        }
    }
} else {
    $faqSession->setCookie(
        UserSession::COOKIE_NAME_SESSION_ID,
        $faqSession->getCurrentSessionId(),
        $request->server->get('REQUEST_TIME') + 3600,
    );
    $sids = sprintf('lang=%s&', $faqLangCode);
}

//
// Found an article language?
//
$lang = Filter::filterVar($request->attributes->get('artlang'), FILTER_SANITIZE_SPECIAL_CHARS);
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
$faq->setUser($currentUser)->setGroups($currentGroups);

//
// Create a new Category object
//
$category = new Category($faqConfig, $currentGroups, true);
$category->setUser($currentUser)->setGroups($currentGroups);

//
// Create a new Tags object
//
$oTag = new Tags($faqConfig);
$oTag->setUser($currentUser)->setGroups($currentGroups);

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

    $seoEntity->setSeoType(SeoType::FAQ)->setReferenceId($id);
    $seoData = $seo->get($seoEntity);

    $title = $seoData->getTitle();
    $metaDescription = str_replace('"', '', $seoData->getDescription() ?? '');
    $url = sprintf(
        '%sindex.php?%saction=faq&cat=%d&id=%d&artlang=%s',
        Strings::htmlentities($faqConfig->getDefaultUrl()),
        $sids,
        $category->getCategoryIdFromFaq($id),
        $id,
        $lang,
    );
    $faqLink = new Link($url, $faqConfig);
    $faqLink->setTitle($faq->faqRecord['title']);
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

    $seoEntity->setSeoType(SeoType::FAQ)->setReferenceId($id)->setReferenceLanguage($lang);
    $seoData = $seo->get($seoEntity);

    $title = $seoData->getTitle();
    $metaDescription = str_replace('"', '', $seoData->getDescription());
    $url = sprintf(
        '%sindex.php?%saction=faq&cat=%d&id=%d&artlang=%s',
        Strings::htmlentities($faqConfig->getDefaultUrl()),
        $sids,
        $faqData['category_id'],
        $id,
        $lang,
    );
    $faqLink = new Link($url, $faqConfig);
    $faqLink->setTitle(Strings::htmlentities($faqData['question']));
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

if (isset($cat) && $cat !== 0 && $id === 0 && $category->getCategoryName($cat) !== null) {
    $seoEntity->setSeoType(SeoType::CATEGORY)->setReferenceId($cat);
    $seoData = $seo->get($seoEntity);
    $title = $seoData->getTitle() ?? $category->getCategoryName($cat);
    $metaDescription = $seoData->getDescription() ?? $category->getCategoryDescription($cat);
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
} elseif (isset($solutionId) && is_numeric($solutionId)) {
    // show the record with the solution ID
    $includeTemplate = 'faq.html';
    $includePhp = 'faq.php';
} else {
    $includeTemplate = 'startpage.html';
    $includePhp = 'startpage.php';
}

//
// Check if the FAQ should be secured
//
if (
    $faqConfig->get('security.enableLoginOnly') && (
        !$user->isLoggedIn() && ($action !== 'login' && $action !== 'password')
    )
) {
    $redirect = new RedirectResponse($faqSystem->getSystemUri($faqConfig) . 'login');
    $redirect->send();
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
    'isDebugEnabled' => Environment::isDebugMode(),
    'richSnippetsEnabled' => $faqConfig->get('seo.enableRichSnippets'),
    'tplSetName' => TwigWrapper::getTemplateSetName(),
    'msgLoginUser' => $user->isLoggedIn() ? $user->getUserData('display_name') : Translation::get(key: 'msgLoginUser'),
    'isUserLoggedIn' => $user->isLoggedIn(),
    'isUserHasAdminRights' => $isUserHasAdminRights || $user->isSuperAdmin(),
    'title' => $title,
    'baseHref' => $faqSystem->getSystemUri($faqConfig),
    'customCss' => $faqConfig->getCustomCss(),
    'version' => $faqConfig->getVersion(),
    'header' => str_replace('"', '', $faqConfig->getTitle()),
    'metaDescription' => $metaDescription ?? $faqConfig->get('seo.description'),
    'metaPublisher' => $faqConfig->get('main.metaPublisher'),
    'metaLanguage' => Translation::get(key: 'metaLanguage'),
    'metaRobots' => $seo->getMetaRobots($action),
    'phpmyfaqVersion' => $faqConfig->getVersion(),
    'stylesheet' => Translation::get(key: 'direction') == 'rtl' ? 'style.rtl' : 'style',
    'currentPageUrl' => $request->getSchemeAndHttpHost() . $request->getRequestUri(),
    'action' => $action,
    'dir' => Translation::get(key: 'direction'),
    'formActionUrl' => '?' . $sids . 'action=search',
    'searchBox' => Translation::get(key: 'msgSearch'),
    'searchTerm' => Strings::htmlentities($searchTerm, ENT_QUOTES),
    'categoryId' => $cat === 0 ? '%' : (int) $cat,
    'headerCategories' => Translation::get(key: 'msgFullCategories'),
    'msgCategory' => Translation::get(key: 'msgCategory'),
    'msgExportAllFaqs' => Translation::get(key: 'msgExportAllFaqs'),
    'languageBox' => Translation::get(key: 'msgLanguageSubmit'),
    'switchLanguages' => LanguageHelper::renderSelectLanguage($faqLangCode, true),
    'copyright' => System::getPoweredByString(),
    'isUserRegistrationEnabled' => $faqConfig->get('security.enableRegistration'),
    'pluginStylesheets' => $faqConfig->getPluginManager()->getAllPluginStylesheets(),
    'pluginScripts' => $faqConfig->getPluginManager()->getAllPluginScripts(),
    'msgRegisterUser' => Translation::get(key: 'msgRegisterUser'),
    'sendPassword' =>
        '<a href="'
        . $faqSystem->getSystemUri($faqConfig)
        . 'forgot-password">'
        . Translation::get(key: 'lostPassword')
        . '</a>',
    'msgFullName' => Translation::get(key: 'ad_user_loggedin') . $user->getLogin(),
    'msgLoginName' => $user->getUserData('display_name'),
    'loginHeader' => Translation::get(key: 'msgLoginUser'),
    'loginMessage' => $loginMessage,
    'writeLoginPath' => $faqSystem->getSystemUri($faqConfig) . '?' . Filter::getFilteredQueryString(),
    'faqloginaction' => $action,
    'login' => Translation::get(key: 'ad_auth_ok'),
    'username' => Translation::get(key: 'ad_auth_user'),
    'realname' => Translation::get(key: 'msgRealname'),
    'password' => Translation::get(key: 'ad_auth_passwd'),
    'rememberMe' => Translation::get(key: 'rememberMe'),
    'submitRegister' => Translation::get(key: 'submitRegister'),
    'headerChangePassword' => Translation::get(key: 'ad_passwd_cop'),
    'msgUsername' => Translation::get(key: 'ad_auth_user'),
    'msgEmail' => Translation::get(key: 'msgEmail'),
    'msgSubmit' => Translation::get(key: 'msgNewContentSubmit'),
    'loginPageMessage' => Translation::get(key: 'loginPageMessage'),
    'msgAdvancedSearch' => Translation::get(key: 'msgAdvancedSearch'),
    'currentYear' => date(
        format: 'Y',
        timestamp: time(),
    ),
    'cookieConsentEnabled' => $faqConfig->get('layout.enableCookieConsent'),
];

$topNavigation = [
    [
        'name' => Translation::get(key: 'msgShowAllCategories'),
        'link' => './show-categories.html',
        'active' => 'show' === $action ? 'active' : '',
    ],
    [
        'name' => Translation::get(key: 'msgAddContent'),
        'link' => './add-faq.html',
        'active' => 'add' === $action ? 'active' : '',
    ],
    [
        'name' => Translation::get(key: 'msgQuestion'),
        'link' => './add-question.html',
        'active' => 'ask' == $action ? 'active' : '',
    ],
    [
        'name' => Translation::get(key: 'msgOpenQuestions'),
        'link' => './open-questions.html',
        'active' => 'open-questions' == $action ? 'active' : '',
    ],
];

$footerNavigation = [
    [
        'name' => Translation::get(key: 'faqOverview'),
        'link' => './overview.html',
        'active' => 'faq-overview' == $action ? 'active' : '',
    ],
    [
        'name' => Translation::get(key: 'msgSitemap'),
        'link' => './sitemap/A/' . $faqLangCode . '.html',
        'active' => 'sitemap' == $action ? 'active' : '',
    ],
    [
        'name' => Translation::get(key: 'ad_menu_glossary'),
        'link' => './glossary.html',
        'active' => 'glossary' == $action ? 'active' : '',
    ],
    [
        'name' => Translation::get(key: 'msgContact'),
        'link' => './contact.html',
        'active' => 'contact' == $action ? 'active' : '',
    ],
];

$templateVars = [
    ...$templateVars,
    'faqHome' => $faqConfig->getDefaultUrl(),
    'topNavigation' => $topNavigation,
    'isAskQuestionsEnabled' => $faqConfig->get('main.enableAskQuestions'),
    'isOpenQuestionsEnabled' => $faqConfig->get('main.enableAskQuestions'),
    'footerNavigation' => $footerNavigation,
    'isPrivacyLinkEnabled' => $faqConfig->get('layout.enablePrivacyLink'),
    'urlPrivacyLink' => $faqConfig->get('main.privacyURL'),
    'msgPrivacyNote' => Translation::get(key: 'msgPrivacyNote'),
    'isCookieConsentEnabled' => $faqConfig->get('layout.enableCookieConsent'),
    'cookiePreferences' => Translation::get(key: 'cookiePreferences'),
    'currentYear' => date(
        format: 'Y',
        timestamp: time(),
    ),
];

//
// Show the login box or logged-in user information
//
if ($user->isLoggedIn() && $user->getUserId() > 0) {
    if (
        $user->perm->hasPermission($user->getUserId(), PermissionType::VIEW_ADMIN_LINK->value)
        || $user->isSuperAdmin()
    ) {
        $templateVars = [
            ...$templateVars,
            'msgAdmin' => Translation::get(key: 'adminSection'),
        ];
    }

    $templateVars = [
        ...$templateVars,
        'msgUserControlDropDown' => Translation::get(key: 'headerUserControlPanel'),
        'msgBookmarks' => Translation::get(key: 'msgBookmarks'),
        'msgUserRemoval' => Translation::get(key: 'ad_menu_RequestRemove'),
        'msgLogoutUser' => Translation::get(key: 'ad_menu_logout'),
        'csrfLogout' => $csrfLogoutToken,
    ];
}

if ('twofactor' === $action) {
    $includePhp = 'login.php';
}

//
// Handle 404 action with PageNotFoundController
//
if ('404' === $action) {
    $pageNotFoundController = new \phpMyFAQ\Controller\PageNotFoundController();
    $notFoundResponse = $pageNotFoundController->index($request);
    $notFoundResponse->send();
    exit;
}

//
// Include requested PHP file
//
require $includePhp;

if (!isset($twigTemplate)) {
    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
    $twigTemplate = $twig->loadTemplate('./startpage.twig');
}

//
// Check for 404 HTTP status code
//
if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
    $response->setStatusCode(Response::HTTP_NOT_FOUND);
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
    'last_modified' => new DateTime(),
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
