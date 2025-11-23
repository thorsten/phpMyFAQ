<?php

/**
 * All SEO relevant stuff.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2014-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-08-31
 */

declare(strict_types=1);

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Seo\SeoRepository;
use phpMyFAQ\Seo\SeoRepositoryInterface;

/**
 * Class Seo
 *
 * @package phpMyFAQ
 */
readonly class Seo
{
    private SeoRepositoryInterface $repository;

    public function __construct(
        private Configuration $configuration,
        ?SeoRepositoryInterface $repository = null,
    ) {
        $this->repository = $repository ?? new SeoRepository($this->configuration);
    }

    /**
     * @return bool True if the SEO entity was created
     */
    public function create(SeoEntity $seoEntity): bool
    {
        return $this->repository->create($seoEntity);
    }

    /**
     * @throws Exception
     */
    public function get(SeoEntity $seoEntity): SeoEntity
    {
        return $this->repository->get($seoEntity);
    }

    /**
     * @return bool True if the SEO entity was updated
     */
    public function update(SeoEntity $seoEntity): bool
    {
        return $this->repository->update($seoEntity);
    }

    /**
     * @return bool True if the SEO entity was deleted
     */
    public function delete(SeoEntity $seoEntity): bool
    {
        return $this->repository->delete($seoEntity);
    }

    public function getMetaRobots(string $action): string
    {
        return match ($action) {
            'main' => $this->configuration->get(item: 'seo.metaTagsHome'),
            'faq' => $this->configuration->get(item: 'seo.metaTagsFaqs'),
            'show' => $this->configuration->get(item: 'seo.metaTagsCategories'),
            default => $this->configuration->get(item: 'seo.metaTagsPages'),
        };
    }
}
