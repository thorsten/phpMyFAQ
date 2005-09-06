<?php
/**
* $Id: sitemap.php,v 1.3 2005-09-06 06:20:09 thorstenr Exp $
*
* Shows the whole FAQ articles
*
* @author       Thomas Zeithaml <seo@annatom.de>
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2005-08-21
* @copyright    (c) 2001-2005 phpMyFAQ Team
* 
* The contents of this file are subject to the Mozilla Public License
* Version 1.1 (the 'License'); you may not use this file except in
* compliance with the License. You may obtain a copy of the License at
* http://www.mozilla.org/MPL/
* 
* Software distributed under the License is distributed on an 'AS IS'
* basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
* License for the specific language governing rights and limitations
* under the License.
*/

Tracking('sitemap', 0);

if (isset($_REQUEST['letter']) ) {
	$currentLetter = $_REQUEST['letter'];
} else {
    $currentLetter = 'A';
}

$writeLetters = '<p>';
$result = $db->query("SELECT DISTINCT substring(thema, 1, 1) AS letters FROM ".SQLPREFIX."faqdata WHERE lang = '".$lang."' AND active = 'yes' ORDER BY letters");
while ($row = $db->fetch_object($result)) {
	$letters = stripslashes($row->letters);
	if (isset($PMF_CONF["mod_rewrite"]) && $PMF_CONF["mod_rewrite"] == "TRUE") {
        $writeLetters .= '<a href="sitemap-'.$letters.'.html">'.$letters.'</a> ';
	} else {
	    $writeLetters .= '<a href="'.$_SERVER["PHP_SELF"].'?'.$sids.'action=sitemap&amp;letter='.$letters.'">'.$letters.'</a> ';
	}
}
$writeLetters .= '</p>';

$writeMap = '<ul>';
$result = $db->query('SELECT a.thema, a.id, a.lang, b.category_id, a.content AS snap FROM '.SQLPREFIX.'faqdata a, '.SQLPREFIX.'faqcategoryrelations b WHERE a.id = b.record_id AND substring(thema, 1, 1) = "'.$currentLetter.'" AND lang = "'.$lang.'" AND active = "yes"');

while ($row = $db->fetch_object($result)) {
	$writeMap .= '<li><a href="'. stripslashes($row->thema).'-'.$row->category_id.'_'.$row->id.'_'.$row->lang.'.html">'. stripslashes($row->thema) ."</a><br />\n";
	$writeMap .= stripslashes(chopString(strip_tags($row->snap), 25)). "</li>\n";
}
$writeMap .= '</ul>';

$tpl->processTemplate ('writeContent', array(
				       'writeLetters' => $writeLetters,
                       'writeMap' => $writeMap,
					   'writeCuttentLetter' => $currentLetter
						));

$tpl->includeTemplate('writeContent', 'index');