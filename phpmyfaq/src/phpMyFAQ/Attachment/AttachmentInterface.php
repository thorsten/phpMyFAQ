<?php

/**
 * Interface to create new attachment types.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

namespace phpMyFAQ\Attachment;

/**
 * Interface AttachmentInterface
 *
 * @package phpMyFAQ\Attachment
 */
interface AttachmentInterface
{
    /**
     * Save current attachment to the appropriate storage.
     *
     * @param string $filePath full path to the attachment file
     * @return bool
     */
    public function save(string $filePath): bool;

    /**
     * Delete attachment.
     *
     * @return bool
     */
    public function delete(): bool;

    /**
     * Retrieve file contents into a variable.
     *
     * @return string
     */
    public function get(): string;

    /**
     * Output current file to stdout.
     *
     */
    public function rawOut(): void;
}
