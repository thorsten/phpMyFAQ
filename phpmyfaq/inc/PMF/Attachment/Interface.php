<?php

/**
 * Interface to create new attachment types.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Attachment_Interface.
 *
 * @category  phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */
interface PMF_Attachment_Interface
{
    /**
     * Save current attachment to the appropriate storage.
     *
     * @param string $filepath full path to the attachment file
     *
     * @return bool
     */
    public function save($filepath);

    /**
     * Delete attachment.
     *
     * @return bool
     */
    public function delete();

    /**
     * Retrieve file contents into a variable.
     *
     * @return string
     */
    public function get();

    /**
     * Output current file to stdout.
     *
     * @param bool   $headers     if headers must be sent
     * @param string $disposition diposition type (ignored if $headers false)
     */
    public function rawOut($headers = true, $disposition = 'attachment');
}
