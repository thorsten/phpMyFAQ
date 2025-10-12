<?php

declare(strict_types=1);

/**
 * PDF Export class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2025 phpMyFAQ Team
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
    private readonly ?Wrapper $wrapper;

    private ?Tags $tags = null;

    private readonly ?CommonMarkConverter $commonMarkConverter;

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

        $this->wrapper = new Wrapper();
        $this->wrapper->setConfig($this->config);

        // Set PDF options
        $this->wrapper->Open();
        $this->wrapper->SetDisplayMode('real');
        $this->wrapper->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->wrapper->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->wrapper->SetFooterMargin(PDF_MARGIN_FOOTER);

        $this->commonMarkConverter = new CommonMarkConverter([
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
        $this->wrapper->enableBookmarks = true;
        $this->wrapper->isFullExport = true;

        $filename = 'FAQs.pdf';

        // Initialize categories
        $this->category->transform($categoryId);

        $this->wrapper->setCategory($categoryId);
        $this->wrapper->setCategories($this->category->categoryName);
        $this->wrapper->SetCreator($this->config->getTitle() . ' - ' . System::getPoweredByString());

        $faqData = $this->faq->get('faq_export_pdf', $categoryId, $downwards, $language);

        $currentCategory = 0;

        foreach ($faqData as $faq) {
            $this->wrapper->AddPage();

            // Bookmark for categories
            if ($currentCategory !== $this->category->categoryName[$faq['category_id']]['id']) {
                $this->wrapper->Bookmark(
                    html_entity_decode(
                        (string) $this->category->categoryName[$faq['category_id']]['name'],
                        ENT_QUOTES,
                        'utf-8',
                    ),
                    $this->category->categoryName[$faq['category_id']]['level'] - 1,
                    0,
                );
            }

            // Bookmark for FAQs
            $this->wrapper->Bookmark(
                html_entity_decode((string) $faq['topic'], ENT_QUOTES, 'utf-8'),
                $this->category->categoryName[$faq['category_id']]['level'],
                0,
            );

            if ($this->tags instanceof Tags) {
                $tags = $this->tags->getAllTagsById($faq['id']);
            }

            $this->wrapper->SetFont($this->wrapper->getCurrentFont(), 'b', 12);
            $this->wrapper->WriteHTML('<h1>' . $this->category->categoryName[$faq['category_id']]['name'] . '</h1>');
            $this->wrapper->WriteHTML('<h2>' . $faq['topic'] . '</h2>');
            $this->wrapper->Ln(10);

            $this->wrapper->SetFont($this->wrapper->getCurrentFont(), '', 10);

            if ($this->config->get('main.enableMarkdownEditor')) {
                $this->wrapper->WriteHTML(trim($this->commonMarkConverter->convert($faq['content'])->getContent()));
            } else {
                $this->wrapper->WriteHTML(trim((string) $faq['content']));
            }

            $this->wrapper->Ln(10);

            if (!empty($faq['keywords'])) {
                $this->wrapper->Ln();
                $this->wrapper->Write(5, Translation::get('msgNewContentKeywords') . ' ' . $faq['keywords']);
            }

            if (isset($tags) && 0 !== (is_countable($tags) ? count($tags) : 0)) {
                $this->wrapper->Ln();
                $this->wrapper->Write(5, Translation::get('msgTags') . ': ' . implode(', ', $tags));
            }

            $this->wrapper->Ln();
            $this->wrapper->Ln();
            $this->wrapper->Write(
                5,
                Translation::get('msgLastUpdateArticle') . Date::createIsoDate($faq['lastmodified']),
            );

            $currentCategory = $this->category->categoryName[$faq['category_id']]['id'];
        }

        // remove default header/footer
        $this->wrapper->setPrintHeader(false);
        $this->wrapper->addFaqToc();

        return $this->wrapper->Output($filename);
    }

    /**
     * Builds the PDF delivery for the given FAQ.
     *
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

        $this->wrapper->setFaq($faqData);
        $this->wrapper->setCategory($faqData['category_id']);
        $this->wrapper->setQuestion($faqData['title']);
        $this->wrapper->setCategories($this->category->categoryName);

        // Set any item
        $this->wrapper->SetTitle($faqData['title']);
        $this->wrapper->SetCreator($this->config->getTitle() . ' - ' . System::getPoweredByString());

        $this->wrapper->AddPage();
        $this->wrapper->SetFont($this->wrapper->getCurrentFont(), '', 10);
        $this->wrapper->SetDisplayMode('real');
        $this->wrapper->Ln();
        $this->wrapper->Ln();
        $this->wrapper->WriteHTML('<h3>' . $faqData['title'] . '</h3>');
        $this->wrapper->Ln(5);
        $this->wrapper->Ln();

        if ($this->config->get('main.enableMarkdownEditor')) {
            $this->wrapper->WriteHTML($this->commonMarkConverter->convert($faqData['content'])->getContent());
        } else {
            $this->wrapper->WriteHTML((string) $faqData['content']);
        }

        if (isset($faqData['attachmentList'])) {
            $this->wrapper->Ln(10);
            $this->wrapper->Ln();
            $this->wrapper->Write(5, Translation::get('msgAttachedFiles') . ':');
            $this->wrapper->Ln(5);
            $this->wrapper->Ln();
            $listItems = '<ul class="pb-4 mb-4 border-bottom">';
            foreach ($faqData['attachmentList'] as $attachment) {
                $listItems .= sprintf('<li><a href="%s">%s</a></li>', $attachment['url'], $attachment['filename']);
            }

            $listItems .= '</ul>';
            $this->wrapper->WriteHTML($listItems);
        }

        $this->wrapper->Ln(10);
        $this->wrapper->Ln();
        $this->wrapper->SetFont($this->wrapper->getCurrentFont(), '', 9);
        $this->wrapper->Write(5, Translation::get('ad_entry_solution_id') . ': #' . $faqData['solution_id']);

        // Check if the author name should be visible, according to the GDPR option
        $currentUser = new CurrentUser($this->config);
        $author = $currentUser->getUserVisibilityByEmail($faqData['email']) ? $faqData['author'] : 'n/a';

        $this->wrapper->SetAuthor($author);
        $this->wrapper->Ln();
        $this->wrapper->Write(5, Translation::get('msgAuthor') . ': ' . $author);
        $this->wrapper->Ln();
        $this->wrapper->Write(5, Translation::get('msgLastUpdateArticle') . $date->format($faqData['date']));

        return $this->wrapper->Output($filename);
    }
}
