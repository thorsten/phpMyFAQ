<?php

/**
 * Helper class for phpMyFAQ FAQs.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL wasn't distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-11-12
 */

namespace phpMyFAQ\Helper;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Link;
use phpMyFAQ\Utils;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FaqHelper
 *
 * @package phpMyFAQ\Helper
 */
class FaqHelper extends AbstractHelper
{
    /**
     * Constructor.
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Extends URL fragments (e.g. <a href="#foo">) with the full default URL.
     */
    public function rewriteUrlFragments(string $answer, string $currentUrl): string
    {
        return str_replace('href="#', 'href="' . $currentUrl . '#', $answer);
    }

    /**
     * Renders a select box with all translations of a FAQ.
     *
     * @todo This method should be moved to a Twig macro.
     * @deprecated Rewrite this method to use Twig, will be removed in v4.1
     */
    public function renderChangeLanguageSelector(Faq $faq, int $categoryId): string
    {
        $html = '';
        $faqUrl = sprintf(
            '?action=faq&cat=%d&id=%d&artlang=%%s',
            $categoryId,
            $faq->faqRecord['id']
        );

        $oLink = new Link($this->configuration->getDefaultUrl() . $faqUrl, $this->configuration);
        $oLink->itemTitle = $faq->faqRecord['title'];

        $availableLanguages = $this->configuration->getLanguage()->isLanguageAvailable($faq->faqRecord['id']);

        if ((is_countable($availableLanguages) ? count($availableLanguages) : 0) > 1) {
            $html = '<form method="post">';
            $html .= '<select class="form-select" name="language" ';
            $html .= 'onchange="top.location.href = this.options[this.selectedIndex].value;">';

            foreach ($availableLanguages as $availableLanguage) {
                $html .= sprintf('<option value="%s"', sprintf($oLink->toString(), $availableLanguage));
                $html .= ($faq->faqRecord['lang'] === $availableLanguage ? ' selected' : '');
                $html .= sprintf('>%s</option>', LanguageCodes::get($availableLanguage));
            }

            $html .= '</select></form>';
        }

        return $html;
    }

    /**
     * Renders a preview of the answer
     *
     * @param string $answer The answer to be previewed
     * @param int    $wordCount The number of words to display in the preview
     * @return string The preview of the answer
     * @throws CommonMarkException
     */
    public function renderAnswerPreview(string $answer, int $wordCount): string
    {
        if ($this->configuration->get('main.enableMarkdownEditor')) {
            $config = [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ];

            $environment = new Environment($config);
            $environment->addExtension(new CommonMarkCoreExtension());
            $environment->addExtension(new GithubFlavoredMarkdownExtension());

            $markdownConverter = new MarkdownConverter($environment);

            $cleanedAnswer = $markdownConverter->convert($answer)->getContent();
            return Utils::chopString(strip_tags($cleanedAnswer), $wordCount);
        }

        return Utils::chopString(strip_tags($answer), $wordCount);
    }

    /**
     * Creates an overview with all categories with their FAQs.
     */
    public function createOverview(Category $category, Faq $faq, string $language = ''): array
    {
        // Initialize categories
        $category->transform(0);

        // Get all FAQs
        $faq->getAllFaqs(FAQ_SORTING_TYPE_CATID_FAQID, ['lang' => $language, 'active' => 'yes']);

        return $faq->faqRecords;
    }

    /**
     * Returns the URL for a given FAQ Entity and category ID.
     */
    public function createFaqUrl(FaqEntity $faqEntity, int $categoryId): string
    {
        return sprintf(
            '%s?action=faq&cat=%d&id=%d&artlang=%s',
            $this->configuration->getDefaultUrl() . 'index.php',
            $categoryId,
            $faqEntity->getId(),
            $faqEntity->getLanguage()
        );
    }

    /**
     * Remove <script> tags, we don't need them
     */
    public function cleanUpContent(string $content): string
    {
        $contentLength = strlen($content);
        $allowedHosts = $this->configuration->getAllowedMediaHosts();
        $allowedHosts[] = Request::createFromGlobals()->getHost();
        $htmlSanitizer = new HtmlSanitizer(
            (new HtmlSanitizerConfig())
                ->withMaxInputLength($contentLength + 1)
                ->allowSafeElements()
                ->allowRelativeLinks()
                ->allowStaticElements()
                ->allowRelativeMedias()
                ->forceHttpsUrls($this->configuration->get('security.useSslOnly'))
                ->allowElement('iframe', ['title', 'src', 'width', 'height', 'allow', 'allowfullscreen'])
                ->allowMediaSchemes(['https', 'http', 'mailto', 'data'])
                ->allowMediaHosts($allowedHosts)
                ->allowLinkSchemes(['https', 'http', 'mailto', 'data'])
        );

        $sanitizedContent = $htmlSanitizer->sanitize($content);

        $sanitizedContent = preg_replace('/<iframe\b(?:(?!src)[^>])*>\s*<\/iframe>/i', '', $sanitizedContent);

        return preg_replace_callback(
            '/style\s*=\s*"([^"]*)"/i',
            function (array $matches): string {
                $styles = explode(';', $matches[1]);
                $filteredStyles = array_filter($styles, function (string $style): bool {
                    return stripos(trim($style), 'overflow:') !== 0; // Exclude 'overflow' properties
                });
                $newStyle = implode('; ', $filteredStyles);
                // Remove the style attribute if empty
                return $newStyle !== '' && $newStyle !== '0' ? 'style="' . $newStyle . '"' : '';
            },
            (string) $sanitizedContent
        );
    }

    /**
     * Converts old internal links to the current format
     * Formats from:
     * - http://<url>/index.php?action=artikel&cat=<category id>&id=<id>
     * - http://<url>/index.php?action=artikel&cat=<category id>&id=<id>&artlang=<language>
     * - http://<url>/index.php?action=faq&cat=<category id>&id=<id>
     * - http://<url>/index.php?action=faq&cat=<category id>&id=<id>&artlang=<language>
     * - supports also HTML encoded parameter (&#61; instead of =, & instead of &)
     *
     * to the new URL structure:
     * https://<url>/content/<category id>/<id>/<language>/<the question with underscores as spaces>.html
     */
    public function convertOldInternalLinks(string $question, string $answer): string
    {
        $link = new Link($this->configuration->getDefaultUrl(), $this->configuration);
        $pattern = '/(https?:\/\/[^\/]+)\/index\.php\?action=(artikel|faq)&cat=(\d+)&id=(\d+)(&artlang=([a-z]{2}))?/i';

        $decodedAnswer = html_entity_decode($answer);

        $result = preg_replace_callback(
            $pattern,
            function ($matches) use ($question, $link): string {
                $baseUrl = $this->configuration->getDefaultUrl();
                $categoryId = $matches[3];
                $faqId = $matches[4];
                $language = $matches[6] ?? $this->configuration->getLanguage()->getLanguage();

                return sprintf(
                    '%scontent/%d/%d/%s/%s.html',
                    $baseUrl,
                    $categoryId,
                    $faqId,
                    $language,
                    $link->getSEOItemTitle($question)
                );
            },
            $decodedAnswer
        );

        if ($result === $decodedAnswer && $decodedAnswer !== $answer) {
            $htmlEncodedPattern = '/(https?:\/\/[^\/]+)\/index\.php\?action(&#61;|=)(artikel|faq)(&|&)cat' .
                '(&#61;|=)(\d+)(&|&)id(&#61;|=)(\d+)((&|&)artlang(&#61;|=)([a-z]{2}))?/i';

            return preg_replace_callback(
                $htmlEncodedPattern,
                function ($matches) use ($question, $link): string {
                    $baseUrl = $this->configuration->getDefaultUrl();
                    $categoryId = $matches[6];
                    $faqId = $matches[9];
                    $language = $matches[13] ?? $this->configuration->getLanguage()->getLanguage();

                    return sprintf(
                        '%scontent/%d/%d/%s/%s.html',
                        $baseUrl,
                        $categoryId,
                        $faqId,
                        $language,
                        $link->getSEOItemTitle($question)
                    );
                },
                $answer
            );
        }

        return $result;
    }
}
