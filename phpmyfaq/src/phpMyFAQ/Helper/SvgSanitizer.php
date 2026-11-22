<?php

/**
 * SVG Sanitizer to prevent XSS attacks
 *
 * Only well-formed XML is accepted: a lenient parser can recover active
 * content from a malformed file that the patterns below do not recognise.
 * Dangerous nodes are removed from the parsed document and the sanitized
 * file is serialised from that document, never from the uploaded string.
 * The patterns then verify the serialised result, so anything the removal
 * missed makes the sanitizer reject the file instead of storing it.
 *
 * Decodes all HTML/XML entities before pattern matching to prevent
 * encoding-based bypasses (e.g., &#106;&#97;&#118;&#97;... → javascript:).
 * Custom entities declared in a DOCTYPE internal subset
 * (<!ENTITY j "javascript">) are expanded as well, and the declaring
 * DOCTYPE is rejected, so &j;:alert() cannot slip past the patterns.
 * A tag split around a token that would itself be removed
 * (<scr<?php?>ipt>) is not well-formed XML and is rejected.
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
 * @since     2026-01-15
 */

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class SvgSanitizer
{
    /**
     * Optional XML namespace prefix in front of an element name.
     * The browser's XML parser resolves <x:script> with xmlns:x bound to the
     * SVG or XHTML namespace to a real script element, so every element
     * pattern must tolerate a prefix.
     *
     * An XML NCName may contain non-ASCII letters, and \w without the /u
     * modifier only matches ASCII, so the byte range \x80-\xff is listed
     * explicitly: it covers every byte of a UTF-8 multibyte character. The
     * patterns stay byte-oriented on purpose, because /u makes preg_match()
     * return false on malformed UTF-8, which would report the content as safe.
     */
    private const string NAMESPACE_PREFIX = '(?:[\w.\-\x80-\xff]+:)?';

    /**
     * Dangerous patterns to detect and remove.
     * These are applied AFTER entity decoding, so encoded bypasses are neutralized.
     */
    private const array DANGEROUS_PATTERNS = [
        // Script tags (any variation)
        '/<' . self::NAMESPACE_PREFIX . 'script\b[^>]*>.*?<\/' . self::NAMESPACE_PREFIX . 'script>/is',
        '/<' . self::NAMESPACE_PREFIX . 'script\b[^>]*\/>/is',
        '/<' . self::NAMESPACE_PREFIX . 'script\b[^>]*>/is',

        // Event handlers (onclick, onload, onerror, etc.). HTML parsers accept
        // "/" as an attribute separator (<svg/onload=...>), so it counts as
        // whitespace here in case the SVG is ever inlined into HTML.
        '/[\s\/]+on\w+\s*=\s*["\'][^"\']*["\']/i',
        '/[\s\/]+on\w+\s*=\s*[^"\'\s>][^\s>]*/i',

        // ForeignObject tags
        '/<' . self::NAMESPACE_PREFIX . 'foreignObject\b[^>]*>.*?<\/' . self::NAMESPACE_PREFIX . 'foreignObject>/is',
        '/<' . self::NAMESPACE_PREFIX . 'foreignObject\b[^>]*\/>/is',

        // JavaScript URLs in href/xlink:href (after entity decoding, these are plain text)
        '/href\s*=\s*["\'][\s]*javascript\s*:[^"\']*["\']/i',
        '/xlink:href\s*=\s*["\'][\s]*javascript\s*:[^"\']*["\']/i',
        '/href\s*=\s*["\'][\s]*vbscript\s*:[^"\']*["\']/i',
        '/xlink:href\s*=\s*["\'][\s]*vbscript\s*:[^"\']*["\']/i',

        // Data URLs with dangerous content types
        '/href\s*=\s*["\'][\s]*data\s*:[^"\']*["\']/i',
        '/xlink:href\s*=\s*["\'][\s]*data\s*:[^"\']*["\']/i',
        '/src\s*=\s*["\'][\s]*data\s*:[^"\']*["\']/i',

        // CSS expressions and dangerous style content
        '/style\s*=\s*["\'][^"\']*expression\s*\([^"\']*["\']/i',
        '/style\s*=\s*["\'][^"\']*javascript\s*:[^"\']*["\']/i',
        '/style\s*=\s*["\'][^"\']*vbscript\s*:[^"\']*["\']/i',
        '/style\s*=\s*["\'][^"\']*@import[^"\']*["\']/i',
        '/style\s*=\s*["\'][^"\']*behavior\s*:[^"\']*["\']/i',
        '/style\s*=\s*["\'][^"\']*-moz-binding\s*:[^"\']*["\']/i',

        // CDATA sections with script content
        '/<!\[CDATA\[.*?<script.*?\]\]>/is',

        // XML processing instructions (but allow standard XML declaration)
        '/<\?(?!xml\b)[^?]*\?>/is',

        // DOCTYPE with an internal subset and custom entity declarations.
        // Browsers resolve <!ENTITY j "javascript"> into &j;:alert(), which
        // html_entity_decode() cannot see.
        '/<!DOCTYPE\b[^\[>]*\[.*?\]\s*>/is',
        '/<!ENTITY\b[^>]*>/i',

        // HTML tags that shouldn't be in SVG
        '/<' . self::NAMESPACE_PREFIX . '(iframe|embed|object|applet|meta|link|base)\b[^>]*>/i',
    ];

    /**
     * DOCTYPE internal subsets and entity declarations, removed after the
     * declared entities have been expanded.
     */
    private const array ENTITY_DECLARATION_PATTERNS = [
        '/<!DOCTYPE\b[^\[>]*\[.*?\]\s*>/is',
        '/<!ENTITY\b[^>]*>/i',
    ];

    /**
     * Style attribute content that can run code or load external resources.
     */
    private const string DANGEROUS_STYLE = '/expression\s*\(|javascript\s*:|vbscript\s*:|@import|behavior\s*:|-moz-binding\s*:/i';

    /**
     * Dangerous element tags to strip completely.
     * Includes animate, set, and use which can execute JavaScript in SVG context.
     */
    private const array DANGEROUS_ELEMENTS = [
        'script',
        'foreignObject',
        'iframe',
        'embed',
        'object',
        'applet',
        'meta',
        'link',
        'base',
        'animate',
        'animateMotion',
        'animateTransform',
        'set',
        'use',
        'handler',
        'listener',
    ];

    /**
     * Sanitizes an SVG file by removing potentially dangerous content
     *
     * @param string $filePath Path to the SVG file
     * @return bool True if sanitization was successful, false otherwise
     */
    public function sanitize(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        // Check if a file actually contains SVG content
        if (!str_contains($content, '<svg')) {
            return false;
        }

        if ($this->hasUnsupportedEncoding($content)) {
            return false;
        }

        $document = $this->parseWellFormedXml($content);
        if (!$document instanceof DOMDocument) {
            return false;
        }

        $document = $this->withoutEntityDeclarations($document, $content);
        if (!$document instanceof DOMDocument) {
            return false;
        }

        $this->removeDangerousNodes($document);

        // Store what the XML parser read, never the uploaded string
        $sanitized = $document->saveXML();
        if (!is_string($sanitized) || !str_contains($sanitized, '<svg')) {
            return false;
        }

        if ($this->containsDangerousContent($this->decodeAllEntities($sanitized))) {
            return false;
        }

        return file_put_contents($filePath, $sanitized) !== false;
    }

    /**
     * Browsers resolve <!ENTITY j "javascript"> into &j;:alert(). The parser does not substitute
     * entities, so the declared ones are expanded once in the source, the declaring DOCTYPE is
     * dropped and the result has to parse again. Returns null when it does not.
     */
    private function withoutEntityDeclarations(DOMDocument $document, string $content): ?DOMDocument
    {
        $internalSubset = $document->doctype?->internalSubset;
        if ($internalSubset === null || $internalSubset === '') {
            return $document;
        }

        $expanded = preg_replace(
            self::ENTITY_DECLARATION_PATTERNS,
            replacement: '',
            subject: $this->expandCustomEntities($content),
        );

        return $this->parseWellFormedXml($expanded ?? '');
    }

    /**
     * Removes dangerous elements, attributes, processing instructions, comments and text from
     * the parsed document.
     */
    private function removeDangerousNodes(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);

        $nodes = $xpath->query('//processing-instruction() | //comment() | //text() | //*');
        if ($nodes === false) {
            return;
        }

        foreach (iterator_to_array($nodes, preserve_keys: false) as $node) {
            if ($node instanceof DOMElement) {
                $this->removeDangerousAttributes($node);
            }

            if (!$this->isDangerousNode($node)) {
                continue;
            }

            $node->parentNode?->removeChild($node);
        }
    }

    private function isDangerousNode(DOMNode $node): bool
    {
        if ($node instanceof DOMElement) {
            return array_any(
                self::DANGEROUS_ELEMENTS,
                static fn(string $element): bool => strcasecmp($element, self::localName($node->nodeName)) === 0,
            );
        }

        if ($node->nodeType === XML_PI_NODE || $node->nodeType === XML_COMMENT_NODE) {
            return true;
        }

        // Text and CDATA sections: markup in them is inert for an XML parser, but not once the
        // image is inlined into HTML.
        return $this->containsDangerousContent($this->decodeAllEntities((string) $node->nodeValue));
    }

    private function removeDangerousAttributes(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes ?? [], preserve_keys: false) as $attribute) {
            if (!$this->isDangerousAttribute($attribute)) {
                continue;
            }

            $element->removeAttributeNode($attribute);
        }
    }

    private function isDangerousAttribute(DOMAttr $attribute): bool
    {
        $name = strtolower(self::localName($attribute->nodeName));

        if (str_starts_with($name, 'on')) {
            return true;
        }

        // The parser has resolved one level of references; decode the rest like the patterns do
        $value = $this->decodeAllEntities($attribute->value);

        if ($name === 'style') {
            return preg_match(self::DANGEROUS_STYLE, $value) === 1;
        }

        if ($name !== 'href' && $name !== 'src') {
            return false;
        }

        // Browsers ignore whitespace and control characters in front of and inside the scheme
        $url = preg_replace('/[\x00-\x20\x7f]/', replacement: '', subject: $value) ?? '';

        return preg_match('/^(?:javascript|vbscript|data):/i', $url) === 1;
    }

    /**
     * Element or attribute name without its namespace prefix.
     */
    private static function localName(string $qualifiedName): string
    {
        $separator = strrpos($qualifiedName, needle: ':');

        return $separator === false ? $qualifiedName : substr($qualifiedName, $separator + 1);
    }

    /**
     * Parses the content as XML without network access, external DTD loading or entity
     * substitution. Returns null when the content is not well-formed.
     */
    private function parseWellFormedXml(string $content): ?DOMDocument
    {
        if ($content === '') {
            return null;
        }

        $document = new DOMDocument();
        $previousErrorHandling = libxml_use_internal_errors(use_errors: true);
        $isWellFormed = $document->loadXML($content, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorHandling);

        return $isWellFormed ? $document : null;
    }

    /**
     * The patterns match bytes, so they only see what the browser sees while the browser decodes
     * the file as UTF-8. Another declared encoding, or the escape sequences of stateful encodings
     * such as ISO-2022-JP, can hide a tag from them ("<scr" ESC "(B" "ipt>").
     */
    private function hasUnsupportedEncoding(string $content): bool
    {
        if (preg_match('/[\x00\x1b]/', $content) === 1) {
            return true;
        }

        $matches = [];
        if (preg_match('/<\?xml\b[^>]*?\bencoding\s*=\s*["\']([^"\']*)["\']/i', $content, $matches) !== 1) {
            return false;
        }

        return !in_array(strtolower(trim($matches[1])), ['utf-8', 'utf8'], strict: true);
    }

    /**
     * Validates if a file is a safe SVG without dangerous content
     *
     * @param string $filePath Path to the SVG file
     * @return bool True if SVG is safe, false if it contains dangerous content
     */
    public function isSafe(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        // Reject non-SVG content
        if (!str_contains($content, '<svg')) {
            return false;
        }

        if ($this->hasUnsupportedEncoding($content)) {
            return false;
        }

        if (!$this->parseWellFormedXml($content) instanceof DOMDocument) {
            return false;
        }

        $decoded = $this->decodeAllEntities($content);

        return !$this->containsDangerousContent($decoded);
    }

    /**
     * Checks already entity-decoded content against every dangerous pattern
     * and element.
     */
    private function containsDangerousContent(string $decoded): bool
    {
        if (array_any(
            self::DANGEROUS_PATTERNS,
            static fn(string $pattern): bool => preg_match($pattern, $decoded) === 1,
        )) {
            return true;
        }

        return array_any(
            self::DANGEROUS_ELEMENTS,
            static fn(string $element): bool => preg_match('/<' . self::elementName($element) . '\b/i', $decoded) === 1,
        );
    }

    /**
     * Regex fragment matching an element name with an optional namespace prefix.
     */
    private static function elementName(string $element): string
    {
        return self::NAMESPACE_PREFIX . preg_quote($element, delimiter: '/');
    }

    /**
     * Validates SVG content before saving (strict mode)
     * Returns an array of detected issues, empty array if safe
     *
     * @param string $content SVG content to validate
     * @return array<string> List of security issues found
     */
    public function detectIssues(string $content): array
    {
        $issues = [];

        if ($this->hasUnsupportedEncoding($content)) {
            $issues[] = 'Unsupported encoding: only UTF-8 without escape sequences or NUL bytes is accepted';
        }

        if (!$this->parseWellFormedXml($content) instanceof DOMDocument) {
            $issues[] = 'Not well-formed XML';
        }

        $decoded = $this->decodeAllEntities($content);

        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            $matches = [];
            if (!preg_match($pattern, $decoded, $matches)) {
                continue;
            }

            $issues[] = 'Dangerous pattern detected: ' . substr(string: $matches[0], offset: 0, length: 100);
        }

        foreach (self::DANGEROUS_ELEMENTS as $element) {
            if (!preg_match('/<' . self::elementName($element) . '\b/i', $decoded)) {
                continue;
            }

            $issues[] = 'Dangerous element found: ' . $element;
        }

        return $issues;
    }

    /**
     * Alternative: Completely reject SVGs instead of sanitizing
     * Use this if you want to be extra cautious
     *
     * @param string $filePath Path to check
     * @return bool True if a file should be rejected
     */
    public function shouldReject(string $filePath): bool
    {
        return !$this->isSafe($filePath);
    }

    /**
     * Decodes all HTML/XML entities (named, decimal, hex, and custom entities
     * declared in a DOCTYPE internal subset) recursively until stable.
     * This ensures that double-encoded or nested-encoded payloads are fully decoded
     * before pattern matching.
     */
    private function decodeAllEntities(string $content): string
    {
        $previous = '';
        $decoded = $content;
        $maxIterations = 10;

        while ($decoded !== $previous && $maxIterations-- > 0) {
            $previous = $decoded;
            // Expand custom entities (<!ENTITY j "javascript"> + &j; → javascript)
            $decoded = $this->expandCustomEntities($decoded);
            // Decode decimal entities (&#106; → j)
            $decoded =
                preg_replace_callback(
                    '/&#(\d+);/',
                    static fn(array $matches): string => mb_chr((int) $matches[1], encoding: 'UTF-8'),
                    $decoded,
                ) ?? '';
            // Decode hex entities (&#x6A; → j)
            $decoded =
                preg_replace_callback(
                    '/&#x([0-9a-fA-F]+);/',
                    static fn(array $matches): string => mb_chr((int) hexdec($matches[1]), encoding: 'UTF-8'),
                    $decoded,
                ) ?? '';
            // Decode named HTML entities (&amp; → &, &lt; → <, etc.)
            $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, encoding: 'UTF-8');
        }

        // Safety net: if the loop exited due to iteration limit, do a final
        // numeric/hex entity decode pass to catch any remaining entities
        $decoded =
            preg_replace_callback(
                '/&#(\d+);/',
                static fn(array $matches): string => mb_chr((int) $matches[1], encoding: 'UTF-8'),
                $decoded,
            ) ?? '';
        $decoded =
            preg_replace_callback(
                '/&#x([0-9a-fA-F]+);/',
                static fn(array $matches): string => mb_chr((int) hexdec($matches[1]), encoding: 'UTF-8'),
                $decoded,
            ) ?? '';

        // Browsers drop tab, CR and LF anywhere inside a URL, so
        // href="java&#9;script:..." executes. Drop them from URL attribute
        // values too, so the scheme is matched the way the browser sees it.
        $decoded =
            preg_replace_callback(
                '/((?:xlink:)?href|src)(\s*=\s*)(["\'])(.*?)\3/is',
                static fn(array $matches): string => (
                    $matches[1]
                    . $matches[2]
                    . $matches[3]
                    . (preg_replace('/[\x09\x0a\x0d]/', replacement: '', subject: $matches[4]) ?? '')
                    . $matches[3]
                ),
                $decoded,
            ) ?? '';

        // Strip null bytes and control characters that could break regex matching
        // Fail closed: a regex failure must never leak partially decoded content
        return preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', replacement: '', subject: $decoded) ?? '';
    }

    /**
     * Expands references to custom entities declared in a DOCTYPE internal subset.
     * The browser's XML parser performs the same substitution, so the sanitizer
     * must inspect the resolved text rather than the raw &name; reference.
     */
    private function expandCustomEntities(string $content): string
    {
        $declarations = [];
        if (!preg_match_all(
            '/<!ENTITY\s+([A-Za-z_:][\w.:-]*)\s+(?:"([^"]*)"|\'([^\']*)\')\s*>/i',
            $content,
            $declarations,
            PREG_SET_ORDER,
        )) {
            return $content;
        }

        $replacements = [];
        foreach ($declarations as $declaration) {
            $replacements['&' . $declaration[1] . ';'] = $declaration[2] !== ''
                ? $declaration[2]
                : $declaration[3] ?? '';
        }

        return strtr($content, $replacements);
    }
}
