<?php
/**
 * XHTML Export class for phpMyFAQ
 *
 * PHP Version 5.2
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
 *
 * @category  phpMyFAQ
 * @package   PMF_Export
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-10-07
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Export_Xhtml
 *
 * @category  phpMyFAQ
 * @package   PMF_Export
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-10-07
 */
class PMF_Export_Xhtml extends PMF_Export 
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
     * return PMF_Export_Xhtml
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
        global $PMF_LANG;

        // Initialize categories
        $this->category->transform($categoryId);
        
        $faqdata = $this->faq->get(FAQ_QUERY_TYPE_EXPORT_XHTML, $categoryId, $downwards, $language);
        $version = PMF_Configuration::getInstance()->get('main.currentVersion');
        $comment = sprintf('XHTML output by phpMyFAQ %s | Date: %s', 
          $version, 
          PMF_Date::createIsoDate(date("YmdHis")));
        
        $this->xml->startDocument('1.0', 'utf-8');
        $this->xml->writeDtd('html', 
                             '-//W3C//DTD XHTML 1.0 Transitional//EN', 
                             'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd');
        $this->xml->startElement('html');
        $this->xml->writeAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
        $this->xml->writeAttribute('xml:lang', $language);
        $this->xml->writeComment($comment);
        
        $this->xml->startElement('head');
        $this->xml->writeElement('title', PMF_Configuration::getInstance()->get('main.titleFAQ'));
        $this->xml->startElement('meta');
        $this->xml->writeAttribute('http-equiv', 'Content-Type');
        $this->xml->writeAttribute('content', 'application/xhtml+xml; charset=utf-8');
        $this->xml->endElement();
        $this->xml->endElement(); // </head>
        
        $this->xml->startElement('body');
        $this->xml->writeAttribute('dir', $PMF_LANG['dir']);
        
        if (count($faqdata)) {
            $lastCategory = 0;
            foreach ($faqdata as $data) {
            
                if ($data['category_id'] != $lastCategory) {
                    $this->xml->writeElement('h1', $this->category->getPath($data['category_id'], ' >> '));
                }
                
                $this->xml->writeElement('h2', strip_tags($data['topic']));
                $this->xml->startElement('p');
                $this->xml->writeCdata(html_entity_decode($data['content'], ENT_QUOTES, 'UTF-8'));
                $this->xml->endElement();
                $this->xml->writeElement('p', $PMF_LANG['msgAuthor'] . ': ' .$data['author_email']);
                $this->xml->writeElement(
                    'p',
                    $PMF_LANG['msgLastUpdateArticle'] . PMF_Date::createIsoDate($data['lastmodified'])
                );
                
                $lastCategory = $data['category_id'];
            }
        }
        
        $this->xml->endElement(); // </body>
        $this->xml->endElement(); // </html>
        
        header('Content-type: text/html'); 
        return $this->xml->outputMemory();
    }
}