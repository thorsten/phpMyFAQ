<?php

/**
 * In-memory SplFileObject used as a safe input for the tivie/htaccess-parser.
 *
 * The parser drives the read loop with `while ($file->valid()) { $file->getCurrentLine(); }`,
 * which mixes SPL's iterator protocol with raw `fgets()`. Under PHP 8.6 the iterator
 * state and the underlying file pointer can desync, causing `getCurrentLine()` to
 * throw "Cannot read from file" at EOF. Aligning `valid()` with `!eof()` restores
 * the behavior the parser expects across PHP versions without patching the library.
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
 * @since     2026-04-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use SplTempFileObject;

final class InMemoryHtaccessFile extends SplTempFileObject
{
    public function valid(): bool
    {
        return !$this->eof();
    }

    /**
     * The parser's readability guard uses SplFileInfo::isReadable(), which returns
     * false for in-memory temp objects because they have no filesystem path. The
     * content is always available here, so advertise the file as readable.
     */
    public function isReadable(): bool
    {
        return true;
    }
}
