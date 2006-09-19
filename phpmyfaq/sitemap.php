<?php
/**
* $Id: sitemap.php,v 1.12 2006-09-19 21:28:33 matteo Exp $
*
* Shows the whole FAQ articles
*
* @author       Thomas Zeithaml <seo@annatom.de>
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2005-08-21
* @copyright    (c) 2001-2006 phpMyFAQ Team
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

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST]'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

Tracking('sitemap', 0);

if (isset($_REQUEST['letter']) && (is_string($_REQUEST['letter']))  && (1 == strlen($_REQUEST['letter']))) {
    $currentLetter = strtoupper($db->escape_string($_REQUEST['letter']));
} else {
    $currentLetter = 'A';
}

switch($DB["type"] ) {
    case 'db2':
        // Queries for IBM DB2
        $query_1 = "SELECT DISTINCT substr(thema, 1, 1) AS letters FROM ".SQLPREFIX."faqdata WHERE lang = '".$lang."' AND active = 'yes' ORDER BY letters";
        $query_2 = "SELECT a.thema AS thema, a.id AS id, a.lang AS lang, b.category_id AS category_id, a.content AS snap FROM ".SQLPREFIX."faqdata a, ".SQLPREFIX."faqcategoryrelations b WHERE a.id = b.record_id AND substr(thema, 1, 1) = '".$currentLetter."' AND lang = '".$lang."' AND active = 'yes'";
        break;
    case 'sqlite':
        // Queries for SQLite
        $query_1 = "SELECT DISTINCT substr(thema, 1, 1) AS letters FROM ".SQLPREFIX."faqdata WHERE lang = '".$lang."' AND active = 'yes' ORDER BY letters";
        $query_2 = "SELECT a.thema AS thema, a.id AS id, a.lang AS lang, b.category_id AS category_id, a.content AS snap FROM ".SQLPREFIX."faqdata a, ".SQLPREFIX."faqcategoryrelations b WHERE a.id = b.record_id AND substr(thema, 1, 1) = '".$currentLetter."' AND lang = '".$lang."' AND active = 'yes'";
        break;
    default:
        // Queries for all other databases
        $query_1 = "SELECT DISTINCT substring(thema, 1, 1) AS letters FROM ".SQLPREFIX."faqdata WHERE lang = '".$lang."' AND active = 'yes' ORDER BY letters";
        $query_2 = "SELECT a.thema AS thema, a.id AS id, a.lang AS lang, b.category_id AS category_id, a.content AS snap FROM ".SQLPREFIX."faqdata a, ".SQLPREFIX."faqcategoryrelations b WHERE a.id = b.record_id AND substring(thema, 1, 1) = '".$currentLetter."' AND lang = '".$lang."' AND active = 'yes'";
        break;
}

$writeLetters = '<p>';
$result = $db->query($query_1);
while ($row = $db->fetch_object($result)) {
    $letters = strtoupper($row->letters);
    if (preg_match("/^[a-z0-9]/i", $letters)) {
        $url = sprintf('%saction=sitemap&amp;letter=%s&amp;lang=%s',
                    $sids,
                    $letters,
                    $lang
                );
        $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
        $oLink->text = $letters;
        $writeLetters .= $oLink->toHtmlAnchor().' ';
    }
}
$writeLetters .= '</p>';

$writeMap = '<ul>';
$result = $db->query($query_2);
$oldId = 0;
while ($row = $db->fetch_object($result)) {
    if ($oldId != $row->id) {
        $title = PMF_htmlentities($row->thema, ENT_NOQUOTES, $PMF_LANG['metaCharset']);
        $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );
        $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
        $oLink->itemTitle = $row->thema;
        $oLink->text = $title;
        $oLink->tooltip = $title;
        $writeMap .= '<li>'.$oLink->toHtmlAnchor().'<br />'."\n";

        $writeMap .= chopString(strip_tags($row->snap), 25). " ...</li>\n";
    }
    $oldId = $row->id;
}
$writeMap .= '</ul>';

$tpl->processTemplate ('writeContent', array(
                       'writeLetters' => $writeLetters,
                       'writeMap' => $writeMap,
                       'writeCuttentLetter' => $currentLetter
                        ));

$tpl->includeTemplate('writeContent', 'index');
