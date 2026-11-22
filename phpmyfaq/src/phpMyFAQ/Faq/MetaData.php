<?php

/**
 * FAQ meta-data class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-04
 */

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use phpMyFAQ\Category;
use phpMyFAQ\Category\Permission as CategoryPermission;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq\Permission as FaqPermission;
use phpMyFAQ\Visits\VisitsRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FaqMetaData
 *
 * @package phpMyFAQ\Faq
 */
class MetaData
{
    private ?int $faqId = null;

    private ?string $faqLanguage = null;

    /** @var int[]|null */
    private ?array $categories = null;

    /**
     * FaqPermission constructor.
     */
    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    public function setFaqId(int $faqId): MetaData
    {
        $this->faqId = $faqId;
        return $this;
    }

    public function setFaqLanguage(string $faqLanguage): MetaData
    {
        $this->faqLanguage = $faqLanguage;
        return $this;
    }

    /**
     * @param int[] $categories
     */
    public function setCategories(array $categories): MetaData
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * This method saves the category relations, the initial visits
     * and the permissions
     */
    public function save(): void
    {
        $categories = $this->categories ?? [];
        $faqId = $this->faqId ?? 0;
        $faqLanguage = $this->faqLanguage ?? '';

        $categoryRelation = new Relation($this->configuration, new Category($this->configuration));
        $categoryRelation->add($categories, $faqId, $faqLanguage);

        $this->logFirstView($faqId, $faqLanguage);

        // Set permissions: derive from category permissions and apply to both FAQ record and categories
        $faqPermission = new FaqPermission($this->configuration);
        $categoryPermission = new CategoryPermission($this->configuration);

        $userPermissions = $categoryPermission->get(CategoryPermission::USER, $categories);

        // Apply user permissions to the FAQ record and keep category permissions aligned
        $faqPermission->add(FaqPermission::USER, $faqId, $userPermissions);
        $categoryPermission->add(CategoryPermission::USER, $categories, $userPermissions);

        if ($this->configuration->get(item: 'security.permLevel') !== 'basic') {
            $groupPermissions = $categoryPermission->get(CategoryPermission::GROUP, $categories);
            $faqPermission->add(FaqPermission::GROUP, $faqId, $groupPermissions);
            $categoryPermission->add(CategoryPermission::GROUP, $categories, $groupPermissions);
        }
    }

    /**
     * Activates the visit counter in the language of the FAQ. Visits::logViews() counts in the
     * language of the current request, which is another one when a FAQ is imported or submitted
     * in a different language; statistics and exports join the counter by FAQ language and
     * would miss such a FAQ.
     */
    private function logFirstView(int $faqId, string $faqLanguage): void
    {
        $visitsRepository = new VisitsRepository($this->configuration);
        $requestTime = Request::createFromGlobals()->server->getInt(key: 'REQUEST_TIME');
        $timestamp = $requestTime > 0 ? $requestTime : time();

        if ($visitsRepository->exists($faqId, $faqLanguage)) {
            $visitsRepository->update($faqId, $faqLanguage, $timestamp);
            return;
        }

        $visitsRepository->insert($faqId, $faqLanguage, $timestamp);
    }
}
