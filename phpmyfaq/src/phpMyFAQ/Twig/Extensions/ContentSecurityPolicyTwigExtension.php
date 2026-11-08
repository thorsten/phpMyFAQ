<?php

/**
 * Twig extension exposing the Content-Security-Policy nonce to the templates
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Template
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-11-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Http\ContentSecurityPolicyNonce;
use Twig\Attribute\AsTwigFunction;
use Twig\Extension\AbstractExtension;

/**
 * Class ContentSecurityPolicyTwigExtension
 *
 * Every inline <script> in a template needs nonce="{{ cspNonce() }}", because
 * script-src no longer allows 'unsafe-inline'. Without the attribute the
 * browser refuses to run the script.
 */
class ContentSecurityPolicyTwigExtension extends AbstractExtension
{
    #[AsTwigFunction(name: 'cspNonce')]
    public static function cspNonce(): string
    {
        return ContentSecurityPolicyNonce::get();
    }
}
