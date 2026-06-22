<?php

/**
 * Composes attachment filenames from an original name and an optional custom base name.
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
 * @since     2026-06-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Attachment;

final class Filename
{
    /**
     * Builds the filename to store for an uploaded attachment.
     *
     * When no custom name is given (null or whitespace-only), the original name
     * is kept unchanged. Otherwise the custom name is reduced to a safe base name
     * and the original extension is always re-applied so the file ending never
     * changes.
     */
    public static function compose(string $originalName, ?string $customName): string
    {
        $customName = $customName === null ? '' : trim($customName);
        if ($customName === '') {
            return $originalName;
        }

        $base = pathinfo(basename($customName), PATHINFO_FILENAME);
        if ($base === '') {
            return $originalName;
        }

        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

        return $extension === '' ? $base : $base . '.' . $extension;
    }
}
