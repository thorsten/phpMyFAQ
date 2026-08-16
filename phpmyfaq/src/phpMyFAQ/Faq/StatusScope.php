<?php

/**
 * The FAQ editorial status scope of the query.
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
 * @since     2026-08-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use phpMyFAQ\Enums\FaqStatus;

/**
 * Answers which editorial status a query may disclose, as a reusable SQL fragment.
 *
 * Public read paths only ever see published FAQs; administrative queries see every
 * status. Centralising the condition keeps the visibility rule one decision instead
 * of a string repeated per query, the same pattern ReadScope established for the
 * read right.
 */
final readonly class StatusScope
{
    private function __construct(
        private ?FaqStatus $status,
    ) {
    }

    public static function publishedOnly(): self
    {
        return new self(FaqStatus::Published);
    }

    public static function any(): self
    {
        return new self(null);
    }

    public function toSqlFragment(string $faqAlias = 'fd'): string
    {
        if ($this->status === null) {
            return '';
        }

        return sprintf(" AND %s.status = '%s'", $faqAlias, $this->status->value);
    }
}
