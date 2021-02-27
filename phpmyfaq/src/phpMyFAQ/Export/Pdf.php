<?php

/**
 * PDF Export class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-10-07
 */

namespace phpMyFAQ\Export;

use Exception;
use ParsedownExtra;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use phpMyFAQ\Export;
use phpMyFAQ\Export\Pdf\Wrapper;
use phpMyFAQ\Faq;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;

/**
 * Class Pdf
 *
 * @package phpMyFAQ\Export
 */
class Pdf extends Export
{
    /**
     * Wrapper object.
     *
     * @var Wrapper
     */
    private $pdf = null;

    /**
     * @var Tags
     */
    private $tags = null;

    /**
     * @var ParsedownExtra
     */
    private $parsedown = null;

    /**
     * Constructor.
     *
     * @param  Faq           $faq      FaqHelper object
     * @param  Category      $category Entity object
     * @param  Configuration $config   Configuration
     * @throws Exception
     */
    public function __construct(Faq $faq, Category $category, Configuration $config)
    {
        $this->faq = $faq;
        $this->category = $category;
        $this->config = $config;

        $this->pdf = new Wrapper();
        $this->pdf->setConfig($this->config);

        // Set PDF options
        $this->pdf->Open();
        $this->pdf->SetDisplayMode('real');
        $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        $this->parsedown = new ParsedownExtra();
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
    public function generate($categoryId = 0, $downwards = true, $language = ''): string
    {
        global $PMF_LANG;

        // Set PDF options
        $this->pdf->enableBookmarks = true;
        $this->pdf->isFullExport = true;
        $filename = 'FAQs.pdf';

        // Initialize categories
        $this->category->transform($categoryId);

        $this->pdf->setCategory($categoryId);
        $this->pdf->setCategories($this->category->categoryName);
        $this->pdf->SetCreator(
            $this->config->getTitle() .
            ' - powered by phpMyFAQ ' .
            $this->config->getVersion()
        );

        $faqData = $this->faq->get(FAQ_QUERY_TYPE_EXPORT_XML, $categoryId, $downwards, $language);

        foreach ($faqData as $faq) {
            $this->pdf->AddPage();
            $this->pdf->Bookmark(
                html_entity_decode(
                    $faq['topic'],
                    ENT_QUOTES,
                    'utf-8'
                ),
                $this->category->categoryName[$faq['category_id']]['level'] + 1,
                0
            );

            if ($this->tags instanceof Tags) {
                $tags = $this->tags->getAllTagsById($faq['id']);
            }

            $this->pdf->WriteHTML('<h2 style="text-align: center;">' . $faq['topic'] . '</h2>', true);
            $this->pdf->Ln(10);

            $this->pdf->SetFont($this->pdf->getCurrentFont(), '', 12);

            if ($this->config->get('main.enableMarkdownEditor')) {
                $this->pdf->WriteHTML(trim($this->parsedown->text($faq['content'])));
            } else {
                $this->pdf->WriteHTML(trim($faq['content']));
            }

            $this->pdf->Ln(10);

            if (!empty($faq['keywords'])) {
                $this->pdf->Ln();
                $this->pdf->Write(5, $PMF_LANG['msgNewContentKeywords'] . ' ' . $faq['keywords']);
            }
            if (isset($tags) && 0 !== count($tags)) {
                $this->pdf->Ln();
                $this->pdf->Write(5, $PMF_LANG['ad_entry_tags'] . ': ' . implode(', ', $tags));
            }

            $this->pdf->Ln();
            $this->pdf->Ln();
            $this->pdf->Write(
                5,
                $PMF_LANG['msgLastUpdateArticle'] . Date::createIsoDate($faq['lastmodified'])
            );
        }

        // remove default header/footer
        $this->pdf->setPrintHeader(false);
        $this->pdf->addFaqToc();

        return $this->pdf->Output($filename);
    }

    /**
     * Builds the PDF delivery for the given faq.
     *
     * @param array       $faqData
     * @param string|null $filename
     * @return string
     */
    public function generateFile(array $faqData, string $filename = null): string
    {
        global $PMF_LANG;

        // Default filename: FAQ-<id>-<language>.pdf
        if (empty($filename)) {
            $filename = 'FAQ-' . $faqData['id'] . '-' . $faqData['lang'] . '.pdf';
        }

        $this->pdf->setFaq($faqData);
        $this->pdf->setCategory($faqData['category_id']);
        $this->pdf->setQuestion($faqData['title']);
        $this->pdf->setCategories($this->category->categoryName);

        // Set any item
        $this->pdf->SetTitle($faqData['title']);
        $this->pdf->SetCreator(
            $this->config->getTitle() .
            ' - powered by phpMyFAQ ' .
            $this->config->getVersion()
        );
        $this->pdf->AddPage();
        $this->pdf->SetFont($this->pdf->getCurrentFont(), '', 12);
        $this->pdf->SetDisplayMode('real');
        $this->pdf->Ln();
        $this->pdf->WriteHTML('<h1 style="text-align: center;">' . $faqData['title'] . '</h1>', true);
        $this->pdf->Ln();
        $this->pdf->Ln();

        if ($this->config->get('main.enableMarkdownEditor')) {
            $this->pdf->WriteHTML(str_replace('../', '', $this->parsedown->text($faqData['content'])), true);
        } else {
            $this->pdf->WriteHTML(str_replace('../', '', $faqData['content']), true);
        }

        $this->pdf->Ln(10);
        $this->pdf->Ln();
        $this->pdf->SetFont($this->pdf->getCurrentFont(), '', 11);
        $this->pdf->Write(5, $PMF_LANG['ad_entry_solution_id'] . ': #' . $faqData['solution_id']);

        // Check if author name should be visible according to GDPR option
        $user = new CurrentUser($this->config);
        if ($user->getUserVisibilityByEmail($faqData['email'])) {
            $author = $faqData['author'];
        } else {
            $author = 'n/a';
        }

        $this->pdf->SetAuthor($author);
        $this->pdf->Ln();
        $this->pdf->Write(5, $PMF_LANG['msgAuthor'] . ': ' . $author);
        $this->pdf->Ln();
        $this->pdf->Write(5, $PMF_LANG['msgLastUpdateArticle'] . $faqData['date']);

        return $this->pdf->Output($filename);
    }
}
