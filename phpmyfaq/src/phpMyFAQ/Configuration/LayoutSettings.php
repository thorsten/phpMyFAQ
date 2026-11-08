<?php

/**
 * The layout settings class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration as CoreConfiguration;

readonly class LayoutSettings
{
    public function __construct(
        private CoreConfiguration $configuration,
    ) {
    }

    public function getTemplateSet(): string
    {
        return (string) ($this->configuration->get(item: 'layout.templateSet') ?? 'default');
    }

    public function getCustomCss(): string
    {
        // The value is rendered verbatim inside a <style> block via Twig's |raw filter.
        // Strip HTML tags so a stored "</style><script>...</script>" payload cannot break
        // out of the style element and execute as script (stored XSS). strip_tags() leaves
        // the ">" child combinator and other CSS syntax intact.
        return strip_tags((string) $this->configuration->get(item: 'layout.customCss'));
    }
}
