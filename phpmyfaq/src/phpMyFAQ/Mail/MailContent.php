<?php

/**
 * The content of an e-mail: either plain text only, or HTML with a plain text alternative.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Mail;

final readonly class MailContent
{
    /**
     * @param string $text Plain text, never markup
     * @param string $html HTML, empty for a plain text mail
     */
    private function __construct(
        public string $text,
        public string $html,
    ) {
    }

    /**
     * A mail that is sent as text/plain only.
     */
    public static function plainText(string $text): self
    {
        return new self($text, html: '');
    }

    /**
     * A mail that is sent as multipart/alternative. Without a text alternative of the caller,
     * the plain text part is rendered from the HTML.
     */
    public static function html(string $html, string $textAlternative = ''): self
    {
        if ($html === '') {
            return new self($textAlternative, html: '');
        }

        return new self($textAlternative === '' ? self::renderAsText($html) : $textAlternative, $html);
    }

    /**
     * Rebuilds the content of an envelope that was prepared without its parts, e.g. a mail
     * queued by an earlier version. A multipart body cannot be taken apart again and is
     * handed over as it is.
     */
    public static function fromPreparedBody(string $contentType, string $body): self
    {
        if (str_starts_with(strtolower(trim($contentType)), 'text/html')) {
            return self::html($body);
        }

        return self::plainText($body);
    }

    public function isHtml(): bool
    {
        return $this->html !== '';
    }

    /**
     * Renders HTML as readable plain text: line breaks and blocks become line feeds,
     * links keep their target, entities are decoded and no markup is left.
     */
    private static function renderAsText(string $html): string
    {
        $text = (string) preg_replace('{<(head|style|script)\b.*?</\1>}is', '', $html);
        $text = (string) preg_replace_callback(
            '{<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)</a>}is',
            static function (array $matches): string {
                $target = (string) preg_replace('/^mailto:/i', '', $matches[2]);
                $label = trim(strip_tags($matches[3]));

                return $label === '' || $label === $target ? $target : $label . ' (' . $target . ')';
            },
            $text,
        );
        $text = (string) preg_replace('{<br\b[^>]*>\r?\n?|<hr\b[^>]*>|</(li|tr|dt|dd)>}i', "\n", $text);
        $text = (string) preg_replace('{<li\b[^>]*>}i', '- ', $text);
        $text = (string) preg_replace('{</(p|div|h[1-6]|ul|ol|table|blockquote|pre)>}i', "\n\n", $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, encoding: 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = (string) preg_replace('/[ \t]+/', ' ', $text);
        $text = (string) preg_replace('/ ?\n ?/', "\n", $text);
        $text = (string) preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}
