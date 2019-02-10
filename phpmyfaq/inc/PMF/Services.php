<?php

/**
 * Abstract class for various services, e.g. Twitter, Facebook.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2010-09-05
 */

/**
 * Services.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2010-09-05
 */
class PMF_Services
{
    /**
     * FAQ ID.
     *
     * @var int
     */
    protected $faqId;

    /**
     * Category ID.
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
     * @var PMF_Configuration
     */
    private $config;

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Services
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Returns the current URL.
     *
     * @return string
     */
    public function getLink()
    {
        $url = sprintf(
            '%sindex.php?action=artikel&cat=%s&id=%d&artlang=%s',
            $this->config->getDefaultUrl(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );

        $link = new PMF_Link($url, $this->config);
        $link->itemTitle = $this->question;

        return urlencode($link->toString());
    }

    /**
     * Returns the current "share on Facebook" URL.
     *
     * @return string
     */
    public function getShareOnFacebookLink()
    {
        $url = sprintf(
            '%sindex.php?action=artikel&cat=%s&id=%d&artlang=%s',
            $this->config->getDefaultUrl(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );

        $link = new PMF_Link($url, $this->config);
        $link->itemTitle = $this->question;

        return sprintf('https://www.facebook.com/sharer.php?u=%s', urlencode($link->toString()));
    }

    /**
     * Returns the current "share on Twitter" URL.
     *
     * @return string
     */
    public function getShareOnTwitterLink()
    {
        $url = sprintf(
            '%sindex.php?action=artikel&cat=%s&id=%d&artlang=%s',
            $this->config->getDefaultUrl(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );

        $link = new PMF_Link($url, $this->config);
        $link->itemTitle = $this->question;

        return sprintf(
            'https://twitter.com/share?url=%s&text=%s',
            urlencode($link->toString()),
            $this->getQuestion().urlencode(' | '.$link->toString())
        );
    }

    /**
     * Returns the "Send 2 Friends" URL.
     *
     * @return string
     */
    public function getSuggestLink()
    {
        return sprintf(
            '%s?action=send2friend&cat=%d&id=%d&artlang=%s',
            $this->config->getDefaultUrl(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );
    }

    /**
     * Returns the "Show FAQ as PDF" URL.
     *
     * @return string
     */
    public function getPdfLink()
    {
        return sprintf(
            '%spdf.php?cat=%d&id=%d&artlang=%s',
            PMF_Link::getSystemRelativeUri('index.php'),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );
    }

    /**
     * Returns the "Show FAQ as PDF" URL.
     *
     * @return string
     */
    public function getPdfApiLink()
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
     * @param int $categoryId
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int $faqId
     */
    public function setFaqId($faqId)
    {
        $this->faqId = $faqId;
    }

    /**
     * @return int
     */
    public function getFaqId()
    {
        return $this->faqId;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $question
     */
    public function setQuestion($question)
    {
        $this->question = $question;
    }

    /**
     * @return string
     */
    public function getQuestion()
    {
        return urlencode(trim($this->question));
    }
}
