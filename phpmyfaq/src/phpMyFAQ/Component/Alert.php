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
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-22
 */

namespace phpMyFAQ\Component;

use phpMyFAQ\Translation;

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
            '<div class="alert alert-danger alert-dismissible fade show">%s%s%s</div>',
            Translation::get($translationKey),
            $errorMessage !== null ? '<br>' . Translation::get('ad_adus_dberr') . '<br>' . $errorMessage : '',
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
        );
    }

    /**
     * Renders a Bootstrap info alert component.
     */
    public static function info(string $translationKey): string
    {
        return sprintf('<div class="alert alert-info">%s</div>', Translation::get($translationKey));
    }

    /**
     * Renders a Bootstrap warning alert component.
     */
    public static function warning(string $translationKey, ?string $warningMessage = null): string
    {
        return sprintf(
            '<div class="alert alert-warning alert-dismissible fade show">%s%s%s</div>',
            Translation::get($translationKey),
            $warningMessage !== null ? '<br>' . $warningMessage : '',
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
        );
    }
}
