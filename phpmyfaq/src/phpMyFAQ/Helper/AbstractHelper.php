<?php

/**
 * Main helper class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use phpMyFAQ\Category;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Configuration;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Tags;

/**
 * Class Helper
 *
 * @package phpMyFAQ
 */
abstract class AbstractHelper
{
    protected ?Category $Category = null;

    protected Relation $categoryRelation;

    protected ?Tags $Tags = null;

    protected ?Plurals $plurals = null;

    protected mixed $sessionId = null;

    protected Configuration $configuration;

    public function setCategory(Category $Category): AbstractHelper
    {
        $this->Category = $Category;
        return $this;
    }

    public function getCategory(): Category
    {
        return $this->Category;
    }

    public function setCategoryRelation(Relation $categoryRelation): AbstractHelper
    {
        $this->categoryRelation = $categoryRelation;
        return $this;
    }

    public function setTags(Tags $Tags): AbstractHelper
    {
        $this->Tags = $Tags;
        return $this;
    }

    public function setPlurals(Plurals $plurals): AbstractHelper
    {
        $this->plurals = $plurals;
        return $this;
    }

    public function setSessionId(int|string $sid): AbstractHelper
    {
        $this->sessionId = $sid;
        return $this;
    }

    public function setConfiguration(Configuration $configuration): AbstractHelper
    {
        $this->configuration = $configuration;
        return $this;
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }
}
