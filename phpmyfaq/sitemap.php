<?php
/**
* $Id: sitemap.php,v 1.2 2005-09-05 18:54:24 thorstenr Exp $
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

if (isset($_REQUEST['letter']) ) {
	$currentLetter = $_REQUEST['letter'];
} else {
    $currentLetter = 'A';
}

Tracking("sitemap", 0);

$sql = 'SELECT a.thema, a.id, a.lang, b.category_id, substring( a.content, 1, 40 ) as snap FROM '.SQLPREFIX.'faqdata a, '.SQLPREFIX.'faqcategoryrelations b WHERE a.id = b.record_id AND substring( thema, 1, 1 ) = "'.$currentLetter.'" AND lang = "'.$lang.'" AND active = "yes"';
$result = $db->query($sql);

while ($row = $db->fetch_object($result)) {
	$writeMap .= '<a href="'. stripslashes($row->thema).'-'.$row->category_id.'_'.$row->id.'_'.$row->lang.'.html">'. stripslashes($row->thema) ."</a><br />\n";
	$writeMap .= stripslashes($row->snap). "<br />\n<br />\n";
}

$result = $db->query('SELECT DISTINCT substring( thema, 1, 1 ) as letters FROM '.SQLPREFIX.'faqdata WHERE lang = \''.$lang.'\' AND active = \'yes\' order by letters');
while ($row = $db->fetch_object($result)) {
	$letters = stripslashes($row->letters);
	$writeLetters .= '<a href="sitemap-'.$letters.'.html">'.$letters.'</a> ';
}

$tpl->processTemplate ('writeContent', array(
				       "writeLetters" => $writeLetters,
                       'writeMap' => $writeMap,
					   'writeCuttentLetter' => $currentLetter
						));

$tpl->includeTemplate('writeContent', 'index');
?>
