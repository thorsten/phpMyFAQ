<?php
/**
* $Id: export.main.php,v 1.14 2004-12-25 20:26:23 thorstenr Exp $
*
* XML, XML DocBook, XHTML and PDF export - main page
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Peter Beauvain <pbeauvain@web.de>
* @since        2003-04-17
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
?>
	<h2><?php print $PMF_LANG["ad_menu_export"]; ?></h2>
<?php
if (isset($_REQUEST["submit"])) {
	$submit = $_REQUEST["submit"];
}

if (isset($submit[0])) {
    generateXMLFile();
}

if (isset($submit[1])) {
    generateXHTMLFile();
}

if (isset($submit[2])) {
	// Full PDF Export
    
	require (PMF_ROOT_DIR."/inc/pdf.php");
	$tree = new Category();
	$arrRubrik = array();
	$arrThema = array();
	$arrContent = array();
	
	$result = $db->query('SELECT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.thema, '.SQLPREFIX.'faqdata.content, '.SQLPREFIX.'faqdata.author, '.SQLPREFIX.'faqdata.datum FROM '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang ORDER BY '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.id');
	if ($db->num_rows($result) > 0) {
		$i = 0;
		while ($row = $db->fetch_object($result)) {
			$arrRubrik[$i] = $row->category_id;
			$arrThema[$i] = stripslashes($row->thema);
			$arrContent[$i] = $row->content;
			$arrDatum[$i] = $row->datum;
			$arrAuthor[$i] = $row->author;
			$i++;
		}
	}
	
	$pdf = new PDF();
    $pdf->enableBookmarks = TRUE;
	$pdf->Open();
	$pdf->AliasNbPages();
	$pdf->SetDisplayMode("real");
	
	foreach ($arrContent as $key => $value) {
		$pdf->rubrik = $arrRubrik[$key];
		$pdf->thema = $arrThema[$key];
        $pdf->categories = $tree->categoryName;
		$date =  $arrDatum[$key];
		$author = $arrAuthor[$key];
		$pdf->AddPage();
		$pdf->SetFont("Arial", "", 12);
    	$pdf->WriteHTML(unhtmlentities(stripslashes($value)));
    }
	
	$pdfFile = PMF_ROOT_DIR."/pdf/faq.pdf";
	$pdf->Output($pdfFile);
	
	print "<p>".$PMF_LANG["ad_export_full_faq"]."<a href=\"../pdf/faq.pdf\" target=\"_blank\">".$PMF_CONF["title"]."</a></p>";
}
if (isset($submit[3])) {
	// XML DocBook export
    generateDocBookExport();
}

if (!emptyTable(SQLPREFIX."faqdata")) {
?>
	<form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
	<input type="hidden" name="aktion" value="export" />
	<p><strong>XML export</strong></p>
    <p align="center"><input class="submit" type="submit" name="submit[0]" value="XML export" /></p>
	<p><strong>XHTML export</strong></p>
    <p align="center"><input class="submit" type="submit" name="submit[1]" value="XHTML export" /></p>
	<p><strong><?php print $PMF_LANG["ad_export_pdf"]; ?></strong></p>
    <p align="center"><input class="submit" type="submit" name="submit[2]" value="<?php print $PMF_LANG["ad_export_generate_pdf"]; ?>" /></p>
    <p><strong>XML DocBook export</strong></p>
    <p align="center"><input class="submit" type="submit" name="submit[3]" value="XML DocBook export" /></p>
	</form>
<?php
} else {
    print $PMF_LANG["err_noArticles"];
}
?>
