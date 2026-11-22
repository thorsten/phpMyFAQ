<?php

/**
 * Thrown when a submitted FAQ publication window cannot be accepted.
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
 * @since     2026-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use InvalidArgumentException;

/**
 * Carries the translation key of the message to show, so the caller can answer in the
 * language of the user without having to tell the two rejections apart itself.
 */
final class InvalidPublicationWindowException extends InvalidArgumentException
{
    private function __construct(
        string $message,
        public readonly string $translationKey,
    ) {
        parent::__construct($message);
    }

    public static function invalidDate(string $value): self
    {
        return new self(
            sprintf('"%s" is not a valid publication date, expected the format YYYY-MM-DDTHH:MM.', $value),
            'msgInvalidFaqDate',
        );
    }

    public static function endBeforeStart(): self
    {
        return new self('The publication window ends before it starts.', 'msgInvalidFaqDateRange');
    }
}
