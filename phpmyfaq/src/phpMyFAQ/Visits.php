<?php

/**
 * Handles all the stuff for visits.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link  https://www.phpmyfaq.de
 * @since 2009-03-08
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Visits\VisitsRepository;
use phpMyFAQ\Visits\VisitsRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Visits
 *
 * @package phpMyFAQ
 */
readonly class Visits
{
    private VisitsRepositoryInterface $repository;

    /**
     * Constructor.
     */
    public function __construct(
        private Configuration $configuration,
    ) {
        $this->repository = new VisitsRepository($configuration);
    }

    /**
     * Counting the views of a FAQ record.
     *
     * @param int $faqId FAQ record ID
     */
    public function logViews(int $faqId): void
    {
        $language = $this->configuration->getLanguage()->getLanguage();
        $visitCount = $this->repository->getVisitCount($faqId, $language);

        if ($visitCount === 0) {
            $this->add($faqId);
            return;
        }

        $this->update($faqId);
    }

    /**
     * Adds a new entry in the table "faqvisits".
     *
     * @param int $faqId Record ID
     */
    public function add(int $faqId): bool
    {
        $language = $this->configuration->getLanguage()->getLanguage();
        $timestamp = Request::createFromGlobals()->server->get(key: 'REQUEST_TIME');

        // If a row already exists for this (id, lang), update it instead of inserting to avoid unique constraint errors
        if ($this->repository->exists($faqId, $language)) {
            $this->update($faqId);
            return true;
        }

        return $this->repository->insert($faqId, $language, $timestamp);
    }

    /**
     * Updates an entry in the table "faqvisits".
     *
     * @param int $faqId FAQ record ID
     */
    private function update(int $faqId): void
    {
        $language = $this->configuration->getLanguage()->getLanguage();
        $timestamp = Request::createFromGlobals()->server->get(key: 'REQUEST_TIME');

        $this->repository->update($faqId, $language, $timestamp);
    }

    /**
     * Get all the entries from the table "faqvisits".
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllData(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Resets all visits to the current date and one visit per FAQ.
     */
    public function resetAll(): bool
    {
        $timestamp = Request::createFromGlobals()->server->get(key: 'REQUEST_TIME');
        return $this->repository->resetAll($timestamp);
    }
}
