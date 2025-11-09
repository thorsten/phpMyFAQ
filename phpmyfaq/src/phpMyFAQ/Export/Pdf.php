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
 * Class PDF
 *
 * @package phpMyFAQ\Export
 */
class Pdf extends Export
{
    private readonly Wrapper $wrapper;

    private Tags $tags;

    private readonly CommonMarkConverter $commonMarkConverter;

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

        $this->tags = new Tags($this->config);

        $this->wrapper = new Wrapper();
        $this->wrapper->setConfig($this->config);

        // Set PDF options
        $this->wrapper->Open();
        $this->wrapper->SetDisplayMode(zoom: 'real');
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
        $this->wrapper->setCategories($this->category->getAllCategories());
        $this->wrapper->SetCreator($this->config->getTitle() . ' - ' . System::getPoweredByString());

        $faqData = $this->faq->get(
            queryType: 'faq_export_pdf',
            categoryId: $categoryId,
            downwards: $downwards,
            lang: $language,
        );

        $currentCategory = 0;

        foreach ($faqData as $faq) {
            $this->wrapper->AddPage();

            // Bookmark for categories
            if ($currentCategory !== $faq['category_id']) {
                $this->wrapper->Bookmark(
                    txt: html_entity_decode(
                        $this->category->getCategoryName((int) $faq['category_id']),
                        ENT_QUOTES,
                        encoding: 'utf-8',
                    ),
                    level: $this->category->getLevelOf((int) $faq['category_id']) - 1,
                    y: 0,
                );
            }

            // Bookmark for FAQs
            $this->wrapper->Bookmark(
                txt: html_entity_decode((string) $faq['topic'], ENT_QUOTES, encoding: 'utf-8'),
                level: $this->category->getLevelOf((int) $faq['category_id']),
                y: 0,
            );

            if ($this->tags instanceof Tags) {
                $tags = $this->tags->getAllTagsById((int) $faq['id']);
            }

            $this->wrapper->SetFont($this->wrapper->getCurrentFont(), style: 'b', size: 12);
            $this->wrapper->WriteHTML('<h1>' . $this->category->getCategoryName((int) $faq['category_id']) . '</h1>');
            $this->wrapper->WriteHTML('<h2>' . $faq['topic'] . '</h2>');
            $this->wrapper->Ln(h: 10);

            $this->wrapper->SetFont($this->wrapper->getCurrentFont(), style: '', size: 10);

            $content = $this->config->get(item: 'main.enableMarkdownEditor')
                ? trim($this->commonMarkConverter->convert($faq['content'])->getContent())
                : trim((string) $faq['content']);
            $this->wrapper->WriteHTML($content);

            $this->wrapper->Ln(h: 10);

            if (isset($faq['keywords'])) {
                $this->wrapper->Ln();
                $this->wrapper->Write(
                    h: 5,
                    txt: Translation::get(languageKey: 'msgNewContentKeywords') . ' ' . $faq['keywords'],
                );
            }

            if (isset($tags) && 0 !== (is_countable($tags) ? count($tags) : 0)) {
                $this->wrapper->Ln();
                $this->wrapper->Write(
                    h: 5,
                    txt: Translation::get(languageKey: 'msgTags')
                    . ': '
                    . implode(
                        separator: ', ',
                        array: $tags,
                    ),
                );
            }

            $this->wrapper->Ln();
            $this->wrapper->Ln();
            $this->wrapper->Write(
                h: 5,
                txt: Translation::get(languageKey: 'msgLastUpdateArticle') . Date::createIsoDate($faq['lastmodified']),
            );

            $currentCategory = $faq['category_id'];
        }

        // remove default header/footer
        $this->wrapper->setPrintHeader(val: false);
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
        if ($filename === null || $filename === '') {
            // Default filename: FAQ-<id>-<language>.pdf
            $name = 'FAQ-%s-%s.pdf';
            $filename = sprintf($name, $faqData['id'], $faqData['lang']);
        }

        $date = new Date($this->config);

        $this->wrapper->setFaq($faqData);
        $this->wrapper->setCategory($faqData['category_id']);
        $this->wrapper->setQuestion($faqData['title']);
        $this->wrapper->setCategories($this->category->getAllCategories());

        // Set any item
        $this->wrapper->SetTitle($faqData['title']);
        $this->wrapper->SetCreator($this->config->getTitle() . ' - ' . System::getPoweredByString());

        $this->wrapper->AddPage();
        $this->wrapper->SetFont($this->wrapper->getCurrentFont(), style: '', size: 10);
        $this->wrapper->SetDisplayMode(zoom: 'real');
        $this->wrapper->Ln();
        $this->wrapper->Ln();
        $this->wrapper->WriteHTML('<h3>' . $faqData['title'] . '</h3>');
        $this->wrapper->Ln(h: 5);
        $this->wrapper->Ln();

        $content = $this->config->get(item: 'main.enableMarkdownEditor')
            ? $this->commonMarkConverter->convert($faqData['content'])->getContent()
            : (string) $faqData['content'];
        $this->wrapper->WriteHTML($content);

        if (isset($faqData['attachmentList'])) {
            $this->wrapper->Ln(h: 10);
            $this->wrapper->Ln();
            $this->wrapper->Write(
                h: 5,
                txt: Translation::get(languageKey: 'msgAttachedFiles') . ':',
            );
            $this->wrapper->Ln(h: 5);
            $this->wrapper->Ln();
            $listItems = '<ul class="pb-4 mb-4 border-bottom">';
            foreach ($faqData['attachmentList'] as $attachment) {
                $list = '<li><a href="%s">%s</a></li>';
                $listItems .= sprintf($list, $attachment['url'], $attachment['filename']);
            }

            $listItems .= '</ul>';
            $this->wrapper->WriteHTML($listItems);
        }

        $this->wrapper->Ln(h: 10);
        $this->wrapper->Ln();
        $this->wrapper->SetFont($this->wrapper->getCurrentFont(), style: '', size: 9);
        $this->wrapper->Write(
            h: 5,
            txt: Translation::get(languageKey: 'ad_entry_solution_id') . ': #' . $faqData['solution_id'],
        );

        // Check if the author name should be visible, according to the GDPR option
        $currentUser = new CurrentUser($this->config);
        $author = $currentUser->getUserVisibilityByEmail($faqData['email']) ? $faqData['author'] : 'n/a';

        $this->wrapper->SetAuthor($author);
        $this->wrapper->Ln();
        $this->wrapper->Write(
            h: 5,
            txt: Translation::get(languageKey: 'msgAuthor') . ': ' . $author,
        );
        $this->wrapper->Ln();
        $this->wrapper->Write(
            h: 5,
            txt: Translation::get(languageKey: 'msgLastUpdateArticle') . $date->format($faqData['date']),
        );

        return $this->wrapper->Output($filename);
    }
}
