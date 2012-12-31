<?php
/**
 * Abstract class for various services, e.g. Twitter, Facebook, Digg, ...
 * 
 * PHP version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   PMF_Services
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-09-05
 */

/**
 * PMF_Services
 * 
 * @category  phpMyFAQ
 * @package   PMF_Services
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-09-05
 */
class PMF_Services
{
    /**
     * FAQ ID
     *
     * @var integer
     */
    protected $faqId;

    /**
     * Category ID
     *
     * @var integer
     */
    protected $categoryId;

    /**
     * Language
     *
     * @var string
     */
    protected $language;

    /**
     * Question of the FAQ
     *
     * @var string 
     */
    protected $question;

    /**
     * Returns the current URL
     *
     * @return string
     */
    public function getLink()
    {
        $url = sprintf('%s?cat=%s&amp;id=%d&amp;lang=%s',
            PMF_Link::getSystemUri(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );

        return urlencode($url);
    }

    /**
     * Returns the current "Digg It!" URL
     *
     * @return string
     */
    public function getDiggLink()
    {
        $url = sprintf('%s?cat=%s&amp;id=%d&amp;lang=%s&amp;title=%s',
            PMF_Link::getSystemUri(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage(),
            $this->getQuestion()
        );

        return sprintf('http://digg.com/submit?phase=2&amp;url=%s', urlencode($url));
    }

    /**
     * Returns the current "share on Facebook" URL
     *
     * @return string
     */
    public function getShareOnFacebookLink()
    {
        $url = sprintf('%s?cat=%s&amp;id=%d&amp;lang=%s',
            PMF_Link::getSystemUri(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );

        return sprintf('http://www.facebook.com/sharer.php?u=%s', urlencode($url));
    }

    /**
     * Returns the current "share on Twitter" URL
     *
     * @return string
     */
    public function getShareOnTwitterLink()
    {
        $url = sprintf('%s?cat=%s&id=%d&lang=%s',
            PMF_Link::getSystemUri(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );

        return sprintf(
            'http://twitter.com/share?url=%s&text=%s',
            urlencode($url),
            $this->getQuestion() . urlencode(' | ' . $url)
        );
    }

    /**
     * Returns the current "Bookmark this on Delicious" URL
     *
     * @return string
     */
    public function getBookmarkOnDeliciousLink()
    {
        $url = sprintf('%s?cat=%s&amp;id=%d&amp;lang=%s',
            PMF_Link::getSystemUri(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );

        return sprintf('http://www.delicious.com/save?url=%s&amp;title=%s',
            urlencode($url),
            $this->getQuestion()
        );
    }

    /**
     * Returns the "Send 2 Friends" URL
     *
     * @return string
     */
    public function getSuggestLink()
    {
        return sprintf('%s?action=send2friend&amp;cat=%d&amp;id=%d&amp;artlang=%s',
            PMF_Link::getSystemUri(),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );
    }

    /**
     * Returns the "Show FAQ as PDF" URL
     *
     * @return string
     */
    public function getPdfLink()
    {
        return sprintf('%spdf.php?cat=%d&amp;id=%d&amp;artlang=%s',
            PMF_Link::getSystemRelativeUri('index.php'),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );
    }




    /**
     * @param integer $categoryId
     *
     * @return void
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param integer $faqId
     *
     * @return void
     */
    public function setFaqId($faqId)
    {
        $this->faqId = $faqId;
    }

    /**
     * @return integer
     */
    public function getFaqId()
    {
        return $this->faqId;
    }

    /**
     * @param string $language
     *
     * @return void
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
     *
     * @return void
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