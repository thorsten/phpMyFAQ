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
 * @copyright 2020-2025 phpMyFAQ Team
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
use phpMyFAQ\Visits;

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
        $categoryRelation = new Relation($this->configuration, new Category($this->configuration));
        $categoryRelation->add($this->categories, $this->faqId, $this->faqLanguage);

        // Activate visits
        $visits = new Visits($this->configuration);
        $visits->logViews($this->faqId);

        // Set permissions: derive from category permissions and apply to both FAQ record and categories
        $faqPermission = new FaqPermission($this->configuration);
        $categoryPermission = new CategoryPermission($this->configuration);

        $userPermissions = $categoryPermission->get(CategoryPermission::USER, $this->categories);

        // Apply user permissions to the FAQ record and keep category permissions aligned
        $faqPermission->add(FaqPermission::USER, (int) $this->faqId, $userPermissions);
        $categoryPermission->add(CategoryPermission::USER, $this->categories, $userPermissions);

        if ($this->configuration->get(item: 'security.permLevel') !== 'basic') {
            $groupPermissions = $categoryPermission->get(CategoryPermission::GROUP, $this->categories);
            $faqPermission->add(FaqPermission::GROUP, (int) $this->faqId, $groupPermissions);
            $categoryPermission->add(CategoryPermission::GROUP, $this->categories, $groupPermissions);
        }
    }
}
