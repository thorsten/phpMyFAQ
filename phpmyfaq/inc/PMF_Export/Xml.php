<?php
/**
 * XML Export class for phpMyFAQ
 *
 * @category  phpMyFAQ
 * @package   PMF_Export
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2009-10-07
 * @license   Mozilla Public License 1.1
 * @copyright 2009-2012 phpMyFAQ Team
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Export_Xml
 *
 * @category  phpMyFAQ
 * @package   PMF_Export
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2009-10-07
 * @license   Mozilla Public License 1.1
 * @copyright 2009 phpMyFAQ Team
 */
class PMF_Export_Xml extends PMF_Export 
{
	/**
	 * XMLWriter object
	 *
	 * @var XMLWriter
	 */
	private $xml = null;
	
	/**
	 * Constructor
	 *
     * @param PMF_Faq      $faq      PMF_Faq object
     * @param PMF_Category $category PMF_Category object 
     *
	 * return PMF_Export_Xml
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
		$comment = sprintf('XML output by phpMyFAQ %s | Date: %s', 
		  $version, 
		  PMF_Date::createIsoDate(date("YmdHis")));
		
		$this->xml->startDocument('1.0', 'utf-8', 'yes');
		$this->xml->writeComment($comment);
		$this->xml->startElement('phpmyfaq');
		
		if (count($faqdata)) {
            foreach ($faqdata as $data) {
                
                // Build the <article/> node
                $this->xml->startElement('article');
                $this->xml->writeAttribute('id', $data['id']);
                $this->xml->writeElement('language', $data['lang']);
                $this->xml->writeElement('category', $this->category->getPath($data['category_id'], ' >> '));
                
                if (!empty($data['keywords'])) {
                    $this->xml->writeElement('keywords', $data['keywords']);
                } else {
                    $this->xml->writeElement('keywords');
                }
                
                $this->xml->writeElement('question', strip_tags($data['topic']));
                $this->xml->writeElement('answer', PMF_String::htmlspecialchars($data['content']));

                if (!empty($data['author_name'])) {
                	$this->xml->writeElement('author', $data['author_name']);
                } else {
                    $this->xml->writeElement('author');
                }
                
                $this->xml->writeElement('data', PMF_Date::createIsoDate($data['lastmodified']));
                $this->xml->endElement();
            }
            	
		}
		
		$this->xml->endElement();
		
		header('Content-type: text/xml'); 
		return $this->xml->outputMemory();
	}
}