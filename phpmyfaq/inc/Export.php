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