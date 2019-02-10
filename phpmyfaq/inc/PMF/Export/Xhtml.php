<?php

/**
 * XHTML Export class for phpMyFAQ.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-10-07
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Export_Xhtml.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-10-07
 */
class PMF_Export_Xhtml extends PMF_Export
{
    /**
     * XMLWriter object.
     *
     * @var XMLWriter
     */
    private $xml = null;

    /**
     * Constructor.
     *
     * @param PMF_Faq           $faq      Faq object
     * @param PMF_Category      $category Category object
     * @param PMF_Configuration $config   Configuration
     *
     * return PMF_Export_Xhtml
     */
    public function __construct(PMF_Faq $faq, PMF_Category $category, PMF_Configuration $config)
    {
        $this->faq = $faq;
        $this->category = $category;
        $this->_config = $config;
        $this->xml = new XMLWriter();

        $this->xml->openMemory();
        $this->xml->setIndent(true);
    }

    /**
     * Generates the export.
     *
     * @param int    $categoryId Category Id
     * @param bool   $downwards  If true, downwards, otherwise upward ordering
     * @param string $language   Language
     *
     * @return string
     */
    public function generate($categoryId = 0, $downwards = true, $language = '')
    {
        global $PMF_LANG;

        // Initialize categories
        $this->category->transform($categoryId);

        $faqdata = $this->faq->get(FAQ_QUERY_TYPE_EXPORT_XHTML, $categoryId, $downwards, $language);
        $version = $this->_config->get('main.currentVersion');
        $comment = sprintf('XHTML output by phpMyFAQ %s | Date: %s',
          $version,
          PMF_Date::createIsoDate(date('YmdHis')));

        $this->xml->startDocument('1.0', 'utf-8');
        $this->xml->writeDtd('html',
                             '-//W3C//DTD XHTML 1.0 Transitional//EN',
                             'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd');
        $this->xml->startElement('html');
        $this->xml->writeAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
        $this->xml->writeAttribute('xml:lang', $language);
        $this->xml->writeComment($comment);

        $this->xml->startElement('head');
        $this->xml->writeElement('title', $this->_config->get('main.titleFAQ'));
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
                $this->xml->writeElement('p', $PMF_LANG['msgAuthor'].': '.$data['author_email']);
                $this->xml->writeElement(
                    'p',
                    $PMF_LANG['msgLastUpdateArticle'].PMF_Date::createIsoDate($data['lastmodified'])
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
