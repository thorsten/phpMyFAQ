<?php

/**
 * FAQ meta data class for phpMyFAQ.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-04
 */

namespace phpMyFAQ\Faq;

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
    /** @var Configuration */
    private $config;

    /** @var int */
    private $faqId;

    /** @var string */
    private $faqLanguage;

    /** @var array */
    private $categories;

    /**
     * FaqPermission constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * @param int $faqId
     * @return FaqMetaData
     */
    public function setFaqId(int $faqId): FaqMetaData
    {
        $this->faqId = $faqId;
        return $this;
    }

    /**
     * @param string $faqLanguage
     * @return FaqMetaData
     */
    public function setFaqLanguage(string $faqLanguage): FaqMetaData
    {
        $this->faqLanguage = $faqLanguage;
        return $this;
    }

    /**
     * @param array $categories
     * @return FaqMetaData
     */
    public function setCategories(array $categories): FaqMetaData
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * This methods saves the category relations, the initial visits
     * and the permissions
     */
    public function save(): void
    {
        $categoryRelation = new CategoryRelation($this->config);
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
