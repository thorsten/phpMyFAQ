<?php
/**
 * $Id: index.php,v 1.100 2007-03-29 12:09:36 thorstenr Exp $
 *
 * This is the main public frontend page of phpMyFAQ. It detects the browser's
 * language, gets and sets all cookie, post and get informations and includes
 * the templates we need and set all internal variables to the template
 * variables. That's all.
 *
 * @author       Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author       Lars Tiedemann <php@larstiedemann.de>
 * @since        2001-02-12
 * @copyright:   (c) 2001-2007 phpMyFAQ Team
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
 */

//
// Check if data.php exist -> if not, redirect to installer
//
if (!file_exists('inc/data.php')) {
    header("Location: install/installer.php");
    exit();
}

//
// Prepend and start the PHP session
//
require_once('inc/Init.php');
define('IS_VALID_PHPMYFAQ', null);
PMF_Init::cleanRequest();
session_name('pmf_auth_'.$faqconfig->get('phpMyFAQToken'));
session_start();

//
// Include required the link class, the template parser class, the captcha class, the category class,
// the main FAQ class, the glossary class and the IDNA class
//
require_once('inc/Link.php');
require_once('inc/Template.php');
require_once('inc/Captcha.php');
require_once('inc/Category.php');
require_once('inc/Faq.php');
require_once('inc/Glossary.php');
require_once('inc/Tags.php');
require_once('inc/libs/idna_convert.class.php');
$IDN = new idna_convert;

//
// get language (default: english)
//
$pmf = new PMF_Init();
$LANGCODE = $pmf->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('main.language'));
// Preload English strings
require_once ('lang/language_en.php');

if (isset($LANGCODE) && PMF_Init::isASupportedLanguage($LANGCODE)) {
    // Overwrite English strings with the ones we have in the current language
    require_once('lang/language_'.$LANGCODE.'.php');
} else {
    $LANGCODE = 'en';
}

//
// Authenticate current user
//
require_once ('inc/PMF_User/CurrentUser.php');
$auth = null;
$error = '';
if (isset($_POST['faqpassword']) and isset($_POST['faqusername'])) {
    // login with username and password
    $user = new PMF_CurrentUser();
    $faqusername = $db->escape_string($_POST['faqusername']);
    $faqpassword = $db->escape_string($_POST['faqpassword']);
    if ($user->login($faqusername, $faqpassword)) {
        // login, if user account is NOT blocked
        if ($user->getStatus() != 'blocked') {
            $auth = true;
        } else {
            $error = $PMF_LANG["ad_auth_fail"]." (".$faqusername." / *)";
        }
    } else {
        // error
        $error = sprintf('%s<br /><a href="admin/password.php" title="%s">%s</a>',
            $PMF_LANG['ad_auth_fail'], $PMF_LANG['lostPassword'], $PMF_LANG['lostPassword']);
        $user = null;
        unset($user);
    }
} else {
    // authenticate with session information
    $user = PMF_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));
    if ($user) {
        $auth = true;
    } else {
        $user = null;
        unset($user);
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
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'logout' && isset($auth)) {
    $user->deleteFromSession();
    $user = null;
    unset($user);
    $auth = null;
    unset($auth);
}

//
// Get current user and group id - default: -1
//
if (isset($user) && is_object($user)) {
    $current_user   = $user->getUserId();
    if (is_a($user->perm, 'PMF_PermMedium')) {
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
// use mbstring extension if available
//
$valid_mb_strings = array('ja', 'en');
if (function_exists('mb_language') && in_array($PMF_LANG['metaLanguage'], $valid_mb_strings)) {
    mb_language($PMF_LANG['metaLanguage']);
    mb_internal_encoding($PMF_LANG['metaCharset']);
}

//
// found a session ID in _GET or _COOKIE?
//
if ((!isset($_GET['sid'])) && (!isset($_COOKIE['pmf_sid']))) {
    // Create a per-site unique SID
    Tracking('new_session', 0);
    setcookie('pmf_sid', $sid, time() + 3600);
} else {
    if (isset($_COOKIE['pmf_sid']) && is_numeric($_COOKIE['pmf_sid'])) {
        CheckSID((int)$_COOKIE['pmf_sid'], $_SERVER['REMOTE_ADDR']);
    } else {
        CheckSID((int)$_GET['sid'], $_SERVER['REMOTE_ADDR']);
    }
}

//
// is user tracking activated?
//
$sids = '';
if ($faqconfig->get('tracking')) {
    if (isset($sid)) {
        if (!isset($_COOKIE['pmf_sid'])) {
            $sids = 'sid='.(int)$sid.'&amp;lang='.$LANGCODE.'&amp;';
        }
    } elseif (isset($_GET['sid']) || isset($_COOKIE['pmf_sid'])) {
        if (!isset($_COOKIE['pmf_sid'])) {
            if (is_numeric($_GET['sid'])) {
                $sids = 'sid='.(int)$_GET['sid'].'&amp;lang='.$LANGCODE.'&amp;';
            }
        }
    }
} else {
    if (!setcookie('pmf_lang', $LANGCODE, time()+3600)) {
        $sids = 'lang='.$LANGCODE.'&amp;';
    }
}

//
// Found a article language?
//
if (isset($_POST["artlang"]) && PMF_Init::isASupportedLanguage($_POST["artlang"]) ) {
    $lang = $_POST["artlang"];
} else {
    $lang = $LANGCODE;
}

//
// Create a new FAQ object
//
$faq = new PMF_Faq($db, $lang, $current_user, $current_groups);

//
// Create a new Category object
//
$category = new PMF_Category($LANGCODE, $current_user, $current_groups);

//
// Create a new Tags object
//
$oTag = new PMF_Tags($db, $LANGCODE);

//
// Found a record ID?
//
if (isset($_REQUEST["id"]) && is_numeric($_REQUEST["id"]) == true) {
    $id = (int)$_REQUEST["id"];
    $title = ' - '.$faq->getRecordTitle($id);
    $keywords = ' '.$faq->getRecordKeywords($id);
} else {
    $id = '';
    $title = ' -  powered by phpMyFAQ '.$faqconfig->get('version');
    $keywords = '';
}

//
// found a solution ID?
//
if (isset($_REQUEST['solution_id']) && is_numeric($_REQUEST['solution_id']) === true) {
    $solution_id = $_REQUEST['solution_id'];
    $title = ' -  powered by phpMyFAQ '.$faqconfig->get('version');
    $keywords = '';
    $a = $faq->getIdFromSolutionId($solution_id);
    if (is_array($a)) {
        $id = $a['id'];
        $lang = $a['lang'];
    }
}

//
// Found a category ID?
//
if (isset($_GET["cat"])) {
    $cat = $_GET["cat"];
} else {
    $cat = 0;
}
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
if (   isset($cat)
    && ($cat != 0)
    && ($id == '')
    && isset($category->categoryName[$cat]['name'])
    ) {
    $title = ' - '.$category->categoryName[$cat]['name'];
}

//
// Found an action request?
//
if (isset($_REQUEST["action"]) && is_string($_REQUEST["action"]) && !preg_match("=/=", $_REQUEST["action"]) && isset($allowedVariables[$_REQUEST["action"]])) {
    $action = trim($_REQUEST["action"]);
} else {
    $action = "main";
}

//
// Select the template for the requested page
//
if (isset($auth)) {
    $login_tpl = 'template/loggedin.tpl';
} else {
    $login_tpl = 'template/loginbox.tpl';
}
if ($action != 'main') {
    $inc_tpl = 'template/' . $action . '.tpl';
    $inc_php = $action.".php";
    $writeLangAdress = $_SERVER['PHP_SELF']."?".str_replace("&", "&amp;",$_SERVER["QUERY_STRING"]);
} else {
    if (isset($solution_id) && is_numeric($solution_id)) {
        // show the record with the solution ID
        $inc_tpl = 'template/artikel.tpl';
        $inc_php = 'artikel.php';
    } else {
        $inc_tpl = 'template/main.tpl';
        $inc_php = 'main.php';
    }
    $writeLangAdress = $_SERVER['PHP_SELF']."?".$sids;
}

//
// Set right column
//
// Check in any tags with at leat one entry exist
$hasTags = $oTag->existTagRelations();
if ($hasTags && (($action == 'artikel') || ($action == 'show'))) {
    $right_tpl = 'template/tagcloud.tpl';
} else {
    $right_tpl = 'template/startpage.tpl';
}

//
// Load template files and set template variables
//
$tpl = new PMF_Template (array(
    'index'                 => 'template/index.tpl',
    'loginBox'              => $login_tpl,
    'rightBox'              => $right_tpl,
    'writeContent'          => $inc_tpl));

$usersOnLine = getUsersOnline();
$totUsersOnLine = $usersOnLine[0] + $usersOnLine[1];
$main_template_vars = array(
    'title'             => $faqconfig->get('title').$title,
    'baseHref'          => PMF_Link::getSystemUri('index.php'),
    'version'           => $faqconfig->get('version'),
    'header'            => str_replace('"', '', $faqconfig->get('title')),
    'metaTitle'         => str_replace('"', '', $faqconfig->get('title')),
    'metaDescription'   => $faqconfig->get('main.metaDescription'),
    'metaKeywords'      => $faqconfig->get('main.metaKeywords').$keywords,
    'metaPublisher'     => $faqconfig->get('main.metaPublisher'),
    'metaLanguage'      => $PMF_LANG['metaLanguage'],
    'metaCharset'       => $PMF_LANG['metaCharset'],
    'action'            => $action,
    'dir'               => $PMF_LANG['dir'],
    'msgCategory'       => $PMF_LANG['msgCategory'],
    'showCategories'    => $category->printCategories($cat),
    'searchBox'         => $PMF_LANG['msgSearch'],
    'languageBox'       => $PMF_LANG['msgLangaugeSubmit'],
    'writeLangAdress'   => $writeLangAdress,
    'switchLanguages'   => selectLanguages($LANGCODE),
    'userOnline'        => $totUsersOnLine.$PMF_LANG['msgUserOnline'].
                                sprintf($PMF_LANG['msgUsersOnline'],
                                $usersOnLine[0],
                                $usersOnLine[1]),
    'copyright'         => 'powered by <a href="http://www.phpmyfaq.de" target="_blank">phpMyFAQ</a> '.$faqconfig->get('version'));

if ($faqconfig->get('mod_rewrite')) {
    $links_template_vars = array(
        "faqHome"               => $_SERVER['PHP_SELF'],
        "msgSearch"             => '<a href="'.PMF_Link::getSystemRelativeUri('index.php').'search.html">'.$PMF_LANG["msgAdvancedSearch"].'</a>',
        'msgAddContent'         => '<a href="'.PMF_Link::getSystemRelativeUri('index.php').'addcontent.html">'.$PMF_LANG["msgAddContent"].'</a>',
        "msgQuestion"           => '<a href="'.PMF_Link::getSystemRelativeUri('index.php').'ask.html">'.$PMF_LANG["msgQuestion"].'</a>',
        "msgOpenQuestions"      => '<a href="'.PMF_Link::getSystemRelativeUri('index.php').'open.html">'.$PMF_LANG["msgOpenQuestions"].'</a>',
        'msgHelp'               => '<a href="'.PMF_Link::getSystemRelativeUri('index.php').'help.html">'.$PMF_LANG["msgHelp"].'</a>',
        "msgContact"            => '<a href="'.PMF_Link::getSystemRelativeUri('index.php').'contact.html">'.$PMF_LANG["msgContact"].'</a>',
        "backToHome"            => '<a href="'.PMF_Link::getSystemRelativeUri('index.php').'index.html">'.$PMF_LANG["msgHome"].'</a>',
        "allCategories"         => '<a href="'.PMF_Link::getSystemRelativeUri('index.php').'showcat.html">'.$PMF_LANG["msgShowAllCategories"].'</a>',
        "writeSendAdress"       => PMF_Link::getSystemRelativeUri('index.php').'search.html',
        'showInstantResponse'   => '<a href="'.PMF_Link::getSystemRelativeUri('index.php').'instantresponse.html">'.$PMF_LANG['msgInstantResponse'].'</a>',
        'showSitemap'           => getLinkHtmlAnchor($_SERVER['PHP_SELF'].'?'.$sids.'action=sitemap&amp;lang='.$LANGCODE, $PMF_LANG['msgSitemap']),
        'opensearch'            => PMF_Link::getSystemRelativeUri('index.php').'search.html'
        );
} else {
    $links_template_vars = array(
        "faqHome"               => $_SERVER['PHP_SELF'],
        "msgSearch"             => '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'action=search">'.$PMF_LANG["msgAdvancedSearch"].'</a>',
        "msgAddContent"         => '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'action=add">'.$PMF_LANG["msgAddContent"].'</a>',
        "msgQuestion"           => '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'action=ask">'.$PMF_LANG["msgQuestion"].'</a>',
        "msgOpenQuestions"      => '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'action=open">'.$PMF_LANG["msgOpenQuestions"].'</a>',
        "msgHelp"               => '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'action=help">'.$PMF_LANG["msgHelp"].'</a>',
        "msgContact"            => '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'action=contact">'.$PMF_LANG["msgContact"].'</a>',
        "allCategories"         => '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'action=show">'.$PMF_LANG["msgShowAllCategories"].'</a>',
        "backToHome"            => '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'">'.$PMF_LANG["msgHome"].'</a>',
        "writeSendAdress"       => $_SERVER['PHP_SELF'].'?'.$sids.'action=search',
        'showInstantResponse'   => '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'action=instantresponse">'.$PMF_LANG['msgInstantResponse'].'</a>',
        'showSitemap'           => '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'action=sitemap&amp;lang='.$LANGCODE.'">'.$PMF_LANG['msgSitemap'].'</a>',
        'opensearch'            => $_SERVER['PHP_SELF'].'?'.$sids.'action=search',
        );
}

if (DEBUG) {
    $cookies = '';
    foreach($_COOKIE as $key => $value) {
        $cookies .= $key.': '.$value.'<br />';
    }
    $debug_template_vars = array(
        'debugMessages' => '<div id="debug_main">DEBUG INFORMATION:<br />'.$db->sqllog().'</div><div id="debug_cookies">COOKIES:<br />'.$cookies.'</div>');
} else {
    // send headers and print template
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Content-type: text/html; charset=".$PMF_LANG['metaCharset']);
    header("Vary: Negotiate,Accept");
    $debug_template_vars = array('debugMessages' => '');
}

//
// Get main template, set main variables
//
$tpl->processTemplate(
    'index',
    array_merge(
        $main_template_vars,
        $links_template_vars,
        $debug_template_vars));

//
// Show login box or logged-in user information
//
if (isset($auth)) {
    $tpl->processTemplate('loginBox', array(
        'loggedinas'        => $PMF_LANG['ad_user_loggedin'],
        'currentuser'       => $user->getUserData('display_name'),
        'printAdminPath'    => 'admin/index.php',
        'adminSection'      => $PMF_LANG['adminSection'],
        'printLogoutPath'   => $_SERVER['PHP_SELF'].'?action=logout',
        'logout'            => $PMF_LANG['ad_menu_logout']));
} else {
    $tpl->processTemplate('loginBox', array(
        'writeLoginPath'    => $_SERVER['PHP_SELF'].'?action=login',
        'login'             => $PMF_LANG['ad_auth_ok'],
        'username'          => $PMF_LANG['ad_auth_user'],
        'password'          => $PMF_LANG['ad_auth_passwd'],
        'msgLoginFailed'    => $error));
}
$tpl->includeTemplate('loginBox', 'index');

$tpl->processTemplate('rightBox', array(
    'writeTopTenHeader'     => $PMF_LANG['msgTopTen'],
    'writeTopTenRow'        => $faq->getTopTen(),
    'writeNewestHeader'     => $PMF_LANG['msgLatestArticles'],
    'writeNewestRow'        => $faq->getLatest(),
    'writeTagCloudHeader'   => $PMF_LANG['msg_tags'],
    'writeTags'             => $oTag->printHTMLTagsCloud()));
$tpl->includeTemplate('rightBox', 'index');

//
// Include requested PHP file
//
require_once($inc_php);
if ('xml' != $action) {
    $tpl->printTemplate();
}

//
// Disconnect from database
//
$db->dbclose();
