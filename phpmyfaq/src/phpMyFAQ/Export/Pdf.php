<?php

/**
 * PDF Export class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-10-07
 */

namespace phpMyFAQ\Export;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Date;
use phpMyFAQ\Export;
use phpMyFAQ\Export\Pdf\Wrapper;
use phpMyFAQ\Faq;
use phpMyFAQ\System;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
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
     */
    private readonly ?Wrapper $pdf;

    private ?Tags $tags = null;

    private readonly ?CommonMarkConverter $converter;

    /**
     * Constructor.
     *
     * @param  Faq           $faq           FaqHelper object
     * @param  Category      $category      Entity object
     * @param  Configuration $configuration Configuration
     */
    public function __construct(Faq $faq, Category $category, Configuration $configuration)
    {
        $this->faq = $faq;
        $this->category = $category;
        $this->config = $configuration;

        $this->pdf = new Wrapper();
        $this->pdf->setConfig($this->config);

        // Set PDF options
        $this->pdf->Open();
        $this->pdf->SetDisplayMode('real');
        $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        $this->converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * Generates the export.
     *
     * @param int    $categoryId CategoryHelper Id
     * @param bool   $downwards If true, downwards, otherwise upward ordering
     * @param string $language Language
     * @throws CommonMarkException
     */
    public function generate(int $categoryId = 0, bool $downwards = true, string $language = ''): string
    {
        // Set PDF options
        $this->pdf->enableBookmarks = true;
        $this->pdf->isFullExport = true;
        $filename = 'FAQs.pdf';

        // Initialize categories
        $this->category->transform($categoryId);

        $this->pdf->setCategory($categoryId);
        $this->pdf->setCategories($this->category->categoryName);
        $this->pdf->SetCreator($this->config->getTitle() . ' - ' . System::getPoweredByString());

        $faqData = $this->faq->get('faq_export_pdf', $categoryId, $downwards, $language);

        $currentCategory = 0;

        foreach ($faqData as $faq) {
            $this->pdf->AddPage();

            // Bookmark for categories
            if ($currentCategory !== $this->category->categoryName[$faq['category_id']]['id']) {
                $this->pdf->Bookmark(
                    html_entity_decode(
                        $this->category->categoryName[$faq['category_id']]['name'],
                        ENT_QUOTES,
                        'utf-8'
                    ),
                    $this->category->categoryName[$faq['category_id']]['level'] - 1,
                    0
                );
            }

            // Bookmark for FAQs
            $this->pdf->Bookmark(
                html_entity_decode(
                    (string) $faq['topic'],
                    ENT_QUOTES,
                    'utf-8'
                ),
                $this->category->categoryName[$faq['category_id']]['level'],
                0
            );

            if ($this->tags instanceof Tags) {
                $tags = $this->tags->getAllTagsById($faq['id']);
            }

            $this->pdf->SetFont($this->pdf->getCurrentFont(), 'b', 12);
            $this->pdf->WriteHTML('<h1>' . $this->category->categoryName[$faq['category_id']]['name'] . '</h1>');
            $this->pdf->WriteHTML('<h2>' . $faq['topic'] . '</h2>');
            $this->pdf->Ln(10);

            $this->pdf->SetFont($this->pdf->getCurrentFont(), '', 10);

            if ($this->config->get('main.enableMarkdownEditor')) {
                $this->pdf->WriteHTML(trim((string) $this->converter->convert($faq['content'])->getContent()));
            } else {
                $this->pdf->WriteHTML(trim((string) $faq['content']));
            }

            $this->pdf->Ln(10);

            if (!empty($faq['keywords'])) {
                $this->pdf->Ln();
                $this->pdf->Write(5, Translation::get('msgNewContentKeywords') . ' ' . $faq['keywords']);
            }

            if (isset($tags) && 0 !== (is_countable($tags) ? count($tags) : 0)) {
                $this->pdf->Ln();
                $this->pdf->Write(5, Translation::get('ad_entry_tags') . ': ' . implode(', ', $tags));
            }

            $this->pdf->Ln();
            $this->pdf->Ln();
            $this->pdf->Write(
                5,
                Translation::get('msgLastUpdateArticle') . Date::createIsoDate($faq['lastmodified'])
            );

            $currentCategory = $this->category->categoryName[$faq['category_id']]['id'];
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
     * @throws CommonMarkException
     * @throws Exception
     */
    public function generateFile(array $faqData, ?string $filename = null): string
    {
        // Default filename: FAQ-<id>-<language>.pdf
        if ($filename === null || $filename === '') {
            $filename = sprintf('FAQ-%s-%s.pdf', $faqData['id'], $faqData['lang']);
        }

        $date = new Date($this->config);

        $this->pdf->setFaq($faqData);
        $this->pdf->setCategory($faqData['category_id']);
        $this->pdf->setQuestion($faqData['title']);
        $this->pdf->setCategories($this->category->categoryName);

        // Set any item
        $this->pdf->SetTitle($faqData['title']);
        $this->pdf->SetCreator($this->config->getTitle() . ' - ' . System::getPoweredByString());

        $this->pdf->AddPage();
        $this->pdf->SetFont($this->pdf->getCurrentFont(), '', 10);
        $this->pdf->SetDisplayMode('real');
        $this->pdf->Ln();
        $this->pdf->Ln();
        $this->pdf->WriteHTML('<h3>' . $faqData['title'] . '</h3>');
        $this->pdf->Ln(5);
        $this->pdf->Ln();

        if ($this->config->get('main.enableMarkdownEditor')) {
            $this->pdf->WriteHTML($this->converter->convert($faqData['content'])->getContent());
        } else {
            $this->pdf->WriteHTML((string) $faqData['content']);
        }

        $this->pdf->Ln(10);
        $this->pdf->Ln();
        $this->pdf->SetFont($this->pdf->getCurrentFont(), '', 9);
        $this->pdf->Write(5, Translation::get('ad_entry_solution_id') . ': #' . $faqData['solution_id']);

        // Check if the author name should be visible, according to the GDPR option
        $currentUser = new CurrentUser($this->config);
        $author = $currentUser->getUserVisibilityByEmail($faqData['email']) ? $faqData['author'] : 'n/a';

        $this->pdf->SetAuthor($author);
        $this->pdf->Ln();
        $this->pdf->Write(5, Translation::get('msgAuthor') . ': ' . $author);
        $this->pdf->Ln();
        $this->pdf->Write(5, Translation::get('msgLastUpdateArticle') . $date->format($faqData['date']));

        return $this->pdf->Output($filename);
    }
}
