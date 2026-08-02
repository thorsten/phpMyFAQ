<?php

declare(strict_types=1);

/**
 * Publication state of a single FAQ record.
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

/**
 * Answers whether an FAQ record is published: activated by an editor and inside its
 * publication window.
 *
 * Faq::getFaq() intentionally returns records the requester may not read. It swaps the
 * answer for a placeholder but keeps title, solution id, author and timestamps, so the
 * detail page can render an "inactive article" notice. Every consumer that exports the
 * record rather than rendering that notice has to ask this first.
 */
final readonly class PublicationStatus
{
    /**
     * @param array<string, mixed> $faqRecord a record as populated by Faq::getFaq()
     */
    public function __construct(
        private array $faqRecord,
    ) {
    }

    public function isPublished(): bool
    {
        return $this->isActivated() && $this->isWithinPublicationWindow();
    }

    /**
     * An empty record means getFaq() was never called; treat that as unpublished so a
     * missing load cannot be mistaken for a public record.
     */
    private function isActivated(): bool
    {
        return 'yes' === ($this->faqRecord['active'] ?? 'no');
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
