<?php
/**
* $Id: pdf.php,v 1.11 2004-11-30 21:41:59 thorstenr Exp $
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Peter Beauvain <pbeauvain@web.de>
* @author       Olivier Plathey <olivier@fpdf.org>
* @author       Krzysztof Kruszynski <thywolf@wolf.homelinux.net>
* @since        2003-02-12
* @copyright    (c) 2001-2004 phpMyFAQ Team
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

require_once ("inc/data.php");
require_once ("inc/db.php");
require_once ("inc/functions.php");
require_once ("inc/config.php");
require_once ("inc/category.php");
require_once ("inc/pdf.php");

define("SQLPREFIX", $DB["prefix"]);
$db = new db($DB["type"]);
$db->connect($DB["server"], $DB["user"], $DB["password"], $DB["db"]);
$tree = new Category;

if (isset($_GET["lang"]) && $_GET["lang"] != "" && strlen($_GET["lang"]) <= 2 && !preg_match("=/=", $_GET["lang"])) {
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

if (isset($_GET['cat']) && is_numeric($_GET['cat']) == TRUE) {
	$currentCategory = $_REQUEST['cat'];
}
if (isset($_GET["id"]) && is_numeric($_GET["id"]) == TRUE) {
	$id = $_GET["id"];
	}
if (isset($_GET["lang"]) && strlen($_GET["lang"]) <= 2 && !preg_match("=/=", $_GET["lang"])) {
    $lang = $_GET["lang"];
    }

$result = $db->query("SELECT id, lang, thema, content, datum, author FROM ".SQLPREFIX."faqdata WHERE id = '".$id."' AND lang = '".$lang."' AND active = 'yes'");
if ($db->num_rows($result) > 0) {
	while ($row = $db->fetch_object($result)) {
		$lang = $row->lang;
		$thema = $row->thema;
		$content = $row->content;
		$date = $row->datum;
		$author = $row->author;
	}
} else {
	print "Error!";
}

$pdf = new PDF($currentCategory, $thema, $tree->categoryName, $orientation = "P", $unit = "mm", $format = "A4");
$pdf->Open();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont("Arial", "", 12);
$pdf->SetDisplayMode("real");
$pdf->WriteHTML(str_replace("../", "", stripslashes($content)));
$pdf->Ln();
$pdf->Ln();
$pdf->Write(5,unhtmlentities($PMF_LANG["msgAuthor"]).$author);
$pdf->Ln();
$pdf->Write(5,unhtmlentities($PMF_LANG["msgLastUpdateArticle"]).makeDate($date));

$pdfFile = "pdf/".$id.".pdf";
$pdf->Output($pdfFile);
$pdf->close($pdfFile);

$file = basename($pdfFile);
$size = filesize($pdfFile);
session_cache_limiter('private'); 
header("Pragma: public");
header("Expires: 0"); // set expiration time
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

if (preg_match("/MSIE/i", $_SERVER["HTTP_USER_AGENT"])) {
    header("Content-type: application/pdf");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".filesize($pdfFile));
    header("Content-Disposition: Attachment; filename=".$id.".pdf" );  
    readfile($pdfFile);
} else {
    header("Location: ".$pdfFile."");
    header("Content-Type: application/pdf");
    header("Content-Length: ".filesize($pdfFile));
    readfile($pdfFile);
}