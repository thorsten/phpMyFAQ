<?php
/**
 * Helper class for phpMyFAQ FAQs
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-11-12
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Helper_Faq
 *
 * @category  phpMyFAQ
 * @package   Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-11-12
 */
class PMF_Helper_Faq extends PMF_Helper
{
    /**
     * SSL enabled
     *
     * @var boolean
     */
    private $ssl = false;

    /**
     * Constructor
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Helper_Faq
     */
    public  function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
    }

    /**
     * Sets SSL mode
     *
     * @param boolean $ssl
     * @return void
     */
    public function setSsl($ssl)
    {
        $this->ssl = $ssl;
    }

    /**
     * Returns current SSL mode
     *
     * @return boolean
     */
    public function getSsl()
    {
        return $this->ssl;
    }

    /**
     * Renders a Facebook Like button
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
     * Renders a Share on Facebook link
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

        return sprintf(
            '<a href="%s" target="_blank"><i class="fa fa-facebook-square fa-4x"></i></a>',
            $url
        );
    }

    /**
     * Renders a Share on Twitter link
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

        return sprintf(
            '<a href="%s" target="_blank"><i class="fa fa-twitter-square fa-4x"></i></a>',
            $url
        );
    }

    /**
     * Renders a select box with all translations of a FAQ
     * @param PMF_Faq $faq
     * @param integer $categoryId
     * @return string
     */
    public function renderChangeLanguageSelector(PMF_Faq $faq, $categoryId)
    {
        global $languageCodes;

        $html   = '';
        $faqUrl = sprintf(
            '?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%%s',
            $categoryId,
            $faq->faqRecord['id']
        );

        $oLink              = new PMF_Link(PMF_Link::getSystemRelativeUri() . $faqUrl, $this->_config);
        $oLink->itemTitle   = $faq->faqRecord['title'];
        $availableLanguages = $this->_config->getLanguage()->languageAvailable($faq->faqRecord['id']);

        if (count($availableLanguages) > 1) {

            $html  = '<form method="post">';
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
     * Creates an overview with all categories with their FAQs
     *
     * @param PMF_Category $category
     * @param PMF_Faq      $faq
     * @param string       $language
     *
     * @return array
     */
    public function createOverview(PMF_Category $category, PMF_Faq $faq, $language = '')
    {
        global $PMF_LANG;

        $output = '';

        // Initialize categories
        $category->transform(0);

        // Get all FAQs
        $faqData = $faq->get(FAQ_QUERY_TYPE_EXPORT_XHTML, 0, true, $language);

        if (count($faqData)) {
            $lastCategory = 0;
            foreach ($faqData as $data) {

                if ($data['category_id'] !== $lastCategory) {
                    $output .= sprintf('<h3>%s</h3>', $category->getPath($data['category_id'], ' &raquo; '));
                }

                $output .= sprintf('<h4>%s</h4>', strip_tags($data['topic']));
                $output .= sprintf('<article>%s</article>', $data['content']);
                $output .= sprintf('<p>%s: %s<br>%s',
                    $PMF_LANG['msgAuthor'],
                    $data['author_name'],
                    $PMF_LANG['msgLastUpdateArticle'] . PMF_Date::createIsoDate($data['lastmodified'])
                );
                $output .= '<hr>';

                $lastCategory = $data['category_id'];
            }
        }

        return $output;
    }
}
