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
 *
 * A reader's view is narrower than "published": a published FAQ is only public inside its
 * publication window (see PublicationWindow), so visibleToReaders() adds that condition.
 */
final readonly class StatusScope
{
    private function __construct(
        private ?FaqStatus $status,
        private bool $insidePublicationWindow = false,
    ) {
    }

    /**
     * Published and inside the publication window right now: what a reader may see. Use this
     * for every public list, count or lookup.
     */
    public static function visibleToReaders(): self
    {
        return new self(FaqStatus::Published, insidePublicationWindow: true);
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

        $statusCondition = sprintf(" AND %s.status = '%s'", $faqAlias, $this->status->value);
        if (!$this->insidePublicationWindow) {
            return $statusCondition;
        }

        // The window columns hold 14-character "YmdHis" strings, so a string comparison orders them.
        return sprintf(
            "%1\$s AND %2\$s.date_start <= '%3\$s' AND %2\$s.date_end >= '%3\$s'",
            $statusCondition,
            $faqAlias,
            date(format: 'YmdHis'),
        );
    }
}
