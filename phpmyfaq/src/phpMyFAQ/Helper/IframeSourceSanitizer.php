<?php

/**
 * Attribute sanitizer for the source of embedded iframes
 *
 * Keeps the src of an <iframe> only if it is an https URL of an allowed media host
 * (records.allowedMediaHosts), e.g. a YouTube or Vimeo embed. Other media such as
 * <img> or <video> are not affected, unlike HtmlSanitizerConfig::allowMediaHosts().
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
 * @since     2026-10-24
 */

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\TextSanitizer\UrlSanitizer;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

final readonly class IframeSourceSanitizer implements AttributeSanitizerInterface
{
    /** @var list<string> */
    private array $allowedHosts;

    /**
     * @param string[] $allowedHosts Host names, optionally with a ":port" suffix
     */
    public function __construct(array $allowedHosts)
    {
        // The browser loads the iframe, so only the host name matters, not the port
        $this->allowedHosts = array_values(array_filter(
            array_map(
                static fn(string $host): string => (
                    preg_replace('/^(\[[0-9a-f:.]+\]|[^:\[\]]+):\d{1,5}$/i', replacement: '$1', subject: trim($host))
                    ?? ''
                ),
                $allowedHosts,
            ),
            static fn(string $host): bool => $host !== '',
        ));
    }

    public function getSupportedElements(): array
    {
        return ['iframe'];
    }

    public function getSupportedAttributes(): array
    {
        return ['src'];
    }

    public function sanitizeAttribute(
        string $element,
        string $attribute,
        string $value,
        HtmlSanitizerConfig $config,
    ): ?string {
        if ($this->allowedHosts === []) {
            return null;
        }

        return UrlSanitizer::sanitize(
            $value,
            allowedSchemes: ['https'],
            allowedHosts: $this->allowedHosts,
            allowRelative: false,
        );
    }
}
