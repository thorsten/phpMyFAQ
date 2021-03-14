<?php

/**
 * Attachment handler class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

namespace phpMyFAQ;

/**
 * Class Attachment
 *
 * @package phpMyFAQ
 */
class Attachment
{
    /**
     * Storage type filesystem.
     *
     * @var int
     */
    public const STORAGE_TYPE_FILESYSTEM = 0;

    /**
     * Storage type database.
     *
     * @var int
     */
    public const STORAGE_TYPE_DB = 1;
}
