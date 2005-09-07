<?php
/**
* $Id: index.php,v 1.28 2005-09-07 08:53:05 thorstenr Exp $
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

/* debug mode:
 * - FALSE	debug mode disabled
 * - TRUE	debug mode enabled
 */
define("DEBUG", FALSE);

if (DEBUG) {
	error_reporting(E_ALL);
}

// Just for security reasons - thanks to Johannes for the hint
$_SERVER['PHP_SELF'] = str_replace('%2F', '/', rawurlencode($_SERVER['PHP_SELF']));
if (isset($GLOBALS['PHP_SELF'])) {
    $GLOBALS['PHP_SELF'] = str_replace('%2F', '/', rawurlencode($GLOBALS['PHP_SELF']));
}

// check if config.php and data.php exist -> if not, redirect to installer
if (!file_exists('inc/config.php') || !file_exists('inc/data.php')) {
    header("Location: http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/install/installer.php");
    exit();
}

// connect to the database server, define the prefix and connect
require_once("inc/data.php");
require_once("inc/db.php");
define("SQLPREFIX", $DB["prefix"]);
$db = db::db_select($DB["type"]);
$db->connect($DB["server"], $DB["user"], $DB["password"], $DB["db"]);

// get configuration, constants, main functions, template parser, category class, IDNA class
require_once("inc/config.php");
require_once("inc/constants.php");
require_once("inc/functions.php");
require_once("inc/parser.php");
require_once("inc/category.php");
require_once("inc/idna_convert.class.php");
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
// TODO: write a global function for that?
if (isset($_POST["language"]) && $_POST["language"] != "" && strlen($_POST["language"]) <= 2 && !preg_match("=/=", $_REQUEST["language"])) {
    $LANGCODE = $_POST["language"];
    require_once("lang/language_".$_POST["language"].".php");
    @setcookie("lang", $LANGCODE, time()+3600);
}

if (!isset($LANGCODE) && isset($_GET["lang"]) && $_GET["lang"] != "" && strlen($_GET["lang"]) <= 2 && !preg_match("=/=", $_GET["lang"])) {
    if (@is_file("lang/language_".$_REQUEST["lang"].".php")) {
        require_once("lang/language_".$_REQUEST["lang"].".php");
        $LANGCODE = $_REQUEST["lang"];
    } else {
        unset($LANGCODE);
    }
}

if (!isset($LANGCODE) && isset($_COOKIE["lang"]) && $_COOKIE["lang"] != "" && strlen($_COOKIE["lang"]) <= 2 && !preg_match("=/=", $_COOKIE["lang"])) {
    if (@is_file("lang/language_".$_COOKIE["lang"].".php")) {
        require_once("lang/language_".$_COOKIE["lang"].".php");
        $LANGCODE = $_COOKIE["lang"];
    } else {
        unset($LANGCODE);
    }
}

if (!isset($LANGCODE) && isset($PMF_CONF["detection"]) && isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
    if (@is_file("lang/language_".substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2).".php")) {
        require_once("lang/language_".substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2).".php");
        $LANGCODE = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2);
        @setcookie("lang", $LANGCODE, time()+3600);
    } else {
        unset($LANGCODE);
    }
} elseif (!isset($PMF_CONF["detection"])) {
    if (@require_once("lang/".$PMF_CONF["language"])) {
        $LANGCODE = $PMF_LANG["metaLanguage"];
        @setcookie("lang", $LANGCODE, time()+3600);
    } else {
        unset($LANGCODE);
    }
}

if (isset($LANGCODE)) {
    require_once("lang/language_".$LANGCODE.".php");
} else {
    $LANGCODE = "en";
    require_once ("lang/language_en.php");
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

// found a article language?
if (isset($_GET["artlang"]) && strlen($_GET["artlang"]) <= 2 && !preg_match("=/=", $_GET["artlang"])) {
	$lang = $_GET["artlang"];
} else {
	$lang = $LANGCODE;
}

// found a record ID?
if (isset($_REQUEST["id"]) && is_numeric($_REQUEST["id"]) == TRUE) {
	$id = $_REQUEST["id"];
    $title = ' - '.stripslashes(getThema($id, $lang));
} else {
	$id = '';
    $title = ' -  powered by phpMyFAQ '.$PMF_CONF['version'];
}

// found a category?
if (isset($_GET["cat"])) {
    $cat = $_GET["cat"];
} else {
    $cat = 0;
}
$tree = new Category($LANGCODE);
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

/* select the template for the requested page */
if ($action != "main") {
    $inc_tpl = "template/".trim($action).".tpl";
    $inc_php = $action.".php";
    $writeLangAdress = $_SERVER["PHP_SELF"]."?".str_replace("&", "&amp;",$_SERVER["QUERY_STRING"]);
} else {
    $inc_tpl = "template/main.tpl";
    $inc_php = "main.php";
    $writeLangAdress = $_SERVER["PHP_SELF"]."?".$sids;
}

// load templates
$tpl = new phpmyfaqTemplate (array(
				"index" => 'template/index.tpl',
				"writeContent" => $inc_tpl));

$main_template_vars = array(
                "title" => $PMF_CONF["title"].$title,
                "header" => $PMF_CONF["title"],
				"metaDescription" => $PMF_CONF["metaDescription"],
				"metaKeywords" => $PMF_CONF["metaKeywords"],
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
                'writeTopTenRow' => generateTopTen($LANGCODE),
                'writeNewestHeader' => $PMF_LANG['msgLatestArticles'],
                'writeNewestRow' => generateFiveNewest($LANGCODE),
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

// get main template, set main variables
$tpl->processTemplate ("index", array_merge($main_template_vars, $links_template_vars));

// include requested PHP file
require_once($inc_php);

if (DEBUG) {
	print "<p>DEBUG INFORMATION:</p>\n";
    print "<p>".$db->sqllog()."</p>\n";
} else {
    // send headers and print template
    @header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    @header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
    @header("Cache-Control: no-store, no-cache, must-revalidate");
    @header("Cache-Control: post-check=0, pre-check=0", false);
    @header("Pragma: no-cache");
    @header("Content-type: text/html; charset=".$PMF_LANG["metaCharset"]);
    @header("Vary: Negotiate,Accept");
}

if ('xml' != $action) {
    $tpl->printTemplate();
}

// disconnect from database
$db->dbclose();
?>