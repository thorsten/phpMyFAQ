<?php
/**
 * Abstract class for various services, e.g. Twitter, Facebook, Digg, ...
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Services
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-09-05
 */

/**
 * Services
 *
 * @category  phpMyFAQ
 * @package   Services
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
     * @var PMF_Configuration
     */
    private $_config;

    /**
     * Constructor
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Services
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
    }

    /**
     * Returns the current URL
     *
     * @return string
     */
    public function getLink()
    {
        $url = sprintf(
            '%sindex.php?action=artikel&amp;cat=%s&amp;id=%d&amp;artlang=%s',
            $this->_config->get('main.referenceURL'),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );

        $link = new PMF_Link($url, $this->_config);
        $link->itemTitle = $this->question;

        return urlencode($link->toString());
    }

    /**
     * Returns the current "Digg It!" URL
     *
     * @return string
     */
    public function getDiggLink()
    {
        $url = sprintf(
            '%sindex.php?action=artikel&amp;cat=%s&amp;id=%d&amp;artlang=%s&amp;title=%s',
            $this->_config->get('main.referenceURL'),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage(),
            $this->getQuestion()
        );

        $link = new PMF_Link($url, $this->_config);
        $link->itemTitle = $this->question;

        return sprintf('http://digg.com/submit?phase=2&amp;url=%s', urlencode($link->toString()));
    }

    /**
     * Returns the current "share on Facebook" URL
     *
     * @return string
     */
    public function getShareOnFacebookLink()
    {
        $url = sprintf(
            '%sindex.php?action=artikel&amp;cat=%s&amp;id=%d&amp;artlang=%s',
            $this->_config->get('main.referenceURL'),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );

        $link = new PMF_Link($url, $this->_config);
        $link->itemTitle = $this->question;

        return sprintf('https://www.facebook.com/sharer.php?u=%s', urlencode($link->toString()));
    }

    /**
     * Returns the current "share on Twitter" URL
     *
     * @return string
     */
    public function getShareOnTwitterLink()
    {
        $url = sprintf(
            '%sindex.php?action=artikel&amp;cat=%s&amp;id=%d&amp;artlang=%s',
            $this->_config->get('main.referenceURL'),
            $this->getCategoryId(),
            $this->getFaqId(),
            $this->getLanguage()
        );

        $link = new PMF_Link($url, $this->_config);
        $link->itemTitle = $this->question;

        return sprintf(
            'https://twitter.com/share?url=%s&amp;text=%s',
            urlencode($link->toString()),
            $this->getQuestion() . urlencode(' | ' . $url)
        );
    }

    /**
     * Returns the "Send 2 Friends" URL
     *
     * @return string
     */
    public function getSuggestLink()
    {
        return sprintf(
            '%s?action=send2friend&amp;cat=%d&amp;id=%d&amp;artlang=%s',
            $this->_config->get('main.referenceURL'),
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
        return sprintf(
            '%spdf.php?cat=%d&amp;id=%d&amp;artlang=%s',
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