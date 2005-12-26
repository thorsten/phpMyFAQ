<?php
/**
* $Id: index.php,v 1.36 2005-12-26 18:41:25 b33blebr0x Exp $
*
* This is the main public frontend page of phpMyFAQ. It detects the browser's
* language, gets all cookie, post and get informations and includes the 
* templates we need and set all internal variables to the template variables.
* That's all.
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2001-02-12
* @copyright:   (c) 2001-2005 phpMyFAQ Team
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

// check if config.php and data.php exist -> if not, redirect to installer
if (!file_exists('inc/config.php') || !file_exists('inc/data.php')) {
    header("Location: http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF'])."/install/installer.php");
    exit();
}

require_once('inc/init.php');
define('IS_VALID_PHPMYFAQ', null);
PMF_Init::cleanRequest();

// Just for security reasons - thanks to Johannes for the hint
$_SERVER['PHP_SELF'] = strtr(rawurlencode($_SERVER['PHP_SELF']),array( "%2F"=>"/", "%257E"=>"%7E"));
$_SERVER['HTTP_USER_AGENT'] = urlencode($_SERVER['HTTP_USER_AGENT']);

// Include required template parser class, category class, the main FAQ class
// and the IDNA class
require_once('inc/parser.php');
require_once('inc/Category.php');
require_once('inc/Faq.php');
require_once('inc/idna_convert.class.php');
$IDN = new idna_convert;

// connect to LDAP server, when LDAP support is enabled
if (isset($PMF_CONF["ldap_support"]) && $PMF_CONF["ldap_support"] == true && file_exists('inc/dataldap.php')) {
    require_once('inc/dataldap.php');
    require_once('inc/ldap.php');
    $ldap = new LDAP($PMF_LDAP['ldap_server'], $PMF_LDAP['ldap_port'], $PMF_LDAP['ldap_base'], $PMF_LDAP['ldap_user'], $PMF_LDAP['ldap_password']);
} else {
    $ldap = null;
}

// get language (default: english)
$pmf = new PMF_Init();
$LANGCODE = $pmf->setLanguage((isset($PMF_CONF['detection']) ? true : false), $PMF_CONF['language']);

if (isset($LANGCODE) && isset($languageCodes[strtoupper($LANGCODE)])) {
    require_once("lang/language_".$LANGCODE.".php");
} else {
    $LANGCODE = "en";
    require_once ("lang/language_en.php");
}

// use mbstring extension if available
$valid_mb_strings = array('ja', 'en');
if (function_exists('mb_language') && in_array($PMF_LANG['metaLanguage'], $valid_mb_strings)) {
    mb_language($PMF_LANG['metaLanguage']);
    mb_internal_encoding($PMF_LANG['metaCharset']);
}

// found a session ID?
if (!isset($_GET["sid"]) && !isset($_COOKIE["sid"])) {
	Tracking("new_session", 0);
    setcookie("sid", $sid, time()+3600);
} else {
	if (isset($_REQUEST["sid"]) && is_numeric($_REQUEST["sid"]) == TRUE) {
		CheckSID($_REQUEST["sid"], getenv("REMOTE_ADDR"));
	}
}

// is user tracking activated?
if (isset($PMF_CONF["tracking"])) {
	if (isset($sid)) {
        if (!isset($_COOKIE["sid"])) {
            $sids = "sid=".$sid."&amp;lang=".$LANGCODE."&amp;";
        } else {
            $sids = "";
        }
	} elseif (isset($_REQUEST["sid"])) {
        if (!isset($_COOKIE["sid"])) {
            $sids = "sid=".$_REQUEST["sid"]."&amp;lang=".$LANGCODE."&amp;";
        } else {
            $sids = "";
        }
	}
} else {
    if (!setcookie("lang", $LANGCODE, time()+3600)) {
        $sids = "lang=".$LANGCODE."&amp;";
    } else {
        $sids = "";
    }
}

// create a new FAQ object
$faq = new FAQ($db, $LANGCODE);

// found a article language?
if (isset($_GET["artlang"]) && strlen($_GET["artlang"]) <= 2 && !preg_match("=/=", $_GET["artlang"])) {
	$lang = $_GET["artlang"];
} else {
	$lang = $LANGCODE;
}

// found a record ID?
if (isset($_REQUEST["id"]) && is_numeric($_REQUEST["id"]) == true) {
	$id = (int)$_REQUEST["id"];
    $title = ' - '.stripslashes($faq->getRecordTitle($id, $lang));
    $keywords = ' '.stripslashes($faq->getRecordKeywords($id, $lang));
} else {
	$id = '';
    $title = ' -  powered by phpMyFAQ '.$PMF_CONF['version'];
    $keywords = '';
}

// found a category?
if (isset($_GET["cat"])) {
    $cat = $_GET["cat"];
} else {
    $cat = 0;
}
$tree = new Category($LANGCODE);
$cat_from_id = -1;
if (is_numeric($id) && $id > 0) {
    $cat_from_id = $tree->getCategoryIdFromArticle($id);
}
if ($cat_from_id != -1 && $cat == 0) {
    $cat = $cat_from_id;
}
$tree->transform(0);
$tree->collapseAll();
if ($cat != 0) {
    $tree->expandTo($cat);
}
if (isset($cat) && $cat != 0 && $id == '') {
    $title = ' - '.$tree->categoryName[$cat]['name'];
}

// found an action request?
if (isset($_REQUEST["action"]) && !preg_match("=/=", $_REQUEST["action"]) && isset($allowedVariables[$_REQUEST["action"]])) {
	$action = $_REQUEST["action"];
} else {
	$action = "main";
}

// select the template for the requested page
if ($action != "main") {
    $inc_tpl = "template/".trim($action).".tpl";
    $inc_php = $action.".php";
    $writeLangAdress = '?'.str_replace("&", "&amp;",$_SERVER["QUERY_STRING"]);
} else {
    $inc_tpl = "template/main.tpl";
    $inc_php = "main.php";
    $writeLangAdress = '?'.$sids;
}

// load templates
$tpl = new phpmyfaqTemplate (array(
				"index" => 'template/index.tpl',
				"writeContent" => $inc_tpl));

$main_template_vars = array(
                "title" => $PMF_CONF["title"].$title,
                "header" => $PMF_CONF["title"],
				"metaDescription" => $PMF_CONF["metaDescription"],
				'metaKeywords' => $PMF_CONF['metaKeywords'].$keywords,
				"metaPublisher" => $PMF_CONF["metaPublisher"],
				"metaLanguage" => $PMF_LANG["metaLanguage"],
				"metaCharset" => $PMF_LANG["metaCharset"],
                "dir" => $PMF_LANG["dir"],
				"msgCategory" => $PMF_LANG["msgCategory"],
				"showCategories" => $tree->printCategories($cat),
                "searchBox" => $PMF_LANG["msgSearch"],
                "languageBox" => $PMF_LANG["msgLangaugeSubmit"],
                "writeLangAdress" => $writeLangAdress,
                "switchLanguages" => selectLanguages($LANGCODE),
				"userOnline" => userOnline().$PMF_LANG["msgUserOnline"],
                'writeTopTenHeader' => $PMF_LANG['msgTopTen'],
                'writeTopTenRow' => $faq->getTopTen(),
                'writeNewestHeader' => $PMF_LANG['msgLatestArticles'],
                'writeNewestRow' => $faq->getFiveLatest(),
				"copyright" => 'powered by <a href="http://www.phpmyfaq.de" target="_blank">phpMyFAQ</a> '.$PMF_CONF["version"]);

if (isset($PMF_CONF["mod_rewrite"]) && $PMF_CONF["mod_rewrite"] == "TRUE") {
    $links_template_vars = array(
                "faqHome" => '',
                "msgSearch" => '<a href="search.html">'.$PMF_LANG["msgSearch"].'</a>',
				"msgAddContent" => '<a href="addcontent.html">'.$PMF_LANG["msgAddContent"].'</a>',
				"msgQuestion" => '<a href="ask.html">'.$PMF_LANG["msgQuestion"].'</a>',
				"msgOpenQuestions" => '<a href="open.html">'.$PMF_LANG["msgOpenQuestions"].'</a>',
				"msgHelp" => '<a href="help.html">'.$PMF_LANG["msgHelp"].'</a>',
				"msgContact" => '<a href="contact.html">'.$PMF_LANG["msgContact"].'</a>',
				"backToHome" => '<a href="index.html">'.$PMF_LANG["msgHome"].'</a>',
                "allCategories" => '<a href="showcat.html">'.$PMF_LANG["msgShowAllCategories"].'</a>',
				"writeSendAdress" => 'search.html',
                'showSitemap' => '<a href="sitemap-a.html">'.$PMF_LANG['msgSitemap'].'</a>');
} else {
    $links_template_vars = array(
                "faqHome" => $_SERVER['PHP_SELF'],
				"msgSearch" => '<a href="'.$_SERVER["PHP_SELF"].'?'.$sids.'action=search">'.$PMF_LANG["msgSearch"].'</a>',
				"msgAddContent" => '<a href="'.$_SERVER["PHP_SELF"].'?'.$sids.'action=add">'.$PMF_LANG["msgAddContent"].'</a>',
				"msgQuestion" => '<a href="'.$_SERVER["PHP_SELF"].'?'.$sids.'action=ask">'.$PMF_LANG["msgQuestion"].'</a>',
				"msgOpenQuestions" => '<a href="'.$_SERVER["PHP_SELF"].'?'.$sids.'action=open">'.$PMF_LANG["msgOpenQuestions"].'</a>',
				"msgHelp" => '<a href="'.$_SERVER["PHP_SELF"].'?'.$sids.'action=help">'.$PMF_LANG["msgHelp"].'</a>',
				"msgContact" => '<a href="'.$_SERVER["PHP_SELF"].'?'.$sids.'action=contact">'.$PMF_LANG["msgContact"].'</a>',
                "allCategories" => '<a href="'.$_SERVER["PHP_SELF"].'?'.$sids.'action=show">'.$PMF_LANG["msgShowAllCategories"].'</a>',
				"backToHome" => '<a href="'.$_SERVER["PHP_SELF"].'?'.$sids.'">'.$PMF_LANG["msgHome"].'</a>',
				"writeSendAdress" => $_SERVER["PHP_SELF"]."?".$sids."action=search",
                'showSitemap' => '<a href="'.$_SERVER["PHP_SELF"].'?'.$sids.'action=sitemap">'.$PMF_LANG['msgSitemap'].'</a>');
}

if (DEBUG) {
    $debug_template_vars = array('debugMessages' => '<p>DEBUG INFORMATION:<br />'.$db->sqllog().'</p>');
} else {
    // send headers and print template
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Content-type: text/html; charset=".$PMF_LANG["metaCharset"]);
    header("Vary: Negotiate,Accept");
    $debug_template_vars = array('debugMessages' => '');
}

// get main template, set main variables
$tpl->processTemplate ("index", array_merge($main_template_vars, $links_template_vars, $debug_template_vars));

// include requested PHP file
require_once($inc_php);

if ('xml' != $action) {
    $tpl->printTemplate();
}

// disconnect from database
$db->dbclose();
