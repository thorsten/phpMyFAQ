<?php

/**
 * The Relation class for dynamic-related record linking.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Marco Enders <marco@minimarco.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-06-18
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Relation\RelationRepository;

/**
 * Class Relation
 * @package phpMyFAQ
 */
readonly class Relation
{
    private RelationRepository $repository;

    /**
     * Relation constructor.
     */
    public function __construct(
        private Configuration $configuration,
    ) {
        $this->repository = new RelationRepository($this->configuration);
    }

    /**
     * Returns all relevant articles for a FAQ record with the same language.
     * Prefer exact matches to avoid unrelated results from fuzzy searches.
     *
     * @param string $question FAQ title
     * @param string $keywords FAQ keywords
     */
    public function getAllRelatedByQuestion(string $question, string $keywords): array
    {
        return $this->repository->getAllRelatedByQuestion(
            $question,
            $keywords,
            $this->configuration->getLanguage()->getLanguage(),
        );
    }
}
