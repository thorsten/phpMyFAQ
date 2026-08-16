<?php

declare(strict_types=1);

/**
 * Visibility of a single FAQ record on public read paths.
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
 * @since     2026-08-02
 */

namespace phpMyFAQ\Faq;

use phpMyFAQ\Enums\FaqStatus;

/**
 * Answers whether an FAQ record may be disclosed to the current requester: permitted,
 * published and inside its publication window.
 *
 * Neither Faq::getFaq() nor Faq::getFaqBySolutionId() filters by publication state. An
 * inactive, not yet published or already expired record comes back with its metadata
 * intact and only the answer replaced by a placeholder, and a non-existing or
 * non-permitted record is flagged with the solution ID 42. Public read paths must ask
 * this before rendering or exporting a record, otherwise they leak the title, solution
 * ID, author and update date of unpublished FAQs.
 */
final readonly class RecordVisibility
{
    /**
     * The solution ID Faq::getFaq() stamps on its placeholder for records that do not
     * exist or that the requester has no permission for.
     */
    private const int ACCESS_DENIED_SOLUTION_ID = 42;

    /**
     * @param array<string, mixed> $faqRecord a record as populated by Faq::getFaq()
     */
    public function __construct(
        private array $faqRecord,
    ) {
    }

    public function isVisible(): bool
    {
        return $this->isPermitted() && $this->isPublished() && $this->isWithinPublicationWindow();
    }

    /**
     * An empty record means getFaq() was never called; treat that as denied so a missing
     * load cannot be mistaken for a visible record.
     */
    private function isPermitted(): bool
    {
        if ($this->faqRecord === []) {
            return false;
        }

        $solutionId = (int) ($this->faqRecord['solution_id'] ?? self::ACCESS_DENIED_SOLUTION_ID);

        return self::ACCESS_DENIED_SOLUTION_ID !== $solutionId;
    }

    private function isPublished(): bool
    {
        return FaqStatus::Published->value === ($this->faqRecord['status'] ?? FaqStatus::Draft->value);
    }

    private function isWithinPublicationWindow(): bool
    {
        $now = date(format: 'YmdHis');
        $dateStart = (string) ($this->faqRecord['dateStart'] ?? '');
        $dateEnd = (string) ($this->faqRecord['dateEnd'] ?? '');

        $hasStarted = $dateStart === '' || $now >= $dateStart;
        $hasNotEnded = $dateEnd === '' || $now <= $dateEnd;

        return $hasStarted && $hasNotEnded;
    }
}
