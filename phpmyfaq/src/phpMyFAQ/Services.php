<?php

/**
 * Abstract class for various services, e.g. Twitter, Facebook.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link  https://www.phpmyfaq.de
 * @since 2010-09-05
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
     *
     * @var int
     */
    protected $faqId;

    /**
     * Entity ID.
     *
     * @var int
     */
    protected $categoryId;

    /**
     * Language.
     *
     * @var string
     */
    protected $language;

    /**
     * Question of the FAQ.
     *
     * @var string
     */
    protected $question;

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
    }

    /**
     * Returns the current URL.
     */
    public function getLink(): string
    {
        $url = sprintf(
            '%sindex.php?action=faq&cat=%s&id=%d&artlang=%s',
            $this->config->getDefaultUrl(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );

        $link = new Link($url, $this->config);
        $link->itemTitle = $this->question;

        return urlencode($link->toString());
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     */
    public function setCategoryId($categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function getFaqId(): int
    {
        return $this->faqId;
    }

    /**
     * @param int $faqId
     */
    public function setFaqId($faqId): void
    {
        $this->faqId = $faqId;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language): void
    {
        $this->language = $language;
    }

    /**
     * Returns the current "share on Twitter" URL.
     */
    public function getShareOnTwitterLink(): string
    {
        $url = sprintf(
            '%sindex.php?action=faq&cat=%s&id=%d&artlang=%s',
            $this->config->getDefaultUrl(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );

        $link = new Link($url, $this->config);
        $link->itemTitle = $this->question;

        return sprintf(
            'https://twitter.com/share?url=%s&text=%s',
            urlencode($link->toString()),
            $this->getQuestion() . urlencode(' | ' . $link->toString())
        );
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
     * Returns the "Send 2 Friends" URL.
     */
    public function getSuggestLink(): string
    {
        return sprintf(
            '%sindex.php?action=send2friend&cat=%d&id=%d&artlang=%s',
            $this->config->getDefaultUrl(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );
    }

    /**
     * Returns the "Show FAQ as PDF" URL.
     */
    public function getPdfLink(): string
    {
        return sprintf(
            '%spdf.php?cat=%d&id=%d&artlang=%s',
            $this->config->getDefaultUrl(),
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
            $this->config->getDefaultUrl(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );
    }
}
