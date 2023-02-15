<?php

/**
 * HTML5 Export class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-10-07
 */

namespace phpMyFAQ\Export;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use phpMyFAQ\Export;
use phpMyFAQ\Faq;
use phpMyFAQ\Init;
use phpMyFAQ\Strings;
use XMLWriter;

/**
 * Class Html5
 *
 * @package phpMyFAQ\Export
 */
class Html5 extends Export
{
    /**
     * XMLWriter object.
     *
     * @var XMLWriter
     */
    private $xml;

    /**
     * Constructor.
     *
     * @param Faq           $faq      FaqHelper object
     * @param Category      $category CategoryHelper object
     * @param Configuration $config   Configuration
     *                                return
     *                                PMF_Export_Xhtml
     */
    public function __construct(Faq $faq, Category $category, Configuration $config)
    {
        $this->faq = $faq;
        $this->category = $category;
        $this->config = $config;
        $this->xml = new XMLWriter();

        $this->xml->openMemory();
        $this->xml->setIndent(true);
    }

    /**
     * Generates the export.
     *
     * @param int    $categoryId CategoryHelper Id
     * @param bool   $downwards  If true, downwards, otherwise upward ordering
     * @param string $language   Language
     *
     * @return string
     */
    public function generate(int $categoryId = 0, bool $downwards = true, string $language = ''): string
    {
        global $PMF_LANG;

        // Initialize categories
        $this->category->transform($categoryId);

        $faqData = $this->faq->get(FAQ_QUERY_TYPE_EXPORT_XHTML, $categoryId, $downwards, $language);
        $version = $this->config->getVersion();
        $comment = sprintf(
            ' HTML5 output by phpMyFAQ %s | Date: %s ',
            $version,
            Date::createIsoDate(date('YmdHis'))
        );

        $this->xml->startDTD('html');
        $this->xml->startElement('html');

        $this->xml->writeComment($comment);

        $this->xml->startElement('head');
        $this->xml->writeElement('title', $this->config->getTitle());
        $this->xml->startElement('meta');
        $this->xml->writeAttribute('charset', 'utf-8');
        $this->xml->endElement();
        $this->xml->endElement(); // </head>

        $this->xml->startElement('body');
        $this->xml->writeAttribute('dir', $PMF_LANG['dir']);

        if (count($faqData)) {
            $lastCategory = 0;
            foreach ($faqData as $data) {
                if ($data['category_id'] != $lastCategory) {
                    $this->xml->writeElement('h1', $this->category->getPath($data['category_id'], ' >> '));
                }

                $this->xml->startElement('h2');
                $this->xml->writeAttribute('id', "entry-" . $data['solution_id']);
                $this->xml->text(strip_tags($data['topic']));
                $this->xml->endElement();
                $this->xml->startElement('p');
                $this->xml->writeCdata(html_entity_decode($data['content'], ENT_HTML5, 'UTF-8'));
                $this->xml->endElement();
                $this->xml->writeElement('p', $PMF_LANG['msgAuthor'] . ': ' . $data['author_email']);
                $this->xml->writeElement(
                    'p',
                    $PMF_LANG['msgLastUpdateArticle'] . Date::createIsoDate($data['lastmodified'])
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
