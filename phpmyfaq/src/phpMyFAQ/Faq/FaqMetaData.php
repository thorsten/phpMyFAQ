<?php

/**
 * FAQ meta data class for phpMyFAQ.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-04
 */

namespace phpMyFAQ\Faq;

use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryPermission;
use phpMyFAQ\Category\CategoryRelation;
use phpMyFAQ\Configuration;
use phpMyFAQ\Visits;

/**
 * Class FaqMetaData
 *
 * @package phpMyFAQ\Faq
 */
class FaqMetaData
{
    private ?int $faqId = null;

    private ?string $faqLanguage = null;

    private ?array $categories = null;

    /**
     * FaqPermission constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
    }

    public function setFaqId(int $faqId): FaqMetaData
    {
        $this->faqId = $faqId;
        return $this;
    }

    public function setFaqLanguage(string $faqLanguage): FaqMetaData
    {
        $this->faqLanguage = $faqLanguage;
        return $this;
    }

    public function setCategories(array $categories): FaqMetaData
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
        $categoryRelation = new CategoryRelation($this->config, new Category($this->config));
        $categoryRelation->add($this->categories, $this->faqId, $this->faqLanguage);

        // Activate visits
        $visits = new Visits($this->config);
        $visits->logViews($this->faqId);

        // Set permissions
        $faqPermission = new FaqPermission($this->config);
        $categoryPermission = new CategoryPermission($this->config);

        $userPermissions = $categoryPermission->get(CategoryPermission::USER, $this->categories);

        $faqPermission->add(FaqPermission::USER, $this->faqId, $userPermissions);
        $categoryPermission->add(CategoryPermission::USER, $this->categories, $userPermissions);

        if ($this->config->get('security.permLevel') !== 'basic') {
            $groupPermissions = $categoryPermission->get(CategoryPermission::GROUP, $this->categories);
            $faqPermission->add(FaqPermission::GROUP, $this->faqId, $groupPermissions);
            $categoryPermission->add(CategoryPermission::GROUP, $this->categories, $groupPermissions);
        }
    }
}
