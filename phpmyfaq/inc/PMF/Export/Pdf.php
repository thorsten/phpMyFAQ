<?php

/**
 * PDF Export class for phpMyFAQ.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-10-07
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Export_Pdf.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-10-07
 */
class PMF_Export_Pdf extends PMF_Export
{
    /**
     * PMF_Export_Pdf_Wrapper object.
     *
     * @var PMF_Export_Pdf_Wrapper
     */
    private $pdf = null;

    /**
     * @var PMF_Tags
     */
    private $tags = null;

    /**
     * @var ParsedownExtra
     */
    private $parsedown = null;

    /**
     * Constructor.
     *
     * @param PMF_Faq           $faq      Faq object
     * @param PMF_Category      $category Category object
     * @param PMF_Configuration $config   Configuration
     *
     * return PMF_Export_Pdf
     */
    public function __construct(PMF_Faq $faq, PMF_Category $category, PMF_Configuration $config)
    {
        $this->faq = $faq;
        $this->category = $category;
        $this->_config = $config;

        $this->pdf = new PMF_Export_Pdf_Wrapper();
        $this->pdf->setConfig($this->_config);

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
     * @param int    $categoryId Category Id
     * @param bool   $downwards  If true, downwards, otherwise upward ordering
     * @param string $language   Language
     *
     * @return string
     */
    public function generate($categoryId = 0, $downwards = true, $language = '')
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
            $this->_config->get('main.titleFAQ').
            ' - powered by phpMyFAQ '.
            $this->_config->get('main.currentVersion')
        );

        $faqdata = $this->faq->get(FAQ_QUERY_TYPE_EXPORT_XML, $categoryId, $downwards, $language);
        $categories = $this->category->catTree;

        $categoryGroup = '';
        $this->pdf->AddPage();
        foreach ($categories as $catKey => $category) {

            if (0 === $catKey || $this->category->categoryName[$category['id']]['name'] !== $categoryGroup) {
                $this->pdf->Bookmark(
                    html_entity_decode(
                        $this->category->categoryName[$category['id']]['name'], ENT_QUOTES, 'utf-8'
                    ),
                    $category['level'],
                    0
                );
                $this->pdf->setCategory($category['id']);
                $categoryGroup = $this->category->categoryName[$category['id']]['name'];
            }

            foreach ($faqdata as $faq) {

                if ($faq['category_id'] === $category['id']) {
                    $this->pdf->AddPage();
                    $this->pdf->Bookmark(
                        html_entity_decode(
                            $faq['topic'], ENT_QUOTES, 'utf-8'
                        ),
                        $category['level'] + 1,
                        0
                    );

                    if ($this->tags instanceof PMF_Tags) {
                        $tags = $this->tags->getAllTagsById($faq['id']);
                    }

                    $this->pdf->WriteHTML('<h2 align="center">'.$faq['topic'].'</h2>', true);
                    $this->pdf->Ln(10);

                    $this->pdf->SetFont($this->pdf->getCurrentFont(), '', 12);

                    if ($this->_config->get('main.enableMarkdownEditor')) {
                        $this->pdf->WriteHTML(trim($this->parsedown->text($faq['content'])));
                    } else {
                        $this->pdf->WriteHTML(trim($faq['content']));
                    }

                    $this->pdf->Ln(10);

                    if (!empty($faq['keywords'])) {
                        $this->pdf->Ln();
                        $this->pdf->Write(5, $PMF_LANG['msgNewContentKeywords'].' '.$faq['keywords']);
                    }
                    if (isset($tags) && 0 !== count($tags)) {
                        $this->pdf->Ln();
                        $this->pdf->Write(5, $PMF_LANG['ad_entry_tags'].': '.implode(', ', $tags));
                    }

                    $this->pdf->Ln();
                    $this->pdf->Ln();
                    $this->pdf->Write(
                        5,
                        $PMF_LANG['msgLastUpdateArticle'].PMF_Date::createIsoDate($faq['lastmodified'])
                    );
                }
            }
        }

        // remove default header/footer
        $this->pdf->setPrintHeader(false);
        $this->pdf->addFaqToc();

        return $this->pdf->Output($filename);
    }

    /**
     * Builds the PDF delivery for the given faq.
     *
     * @param array  $faqData
     * @param string $filename
     *
     * @return string
     */
    public function generateFile(Array $faqData, $filename = null)
    {
        global $PMF_LANG;

        // Default filename: FAQ-<id>-<language>.pdf
        if (empty($filename)) {
            $filename = 'FAQ-'.$faqData['id'].'-'.$faqData['lang'].'.pdf';
        }

        $this->pdf->setFaq($faqData);
        $this->pdf->setCategory($faqData['category_id']);
        $this->pdf->setQuestion($faqData['title']);
        $this->pdf->setCategories($this->category->categoryName);

        // Set any item
        $this->pdf->SetTitle($faqData['title']);
        $this->pdf->SetCreator(
            $this->_config->get('main.titleFAQ').
            ' - powered by phpMyFAQ '.
            $this->_config->get('main.currentVersion')
        );
        $this->pdf->AddPage();
        $this->pdf->SetFont($this->pdf->getCurrentFont(), '', 12);
        $this->pdf->SetDisplayMode('real');
        $this->pdf->Ln();
        $this->pdf->WriteHTML('<h1 align="center">'.$faqData['title'].'</h1>', true);
        $this->pdf->Ln();
        $this->pdf->Ln();

        if ($this->_config->get('main.enableMarkdownEditor')) {
            $this->pdf->WriteHTML(str_replace('../', '', $this->parsedown->text($faqData['content'])), true);
        } else {
            $this->pdf->WriteHTML(str_replace('../', '', $faqData['content']), true);
        }

        $this->pdf->Ln(10);
        $this->pdf->Ln();
        $this->pdf->SetFont($this->pdf->getCurrentFont(), '', 11);
        $this->pdf->Write(5, $PMF_LANG['ad_entry_solution_id'].': #'.$faqData['solution_id']);
        $this->pdf->SetAuthor($faqData['author']);
        $this->pdf->Ln();
        $this->pdf->Write(5, $PMF_LANG['msgAuthor'].': '.$faqData['author']);
        $this->pdf->Ln();
        $this->pdf->Write(5, $PMF_LANG['msgLastUpdateArticle'].$faqData['date']);

        return $this->pdf->Output($filename);
    }
}
