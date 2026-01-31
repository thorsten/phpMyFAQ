<?php

/**
 * SVG Sanitizer (Regex-based) to prevent XSS attacks
 * Alternative implementation without ext-dom dependency
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
     * Dangerous patterns to detect and remove
     */
    private const array DANGEROUS_PATTERNS = [
        // Script tags (any variation)
        '/<script\b[^>]*>.*?<\/script>/is',
        '/<script\b[^>]*\/>/is',

        // Event handlers (onclick, onload, onerror, etc.)
        '/\s*on\w+\s*=\s*["\'][^"\']*["\']/i',
        '/\s*on\w+\s*=\s*[^"\'\s>][^\s>]*/i',

        // ForeignObject tags
        '/<foreignObject\b[^>]*>.*?<\/foreignObject>/is',
        '/<foreignObject\b[^>]*\/>/is',

        // JavaScript URLs in href/xlink:href
        '/href\s*=\s*["\']javascript:[^"\']*["\']/i',
        '/xlink:href\s*=\s*["\']javascript:[^"\']*["\']/i',

        // Data URLs with HTML content
        '/href\s*=\s*["\']data:text\/html[^"\']*["\']/i',
        '/xlink:href\s*=\s*["\']data:text\/html[^"\']*["\']/i',

        // VBScript URLs
        '/href\s*=\s*["\']vbscript:[^"\']*["\']/i',
        '/xlink:href\s*=\s*["\']vbscript:[^"\']*["\']/i',

        // CSS expressions
        '/style\s*=\s*["\'][^"\']*expression\s*\([^"\']*["\']/i',
        '/style\s*=\s*["\'][^"\']*javascript:[^"\']*["\']/i',
        '/style\s*=\s*["\'][^"\']*import[^"\']*["\']/i',

        // Embedded XML/HTML entities that could be malicious
        '/<!\[CDATA\[.*?<script.*?\]\]>/is',

        // XML processing instructions (but allow standard XML declaration)
        '/<\?(?!xml)[^?]*\?>/is',

        // HTML tags that shouldn't be in SVG
        '/<(iframe|embed|object|applet|meta|link|base)\b[^>]*>/i',
    ];

    /**
     * Additional dangerous element tags to strip completely
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

        // Check for dangerous patterns
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (!preg_match($pattern, $content)) {
                continue;
            }

            return false;
        }

        // Check for dangerous element tags
        foreach (self::DANGEROUS_ELEMENTS as $element) {
            if (!preg_match('/<' . preg_quote($element, '/') . '\b/i', $content)) {
                continue;
            }

            return false;
        }

        return true;
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

        // First pass: Remove dangerous element tags with their content
        foreach (self::DANGEROUS_ELEMENTS as $element) {
            // Remove opening and closing tags with content
            $sanitized = preg_replace(
                '/<' . preg_quote($element, '/') . '\b[^>]*>.*?<\/' . preg_quote($element, '/') . '>/is',
                '',
                $sanitized,
            );

            // Remove self-closing tags
            $sanitized = preg_replace('/<' . preg_quote($element, '/') . '\b[^>]*\/>/is', '', $sanitized);
        }

        // Second pass: Remove dangerous patterns using regex
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            $sanitized = preg_replace($pattern, '', $sanitized);
        }

        // Third pass: Additional cleanup for remaining event handlers
        // This catches edge cases where the attribute might not have quotes
        $sanitized = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $sanitized);

        // Fourth pass: Clean up any remaining javascript: or data:text/html in attributes
        $sanitized = preg_replace(
            '/(href|xlink:href|src)\s*=\s*(["\'])(?:javascript:|data:text\/html|vbscript:)[^\2]*\2/i',
            '',
            $sanitized,
        );

        // Fifth pass: Remove CDATA sections with script content
        $sanitized = preg_replace('/<!\[CDATA\[.*?<script.*?\]\]>/is', '', $sanitized);

        // Normalize whitespace (optional, for cleaner output)
        $sanitized = preg_replace('/\s+/', ' ', $sanitized);

        return preg_replace('/>\s+</', '><', $sanitized);
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

        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (!preg_match($pattern, $content, $matches)) {
                continue;
            }

            $issues[] = 'Dangerous pattern detected: ' . substr($matches[0], 0, 100);
        }

        foreach (self::DANGEROUS_ELEMENTS as $element) {
            if (!preg_match('/<' . preg_quote($element, '/') . '\b/i', $content)) {
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
}
