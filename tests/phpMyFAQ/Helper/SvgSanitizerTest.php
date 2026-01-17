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
}
