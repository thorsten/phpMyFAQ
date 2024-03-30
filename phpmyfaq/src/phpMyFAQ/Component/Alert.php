<?php

/**
 * phpMyFAQ Alert component renderer based on Bootstrap v5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-22
 */

namespace phpMyFAQ\Component;

use phpMyFAQ\Translation;

/**
 * Class Alert
 *
 * @package phpMyFAQ\Component
 * @deprecated will be removed in phpMyFAQ v4.1, please use Alerts directly in Twig templates
 */
class Alert
{
    /**
     * Renders a Bootstrap success alert component.
     */
    public static function success(string $translationKey): string
    {
        return sprintf(
            '<div class="alert alert-success alert-dismissible fade show">%s%s</div>',
            Translation::get($translationKey),
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
        );
    }

    /**
     * Renders a Bootstrap danger alert component.
     */
    public static function danger(string $translationKey, ?string $errorMessage = null): string
    {
        return sprintf(
            '<div class="alert alert-danger alert-dismissible fade show mt-2">%s%s%s</div>',
            '<h4 class="alert-heading">' . Translation::get($translationKey) . '</h4>',
            $errorMessage !== null ? '<p>' . $errorMessage . '</p>' : '',
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
        );
    }
}
