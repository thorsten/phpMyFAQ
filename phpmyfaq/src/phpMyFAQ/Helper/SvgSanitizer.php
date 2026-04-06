<?php

/**
 * SVG Sanitizer (Regex-based) to prevent XSS attacks
 * Alternative implementation without ext-dom dependency
 *
 * Decodes all HTML/XML entities before pattern matching to prevent
 * encoding-based bypasses (e.g., &#106;&#97;&#118;&#97;... → javascript:).
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

class SvgSanitizer
{
    /**
     * Dangerous patterns to detect and remove.
     * These are applied AFTER entity decoding, so encoded bypasses are neutralized.
     */
    private const array DANGEROUS_PATTERNS = [
        // Script tags (any variation)
        '/<script\b[^>]*>.*?<\/script>/is',
        '/<script\b[^>]*\/>/is',
        '/<script\b[^>]*>/is',

        // Event handlers (onclick, onload, onerror, etc.)
        '/\s+on\w+\s*=\s*["\'][^"\']*["\']/i',
        '/\s+on\w+\s*=\s*[^"\'\s>][^\s>]*/i',

        // ForeignObject tags
        '/<foreignObject\b[^>]*>.*?<\/foreignObject>/is',
        '/<foreignObject\b[^>]*\/>/is',

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

        // HTML tags that shouldn't be in SVG
        '/<(iframe|embed|object|applet|meta|link|base)\b[^>]*>/i',
    ];

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

        // Remove dangerous patterns
        $sanitized = $this->removeDangerousContent($content);

        // Verify we still have valid SVG
        if (!str_contains($sanitized, '<svg')) {
            return false;
        }

        return file_put_contents($filePath, $sanitized) !== false;
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

        // Decode all HTML/XML entities so encoded payloads become plaintext
        // before regex matching. This defeats &#106;&#97;&#118;... → javascript: bypasses.
        $decoded = $this->decodeAllEntities($content);

        // Check for dangerous patterns on decoded content
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $decoded)) {
                return false;
            }
        }

        // Check for dangerous element tags on decoded content
        foreach (self::DANGEROUS_ELEMENTS as $element) {
            if (preg_match('/<' . preg_quote($element, delimiter: '/') . '\b/i', $decoded)) {
                return false;
            }
        }

        return true;
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

        $decoded = $this->decodeAllEntities($content);

        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (!preg_match($pattern, $decoded, $matches)) {
                continue;
            }

            $issues[] = 'Dangerous pattern detected: ' . substr(string: $matches[0], offset: 0, length: 100);
        }

        foreach (self::DANGEROUS_ELEMENTS as $element) {
            if (!preg_match('/<' . preg_quote($element, delimiter: '/') . '\b/i', $decoded)) {
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
     * Decodes all HTML/XML entities (named, decimal, hex) recursively until stable.
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
            // Decode decimal entities (&#106; → j)
            $decoded = preg_replace_callback(
                '/&#(\d+);/',
                static fn(array $matches): string => mb_chr((int) $matches[1], encoding: 'UTF-8'),
                $decoded,
            );
            // Decode hex entities (&#x6A; → j)
            $decoded = preg_replace_callback(
                '/&#x([0-9a-fA-F]+);/',
                static fn(array $matches): string => mb_chr(hexdec($matches[1]), encoding: 'UTF-8'),
                $decoded,
            );
            // Decode named HTML entities (&amp; → &, &lt; → <, etc.)
            $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, encoding: 'UTF-8');
        }

        // Safety net: if the loop exited due to iteration limit, do a final
        // numeric/hex entity decode pass to catch any remaining entities
        $decoded = preg_replace_callback(
            '/&#(\d+);/',
            static fn(array $matches): string => mb_chr((int) $matches[1], encoding: 'UTF-8'),
            $decoded,
        );
        $decoded = preg_replace_callback(
            '/&#x([0-9a-fA-F]+);/',
            static fn(array $matches): string => mb_chr(hexdec($matches[1]), encoding: 'UTF-8'),
            $decoded,
        );

        // Strip null bytes and control characters that could break regex matching
        return preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', replacement: '', subject: $decoded);
    }

    /**
     * Removes dangerous content from SVG string
     *
     * @param string $content SVG content
     * @return string Sanitized content
     */
    private function removeDangerousContent(string $content): string
    {
        $sanitized = $content;

        // First: decode all entities so encoded payloads become plaintext
        $sanitized = $this->decodeAllEntities($sanitized);

        // Second: Remove dangerous element tags with their content
        foreach (self::DANGEROUS_ELEMENTS as $element) {
            // Remove opening and closing tags with content
            $sanitized = preg_replace(
                '/<'
                . preg_quote($element, delimiter: '/')
                . '\b[^>]*>.*?<\/'
                . preg_quote($element, delimiter: '/')
                . '>/is',
                replacement: '',
                subject: $sanitized,
            );

            // Remove self-closing tags
            $sanitized = preg_replace(
                '/<' . preg_quote($element, delimiter: '/') . '\b[^>]*\/>/is',
                replacement: '',
                subject: $sanitized,
            );

            // Remove unclosed tags
            $sanitized = preg_replace(
                '/<' . preg_quote($element, delimiter: '/') . '\b[^>]*>/is',
                replacement: '',
                subject: $sanitized,
            );
        }

        // Third: Remove dangerous patterns using regex
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            $sanitized = preg_replace($pattern, replacement: '', subject: $sanitized);
        }

        // Fourth: Additional cleanup for remaining event handlers
        $sanitized = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', replacement: '', subject: $sanitized);

        // Fifth: Clean up any remaining dangerous URIs in attributes
        $sanitized = preg_replace(
            '/(href|xlink:href|src)\s*=\s*(["\'])[\s]*(javascript|vbscript|data)\s*:[^\2]*?\2/i',
            replacement: '',
            subject: $sanitized,
        );

        // Sixth: Remove CDATA sections with script content
        $sanitized = preg_replace('/<!\[CDATA\[.*?<script.*?\]\]>/is', replacement: '', subject: $sanitized);

        // Normalize whitespace (optional, for cleaner output)
        $sanitized = preg_replace('/\s+/', replacement: ' ', subject: $sanitized);

        return preg_replace('/>\s+</', replacement: '><', subject: $sanitized);
    }
}
