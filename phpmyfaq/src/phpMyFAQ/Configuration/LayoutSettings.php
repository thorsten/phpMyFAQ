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
        private CoreConfiguration $coreConfiguration,
    ) {
    }

    public function getTemplateSet(): string
    {
        return (string) ($this->coreConfiguration->get(item: 'layout.templateSet') ?? 'default');
    }

    /**
     * Returns the custom CSS, safe to render unescaped inside a <style> element.
     *
     * Inside an HTML raw text element the only sequence that can terminate the element - and
     * therefore the only way a stored value can break out into markup - is a literal "</style"
     * followed by whitespace, "/" or ">". That sequence is removed here. Everything else is kept
     * verbatim, because valid CSS needs "<" (media query range syntax) and ">" (child combinator),
     * which strip_tags() or HTML escaping would destroy.
     *
     * Removal runs until the value no longer changes, because deleting one occurrence can make its
     * neighbours adjacent and form a new one ("</sty" . "</style>" . "le >").
     */
    public function getCustomCss(): string
    {
        $customCss = (string) $this->coreConfiguration->get(item: 'layout.customCss');

        do {
            $previous = $customCss;
            $customCss = (string) preg_replace(
                pattern: '#</style(?=[\s/>])|</style$#i',
                replacement: '',
                subject: $customCss,
            );
        } while ($customCss !== $previous);

        return $customCss;
    }
}
