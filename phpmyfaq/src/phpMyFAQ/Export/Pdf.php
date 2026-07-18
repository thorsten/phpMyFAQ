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
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-10-07
 */

declare(strict_types=1);

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
        $this->wrapper->SetCreator($this->config->getTitle() . ' - ' . System::getPoweredByPlainString());

        $faqData = $this->faq->get(
            queryType: 'faq_export_pdf',
            categoryId: $categoryId,
            downwards: $downwards,
            lang: $language,
        );

        $currentCategory = 0;

        foreach ($faqData as $faq) {
            if (!is_array($faq)) {
                continue;
            }

            $faqId = (int) ($faq['id'] ?? 0);
            $faqCategoryId = (int) ($faq['category_id'] ?? 0);
            $topic = (string) ($faq['topic'] ?? '');

            $this->wrapper->AddPage();

            // Bookmark for categories
            if ($currentCategory !== $faqCategoryId) {
                $this->wrapper->Bookmark(
                    txt: html_entity_decode(
                        $this->category->getCategoryName($faqCategoryId),
                        ENT_QUOTES,
                        encoding: 'utf-8',
                    ),
                    level: $this->category->getLevelOf($faqCategoryId) - 1,
                    y: 0,
                );
            }

            // Bookmark for FAQs
            $this->wrapper->Bookmark(
                txt: html_entity_decode($topic, ENT_QUOTES, encoding: 'utf-8'),
                level: $this->category->getLevelOf($faqCategoryId),
                y: 0,
            );

            $tags = $this->tags->getAllTagsById($faqId);

            $this->wrapper->SetFont($this->wrapper->getCurrentFont(), style: 'b', size: 12);
            $this->wrapper->WriteHTML('<h1>' . $this->category->getCategoryName($faqCategoryId) . '</h1>');
            $this->wrapper->WriteHTML('<h2>' . $topic . '</h2>');
            $this->wrapper->Ln(h: 10);

            $this->wrapper->SetFont($this->wrapper->getCurrentFont(), style: '', size: 10);

            $content = $this->config->get(item: 'main.enableMarkdownEditor')
                ? trim($this->commonMarkConverter->convert((string) ($faq['content'] ?? ''))->getContent())
                : trim((string) ($faq['content'] ?? ''));
            $this->wrapper->WriteHTML($content);

            $this->wrapper->Ln(h: 10);

            if (array_key_exists('keywords', $faq)) {
                $this->wrapper->Ln();
                $this->wrapper->Write(
                    h: 5,
                    txt: Translation::getString(key: 'msgNewContentKeywords') . ' ' . (string) $faq['keywords'],
                );
            }

            if ($tags !== []) {
                $this->wrapper->Ln();
                $this->wrapper->Write(
                    h: 5,
                    txt: Translation::getString(key: 'msgTags') . ': ' . implode(separator: ', ', array: $tags),
                );
            }

            $this->wrapper->Ln();
            $this->wrapper->Ln();
            $this->wrapper->Write(
                h: 5,
                txt: Translation::getString(key: 'msgLastUpdateArticle')
                    . Date::createIsoDate((string) ($faq['lastmodified'] ?? '')),
            );

            $currentCategory = $faqCategoryId;
        }

        // remove default header/footer
        $this->wrapper->setPrintHeader(val: false);
        $this->wrapper->addFaqToc();

        return $this->wrapper->Output($filename, 'S');
    }

    /**
     * Builds the PDF delivery for the given FAQ.
     *
     * @param array<string, mixed> $faqData
     * @throws CommonMarkException
     * @throws Exception
     */
    public function generateFile(array $faqData, ?string $filename = null): string
    {
        $title = (string) ($faqData['title'] ?? '');

        if ($filename === null || $filename === '') {
            // Default filename: FAQ-<id>-<language>.pdf
            $name = 'FAQ-%s-%s.pdf';
            $filename = sprintf($name, (string) ($faqData['id'] ?? ''), (string) ($faqData['lang'] ?? ''));
        }

        $date = new Date($this->config);

        $this->wrapper->setFaq($faqData);
        $this->wrapper->setCategory((int) ($faqData['category_id'] ?? 0));
        $this->wrapper->setQuestion($title);
        $this->wrapper->setCategories($this->category->getAllCategories());

        // Set any item
        $this->wrapper->SetTitle($title);
        $this->wrapper->SetCreator($this->config->getTitle() . ' - ' . System::getPoweredByPlainString());

        $this->wrapper->AddPage();
        $this->wrapper->SetFont($this->wrapper->getCurrentFont(), style: '', size: 10);
        $this->wrapper->SetDisplayMode(zoom: 'real');
        $this->wrapper->Ln();
        $this->wrapper->Ln();
        $this->wrapper->WriteHTML('<h3>' . $title . '</h3>');
        $this->wrapper->Ln(h: 5);
        $this->wrapper->Ln();

        $content = $this->config->get(item: 'main.enableMarkdownEditor')
            ? $this->commonMarkConverter->convert((string) ($faqData['content'] ?? ''))->getContent()
            : (string) ($faqData['content'] ?? '');
        $this->wrapper->WriteHTML($content);

        $attachmentList = $faqData['attachmentList'] ?? null;
        if (is_array($attachmentList)) {
            $this->wrapper->Ln(h: 10);
            $this->wrapper->Ln();
            $this->wrapper->Write(h: 5, txt: Translation::getString(key: 'msgAttachedFiles') . ':');
            $this->wrapper->Ln(h: 5);
            $this->wrapper->Ln();
            $listItems = '<ul class="pb-4 mb-4 border-bottom">';
            foreach ($attachmentList as $attachment) {
                if (!is_array($attachment)) {
                    continue;
                }

                $list = '<li><a href="%s">%s</a></li>';
                $listItems .= sprintf(
                    $list,
                    (string) ($attachment['url'] ?? ''),
                    (string) ($attachment['filename'] ?? ''),
                );
            }

            $listItems .= '</ul>';
            $this->wrapper->WriteHTML($listItems);
        }

        $this->wrapper->Ln(h: 10);
        $this->wrapper->Ln();
        $this->wrapper->SetFont($this->wrapper->getCurrentFont(), style: '', size: 9);
        $this->wrapper->Write(
            h: 5,
            txt: Translation::getString(key: 'ad_entry_solution_id') . ': #' . (string) ($faqData['solution_id'] ?? ''),
        );

        // Check if the author name should be visible, according to the GDPR option
        $currentUser = new CurrentUser($this->config);
        $author = $currentUser->getUserVisibilityByEmail((string) ($faqData['email'] ?? ''))
            ? (string) ($faqData['author'] ?? '')
            : 'n/a';

        $this->wrapper->SetAuthor($author);
        $this->wrapper->Ln();
        $this->wrapper->Write(h: 5, txt: Translation::getString(key: 'msgAuthor') . ': ' . $author);
        $this->wrapper->Ln();
        $this->wrapper->Write(
            h: 5,
            txt: Translation::getString(key: 'msgLastUpdateArticle') . $date->format((string) ($faqData['date'] ?? '')),
        );

        return $this->wrapper->Output($filename, 'S');
    }
}
