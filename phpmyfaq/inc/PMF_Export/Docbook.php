<?php
/**
 * This is the DocBook XML V5.0 export class for phpMyFAQ content
 *
 * @category  phpMyFAQ
 * @package   PMF_Export
 * @author    David Sauer <david_sauer@web.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2005-07-21
 * @license   Mozilla Public License 1.1
 * @copyright 2005-2009 phpMyFAQ Team
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

/**
 * PMF_Export_Docbook
 *
 * @category  phpMyFAQ
 * @package   PMF_Export
 * @author    David Sauer <david_sauer@web.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2005-07-21
 * @license   Mozilla Public License 1.1
 * @copyright 2005-2009 phpMyFAQ Team
 */
class PMF_Export_Docbook extends PMF_Export 
{
    /**
     * XMLWriter object
     * 
     * @var XMLWriter
     */
    private $xml = null;
    
	var $xmlContent;
	var $xmlEntities;
	var $info;
	var $part_counter  = 0;
	var $table_counter = 0;
	var $cell_counter  = 0;

    /**
     * Constructor
     * 
     * @param PMF_Faq      $faq      PMF_Faq object
     * @param PMF_Category $category PMF_Category object 
     * 
     * return PMF_Export_Pdf
     */
    public function __construct(PMF_Faq $faq, PMF_Category $category)
    {
        $this->faq      = $faq;
        $this->category = $category;
        $this->xml      = new XMLWriter();
        
        $this->xml->openMemory();
        $this->xml->setIndent(true);
	}

    /**
     * Generates the export
     * 
     * @param integer $categoryId Category Id
     * @param boolean $downwards  If true, downwards, otherwise upward ordering
     * @param string  $language   Language
     * 
     * @return string
     */
    public function generate($categoryId = 0, $downwards = true, $language = '')
    {        
        // Initialize categories
        $this->category->transform($categoryId);
        
        $faqdata = $this->faq->get(FAQ_QUERY_TYPE_EXPORT_XML, $categoryId, $downwards, $language);
        $version = PMF_Configuration::getInstance()->get('main.currentVersion');
        $author  = PMF_Configuration::getInstance()->get('main.metaPublisher');
        $comment = sprintf(' Docbook XML output by phpMyFAQ %s | Date: %s ', 
            $version, 
            PMF_Date::createIsoDate(date("YmdHis")));
        
        // Docbook XML Header
        $this->xml->startDocument('1.0', 'utf-8');
        $this->xml->writeDtd('book', 
                             '-//OASIS//DTD DocBook V5.0//EN', 
                             'http://www.oasis-open.org/docbook/xml/5.0/docbook.dtd');
        $this->xml->writeComment($comment);
        $this->xml->startElement('book');
        $this->xml->startElement('info');
        $this->xml->writeElement('title', PMF_Configuration::getInstance()->get('main.titleFAQ'));
        $this->xml->writeElement('author', $author);
        $this->xml->endElement();
        
        // FAQs
        if (count($faqdata)) {
        	
        	foreach ($this->category->categories as $category) {
        		
        		$section = 1;
        		$this->xml->startElement('chapter');
        		$this->xml->writeElement('title', $category['name']);
        		if (!empty($category['description'])) {
        			$this->xml->writeElement('para', $category['description']);
        		}
        		
                foreach ($faqdata as $faq) {
                    if ($category['id'] == $faq['category_id']) {
                    	$this->xml->startElement('sect' . $section);
                    	$this->xml->writeElement('title', $faq['topic']);
                    	$this->xml->writeElement('para', $this->convertAnswer($faq['content']));
                    	$this->xml->endElement(); // </sect1>
                    	$section++;
                    }
                }
                
        		$this->xml->endElement(); // </chapter>
        	}
        }
        
        $this->xml->endElement(); // </book>
        
        header('Content-type: text/xml'); 
        return $this->xml->outputMemory();
    }
    
    /**
     * Converts the (X)HTML code to valid Docbook XML code
     * 
     * @param string $xhtml (X)HTML string
     * 
     * @return string 
     */
    protected function convertAnswer($xhtml)
    {
    	$docbookXml = '';
    	
    	
    	return $docbookXml;
    }
}