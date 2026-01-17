<?php

/**
 * Service for translating content across different entity types.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-17
 */

namespace phpMyFAQ\Translation;

use phpMyFAQ\Configuration;
use phpMyFAQ\Translation\DTO\TranslationRequest;
use phpMyFAQ\Translation\DTO\TranslationResult;
use phpMyFAQ\Translation\Exception\TranslationException;

/**
 * Class ContentTranslationService
 *
 * High-level service for translating different content types (FAQ, Custom Pages, Categories, News).
 */
class ContentTranslationService
{
    private ?TranslationProviderInterface $provider = null;

    /**
     * Constructor.
     *
     * @param Configuration $configuration phpMyFAQ configuration
     */
    public function __construct(
        private readonly Configuration $configuration,
    ) {
        $this->provider = $this->configuration->getTranslationProvider();
    }

    /**
     * Translate FAQ fields (question, answer, keywords).
     *
     * @param TranslationRequest $request Translation request
     * @return TranslationResult Translation result
     * @throws TranslationException
     */
    public function translateFaq(TranslationRequest $request): TranslationResult
    {
        if (!$this->provider) {
            throw new TranslationException('No translation provider configured');
        }

        $fields = $request->getFields();
        $result = [];

        // Translate question (plain text)
        if (isset($fields['question']) && !empty($fields['question'])) {
            $result['question'] = $this->provider->translate(
                $fields['question'],
                $request->getSourceLang(),
                $request->getTargetLang(),
                false,
            );
        }

        // Translate answer (HTML content)
        if (isset($fields['answer']) && !empty($fields['answer'])) {
            $result['answer'] = $this->provider->translate(
                $fields['answer'],
                $request->getSourceLang(),
                $request->getTargetLang(),
                true, // Preserve HTML
            );
        }

        // Translate keywords (plain text)
        if (isset($fields['keywords']) && !empty($fields['keywords'])) {
            $result['keywords'] = $this->provider->translate(
                $fields['keywords'],
                $request->getSourceLang(),
                $request->getTargetLang(),
                false,
            );
        }

        return new TranslationResult($result, true);
    }

    /**
     * Translate Custom Page fields (pageTitle, content, seoTitle, seoDescription).
     *
     * @param TranslationRequest $request Translation request
     * @return TranslationResult Translation result
     * @throws TranslationException
     */
    public function translateCustomPage(TranslationRequest $request): TranslationResult
    {
        if (!$this->provider) {
            throw new TranslationException('No translation provider configured');
        }

        $fields = $request->getFields();
        $result = [];

        // Translate page title
        if (isset($fields['pageTitle']) && !empty($fields['pageTitle'])) {
            $result['pageTitle'] = $this->provider->translate(
                $fields['pageTitle'],
                $request->getSourceLang(),
                $request->getTargetLang(),
                false,
            );
        }

        // Translate content (HTML)
        if (isset($fields['content']) && !empty($fields['content'])) {
            $result['content'] = $this->provider->translate(
                $fields['content'],
                $request->getSourceLang(),
                $request->getTargetLang(),
                true, // Preserve HTML
            );
        }

        // Translate SEO title
        if (isset($fields['seoTitle']) && !empty($fields['seoTitle'])) {
            $result['seoTitle'] = $this->provider->translate(
                $fields['seoTitle'],
                $request->getSourceLang(),
                $request->getTargetLang(),
                false,
            );
        }

        // Translate SEO description
        if (isset($fields['seoDescription']) && !empty($fields['seoDescription'])) {
            $result['seoDescription'] = $this->provider->translate(
                $fields['seoDescription'],
                $request->getSourceLang(),
                $request->getTargetLang(),
                false,
            );
        }

        return new TranslationResult($result, true);
    }

    /**
     * Translate Category fields (name, description).
     *
     * @param TranslationRequest $request Translation request
     * @return TranslationResult Translation result
     * @throws TranslationException
     */
    public function translateCategory(TranslationRequest $request): TranslationResult
    {
        if (!$this->provider) {
            throw new TranslationException('No translation provider configured');
        }

        $fields = $request->getFields();
        $result = [];

        // Translate name
        if (isset($fields['name']) && !empty($fields['name'])) {
            $result['name'] = $this->provider->translate(
                $fields['name'],
                $request->getSourceLang(),
                $request->getTargetLang(),
                false,
            );
        }

        // Translate description
        if (isset($fields['description']) && !empty($fields['description'])) {
            $result['description'] = $this->provider->translate(
                $fields['description'],
                $request->getSourceLang(),
                $request->getTargetLang(),
                false,
            );
        }

        return new TranslationResult($result, true);
    }

    /**
     * Translate News fields (header, message, linkTitle).
     *
     * @param TranslationRequest $request Translation request
     * @return TranslationResult Translation result
     * @throws TranslationException
     */
    public function translateNews(TranslationRequest $request): TranslationResult
    {
        if (!$this->provider) {
            throw new TranslationException('No translation provider configured');
        }

        $fields = $request->getFields();
        $result = [];

        // Translate header
        if (isset($fields['header']) && !empty($fields['header'])) {
            $result['header'] = $this->provider->translate(
                $fields['header'],
                $request->getSourceLang(),
                $request->getTargetLang(),
                false,
            );
        }

        // Translate message
        if (isset($fields['message']) && !empty($fields['message'])) {
            $result['message'] = $this->provider->translate(
                $fields['message'],
                $request->getSourceLang(),
                $request->getTargetLang(),
                false,
            );
        }

        // Translate link title
        if (isset($fields['linkTitle']) && !empty($fields['linkTitle'])) {
            $result['linkTitle'] = $this->provider->translate(
                $fields['linkTitle'],
                $request->getSourceLang(),
                $request->getTargetLang(),
                false,
            );
        }

        return new TranslationResult($result, true);
    }
}
