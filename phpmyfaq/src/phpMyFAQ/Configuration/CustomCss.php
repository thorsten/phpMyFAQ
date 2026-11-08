<?php

/**
 * Administrator-supplied CSS, safe to render inside a <style> element
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
 * @since     2026-11-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use Stringable;

/**
 * The layout.customCss configuration value is rendered unescaped into a <style> block, so a
 * delegated administrator with the CONFIGURATION_EDIT right could otherwise store
 * "</style><script>...</script>" and run script on every public page (stored XSS).
 *
 * Inside an HTML raw text element the only sequence that can terminate the element - and therefore
 * the only way the value can break out into markup - is a literal "</style" followed by whitespace,
 * "/" or ">". An instance of this class can never contain that sequence.
 *
 * Only that sequence is removed. strip_tags() would also drop valid CSS: it truncates the media
 * query range syntax "@media (width <= 600px)" at the "<=" and empties 'content: "<click>"'.
 * HTML escaping is no option either, because it would encode the ">" child combinator.
 *
 * A malformed value is sanitized rather than rejected: refusing a whole stylesheet over one bad
 * sequence would lock an administrator out of the layout settings instead of helping them.
 */
final readonly class CustomCss implements Stringable
{
    /**
     * A literal "</style" that an HTML parser would treat as the end of the element. The trailing
     * "$" alternative covers a value that was truncated right after the tag name.
     */
    private const string STYLE_TERMINATOR = '#</style(?=[\s/>])|</style$#i';

    public string $css;

    public function __construct(?string $css)
    {
        $this->css = self::removeStyleTerminators((string) $css);
    }

    public function __toString(): string
    {
        return $this->css;
    }

    /**
     * Removal repeats until the value no longer changes: deleting one occurrence can make its
     * neighbours adjacent and form a new one ("</sty" . "</style>" . "le >" becomes "</style >").
     */
    private static function removeStyleTerminators(string $css): string
    {
        do {
            $previous = $css;
            $css = (string) preg_replace(pattern: self::STYLE_TERMINATOR, replacement: '', subject: $css);
        } while ($css !== $previous);

        return $css;
    }
}
