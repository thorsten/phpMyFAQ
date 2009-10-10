<?php
/**
 * XML, XML DocBook, XHTML and PDF export - Classes and Functions
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Export
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since      2005-11-02
 * @version    SVN: $Id$
 * @copyright  2005-2009 phpMyFAQ Team
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

require_once PMF_CONFIG_DIR . '/constants.php';

define("EXPORT_TYPE_DOCBOOK", "docbook");
define("EXPORT_TYPE_PDF", "pdf");
define("EXPORT_TYPE_XHTML", "xhtml");
define("EXPORT_TYPE_XML", "xml");
define("EXPORT_TYPE_NONE", "none");


/**
 * PMF_Export Class
 *
 * This class manages the export formats supported by phpMyFAQ:
 * - DocBook
 * - PDF
 * - XHTML
 * - XML
 *
 * This class has only static methods
 * @package    phpMyFAQ
 * @subpackage PMF_Export
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since      2005-11-02
 * @copyright  2005-2009 phpMyFAQ Team
 */
class PMF_Export
{
	/**
	 * PMF_Faq object
	 * 
	 * @var PMF_Faq
	 */
	protected $faq = null;
	
	/**
	 * PMF_Category object
	 * 
	 * @var PMF_Category
	 */
	protected $category = null;
	
	/**
	 * Factory
	 * 
	 * @param PMF_Faq      $faq      PMF_Faq object
	 * @param PMF_Category $category PMF_Category object 
	 * @param string       $mode     Export 
	 * 
	 * @return PMF_Export
	 */
	public static function create(PMF_Faq $faq, PMF_Category $category, $mode = 'pdf')
	{
		switch ($mode) {
			case 'pdf':
				return new PMF_Export_Pdf($faq, $category);
				break;
			case 'xml':
                return new PMF_Export_Xml($faq, $category);
                break;
			case 'xhtml':
				return new PMF_Export_Xhtml($faq, $category);
				break;
			case 'docbook':
				return new PMF_Export_Docbook($faq, $category);
				break;
			default:
				throw new Exception('Export not implemented!');
		}
	}
	
	
    /**
     * Returns the timestamp of the export
     *
     * @return string
     */
    public static function getExportTimeStamp()
    {
        return date("Y-m-d-H-i-s", $_SERVER['REQUEST_TIME']);
    }

    /**
     * Returns the DocBook XML export
     * 
     * @param integer $nCatid     Number of categories
     * @param boolean $bDownwards Downwards
     * @param string  $lang       Language
     * 
     * @return string
     */
    public static function getDocBookExport($nCatid = 0, $bDownwards = true, $lang = "")
    {
        // TODO: remove the need of pre-generating a file to be read
        //generateDocBookExport();
        PMF_Export::_generateDocBookExport2();
        $filename = dirname(dirname(__FILE__)) . "/xml/docbook/docbook.xml";
        return PMF_Export::_getFileContents($filename);
    }

    /**
     * Returns the PDF export
     * 
     * @param integer $nCatid     Number of categories
     * @param boolean $bDownwards Downwards
     * @param string  $lang       Language
     * 
     * @return string
     */
    public static function getPDFExport($nCatid = 0, $bDownwards = true, $lang = "")
    {
        $tree       = new PMF_Category();
        $arrRubrik  = array();
        $arrThema   = array();
        $arrContent = array();
        $arrAuthor  = array();
        $arrDatum   = array();

        // Get Faq Data
        $oFaq = new PMF_Faq();
        $faqs = $oFaq->get(FAQ_QUERY_TYPE_EXPORT_PDF, $nCatid,  $bDownwards, $lang);

        if (count($faqs) > 0) {
            $i = 0;

            // Get the data
            foreach ($faqs as $faq) {
                $arrRubrik[$i]  = $faq['category_id'];
                $arrThema[$i]   = $faq['topic'];
                $arrContent[$i] = $faq['content'];
                $arrAuthor[$i]  = $faq['author_name'];
                $arrDatum[$i]   = $faq['lastmodified'];
                $i++;
            }

            // Start composing PDF
            $pdf = new PMF_Export_Pdf();
            $pdf->enableBookmarks = true;
            $pdf->isFullExport    = true;
            $pdf->Open();
            $pdf->AliasNbPages();
            $pdf->SetDisplayMode('real');

            // Create the PDF
            foreach ($arrContent as $key => $value) {
                $pdf->category   = $arrRubrik[$key];
                $pdf->thema      = $arrThema[$key];
                $pdf->categories = $tree->categoryName;
                $date            = $arrDatum[$key];
                $author          = $arrAuthor[$key];
                $pdf->AddPage();
                $pdf->SetFont("Arial", "", 12);
                $pdf->WriteHTML($value);
            }

            return $pdf->Output('', 'S');
        }
    }

    /**
     * Returns the XHTML export
     * 
     * @param integer $nCatid     Number of categories
     * @param boolean $bDownwards Downwards
     * @param string  $lang       Language
     * 
     * @return string
     */
    public static function getXHTMLExport($nCatid = 0, $bDownwards = true, $lang = "")
    {
        global $PMF_CONF, $PMF_LANG;

        $tree = new PMF_Category();
        $tree->transform(0);
        $old = 0;
        $xhtml  = '<?xml version="1.0" encoding="'.$PMF_LANG['metaCharset'].'" ?>';
        $xhtml .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        $xhtml .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$PMF_LANG['metaLanguage'].'" lang="'.$PMF_LANG['metaLanguage'].'">';
        $xhtml .= '<head>';
        $xhtml .= '    <title>'.PMF_htmlentities($PMF_CONF['main.titleFAQ'], ENT_QUOTES, $PMF_LANG['metaCharset']).'</title>';
        $xhtml .= '    <meta http-equiv="Content-Type" content="application/xhtml+xml; charset='.$PMF_LANG['metaCharset'].'" />';
        $xhtml .= '    <meta name="title" content="'.PMF_String::htmlspecialchars($PMF_CONF['main.titleFAQ']).'" />';
        $xhtml .= '</head>';
        $xhtml .= '<body dir="'.$PMF_LANG['dir'].'">';

        // Get Faq Data
        $oFaq = new PMF_Faq();
        $faqs = $oFaq->get(FAQ_QUERY_TYPE_EXPORT_XHTML, $nCatid,  $bDownwards, $lang);

        // Start composing XHTML
        if (count($faqs) > 0) {
            foreach ($faqs as $faq) {
                // Get faq properties
                $id      = $faq['id'];
                $lang    = $faq['lang'];
                $rub     = $faq['category_id'];
                $thema   = $faq['topic'];
                $content = $faq['content'];
                $author  = $faq['author_email'];
                $datum   = $faq['lastmodified'];
                // Take care of writing well-formed XML
                // TODO: we must implement something like tidy,
                //       $content = data2XHTML($content)
                //       BUT tidy is striclty for PHP 4.3.0+
                //       See. http://it.php.net/tidy
                // Build faq section
                if ($rub != $old) {
                    $xhtml .= '<h1>'.$tree->getPath($rub).'</h1>';
                }
                $xhtml .= '<h2>'.$thema.'</h2>';
                $xhtml .= '<p>'.$content.'</p>';
                $xhtml .= '<p>'.$PMF_LANG["msgAuthor"].$author.'<br />';
                $xhtml .= $PMF_LANG["msgLastUpdateArticle"].PMF_Date::createIsoDate($datum).'</p>';
                $xhtml .= '<hr style="width: 90%;" />';
                $old = $rub;
            }
        }

        $xhtml .= '</body>';
        $xhtml .= '</html>';

        return $xhtml;
    }
    
    /**
     * Wrapper for the PMF_Export_Docbook class
     * 
     */
    private static function _generateDocBookExport2()
    {
        // TODO: check/refine/improve/fix docbook.php and add toString method before recoding the method in order to use faq and news classes.

        global $PMF_CONF, $PMF_LANG;

        // XML DocBook export
        $parentID = 0;
        $db       = PMF_Db::getInstance();

        $export = new PMF_Export_Docbook();
        $export->delete_file();

        // Set the FAQ title
        $faqtitel = PMF_String::htmlspecialchars($PMF_CONF['main.titleFAQ']);

        // Print the title of the FAQ
        $export-> xmlContent='<?xml version="1.0" encoding="'.$PMF_LANG['metaCharset'].'"?>'
        .'<book lang="en">'
        .'<title>phpMyFAQ</title>'
        .'<bookinfo>'
        .'<title>'. $faqtitel. '</title>'
        .'</bookinfo>';

        // include the news
        $result = $db->query("SELECT id, header, artikel, datum FROM ".SQLPREFIX."faqnews");

        // Write XML file
        $export->write_file();

        // Transformation of the news entries
        if ($db->num_rows($result) > 0)
        {
            $export->xmlContent.='<part><title>News</title>';

            while ($row = $db->fetch_object($result)){

                $datum = $export->aktually_date($row->datum);
                $export->xmlContent .='<article>'
                .  '<title>'.$row->header.'</title>'
                .  '<para>'.wordwrap($datum,20).'</para>';
                $replacedString = ltrim(str_replace('<br />', '', $row->artikel));
                $export->TableImageText($replacedString);
                $export->xmlContent.='</article>';
            }
            $export->xmlContent .= '</part>';
        }

        $export->write_file();

        // Transformation of the articles
        $export->xmlContent .='<part>'
        . '<title>Artikel</title>'
        . '<preface>'
        . '<title>Rubriken</title>';

        // Selection of the categories
        $export->recursive_category($parentID);
        $export->xmlContent .='</preface>'
        . '</part>'
        . '</book>';

        $export->write_file();
    }

    /**
     * Wrapper for file_get_contents()
     * 
     * @param string $filename Filename
     * 
     * @return void
     */
    private static function _getFileContents($filename)
    {
        $filedata = "";

        // Be sure that PHP doesn't cache what it has created just before!
        clearstatcache();
        // Read the content of the text file
        $file_handler = fopen($filename, "r");
        if ($file_handler) {
            while (!feof($file_handler)) {
                $buffer = fgets($file_handler, 4096);
                $filedata .= $buffer;
            }
            fclose($file_handler);
        } else {
            die( "<b>PMF_Export Class</b> error: unable to open ".$filename);
        }

        return $filedata;
    }
}