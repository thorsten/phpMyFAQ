<?php

namespace phpMyFAQ\Helper;

/**
 * Helper class for phpMyFAQ FAQs.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-11-12
 */

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use phpMyFAQ\Faq;
use phpMyFAQ\Helper;
use phpMyFAQ\Link;
use phpMyFAQ\Utils;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Helper_Faq.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-11-12
 */
class FaqHelper extends Helper
{
    /**
     * SSL enabled.
     *
     * @var bool
     */
    private $ssl = false;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->_config = $config;
    }

    /**
     * Sets SSL mode.
     *
     * @param bool $ssl
     */
    public function setSsl($ssl)
    {
        $this->ssl = $ssl;
    }

    /**
     * Returns current SSL mode.
     *
     * @return bool
     */
    public function getSsl()
    {
        return $this->ssl;
    }

    /**
     * Renders a Facebook Like button.
     *
     * @param string $url
     *
     * @return string
     */
    public function renderFacebookLikeButton($url)
    {
        if (empty($url) || $this->_config->get('socialnetworks.enableFacebookSupport') == false) {
            return '';
        }

        if ($this->ssl) {
            $http = 'https://';
        } else {
            $http = 'http://';
        }

        return sprintf(
            '<iframe src="%sfacebook.com/plugins/like.php?href=%s&amp;layout=standard&amp;show_faces=true&amp;width=250&amp;action=like&amp;font=arial&amp;colorscheme=light&amp;height=30" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:250px; height:30px;" allowTransparency="true"></iframe>',
            $http,
            urlencode($url)
        );
    }

    /**
     * Renders a Share on Facebook link.
     *
     * @param string $url
     *
     * @return string
     */
    public function renderFacebookShareLink($url)
    {
        if (empty($url) || $this->_config->get('socialnetworks.disableAll') === true) {
            return '';
        }

        $icon = '<span class="fa-stack fa-lg">
                        <i aria-hidden="true" class="fa fa-square-o fa-stack-2x"></i>
                        <i aria-hidden="true" class="fa fa-facebook fa-stack-1x"></i>
                    </span>';

        return sprintf(
            '<a href="%s" target="_blank">%s</a>',
            $url,
            $icon
        );
    }

    /**
     * Renders a Share on Twitter link.
     *
     * @param string $url
     *
     * @return string
     */
    public function renderTwitterShareLink($url)
    {
        if (empty($url) || $this->_config->get('socialnetworks.disableAll') === true) {
            return '';
        }

        $icon = '<span class="fa-stack fa-lg">
                        <i aria-hidden="true" class="fa fa-square-o fa-stack-2x"></i>
                        <i aria-hidden="true" class="fa fa-twitter fa-stack-1x"></i>
                    </span>';

        return sprintf(
            '<a href="%s" target="_blank">%s</a>',
            $url,
            $icon
        );
    }

    /**
     * Renders a select box with all translations of a FAQ.
     *
     * @param Faq $faq
     * @param int     $categoryId
     *
     * @return string
     */
    public function renderChangeLanguageSelector(Faq $faq, $categoryId)
    {
        global $languageCodes;

        $html = '';
        $faqUrl = sprintf(
            '?action=faq&amp;cat=%d&amp;id=%d&amp;artlang=%%s',
            $categoryId,
            $faq->faqRecord['id']
        );

        $oLink = new Link(Link::getSystemRelativeUri().$faqUrl, $this->_config);
        $oLink->itemTitle = $faq->faqRecord['title'];
        $availableLanguages = $this->_config->getLanguage()->languageAvailable($faq->faqRecord['id']);

        if (count($availableLanguages) > 1) {
            $html = '<form method="post">';
            $html .= '<select name="language" onchange="top.location.href = this.options[this.selectedIndex].value;">';

            foreach ($availableLanguages as $language) {
                $html .= sprintf('<option value="%s"', sprintf($oLink->toString(), $language));
                $html .= ($faq->faqRecord['lang'] === $language ? ' selected' : '');
                $html .= sprintf('>%s</option>', $languageCodes[strtoupper($language)]);
            }

            $html .= '</select></form>';
        }

        return $html;
    }

    /**
     * Renders a preview of the answer.
     *
     * @param string $answer
     * @param integer $numWords
     * @return string
     */
    public function renderAnswerPreview($answer, $numWords)
    {
        if ($this->_config->get('main.enableMarkdownEditor')) {
            $parseDown = new ParsedownExtra();
            return Utils::chopString(strip_tags($parseDown->text($answer)), $numWords);
        } else {
            return Utils::chopString(strip_tags($answer), $numWords);
        }
    }

    /**
     * Creates an overview with all categories with their FAQs.
     *
     * @param PMF_Category $category
     * @param PMF_Faq      $faq
     * @param string       $language
     *
     * @return array
     */
    public function createOverview(Category $category, Faq $faq, $language = '')
    {
        global $PMF_LANG;

        $output = '';

        // Initialize categories
        $category->transform(0);

        // Get all FAQs
        $faq->getAllRecords(FAQ_SORTING_TYPE_CATID_FAQID, ['lang' => $language]);
        $date = new Date($this->_config);
        
        if (count($faq->faqRecords)) {
            $lastCategory = 0;
            foreach ($faq->faqRecords as $data) {
                if ($data['category_id'] !== $lastCategory) {
                    $output .= sprintf('<h3>%s</h3>', $category->getPath($data['category_id'], ' &raquo; '));
                }

                $output .= sprintf('<h4>%s</h4>', strip_tags($data['title']));
                $output .= sprintf('<article>%s</article>', $data['content']);
                $output .= sprintf('<p>%s: %s<br>%s',
                    $PMF_LANG['msgAuthor'],
                    $data['author'],
                    $PMF_LANG['msgLastUpdateArticle'].$date->format($data['updated'])
                );
                $output .= '<hr>';

                $lastCategory = $data['category_id'];
            }
        }

        return $output;
    }
}
