<?php

/**
 * Abstract class for 3rd party services, e.g., Gravatar.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-09-05
 */

namespace phpMyFAQ;

/**
 * Class Services
 *
 * @package phpMyFAQ
 */
class Services
{
    /**
     * FAQ ID.
     */
    protected int $faqId;

    /**
     * Entity ID.
     */
    protected int $categoryId;

    /**
     * Language.
     */
    protected string $language;

    /**
     * Question of the FAQ.
     */
    protected string $question;

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $configuration)
    {
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function setCategoryId(int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function getFaqId(): int
    {
        return $this->faqId;
    }

    public function setFaqId(int $faqId): void
    {
        $this->faqId = $faqId;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function getQuestion(): string
    {
        return urlencode(trim($this->question));
    }

    public function setQuestion(string $question): void
    {
        $this->question = $question;
    }

    /**
     * Returns the "Show FAQ as PDF" URL.
     */
    public function getPdfLink(): string
    {
        return sprintf(
            '%spdf.php?cat=%d&id=%d&artlang=%s',
            $this->configuration->getDefaultUrl(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );
    }

    /**
     * Returns the "Show FAQ as PDF" URL.
     */
    public function getPdfApiLink(): string
    {
        return sprintf(
            '%spdf.php?cat=%d&id=%d&artlang=%s',
            $this->configuration->getDefaultUrl(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );
    }
}
