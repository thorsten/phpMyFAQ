<?php

/**
 * Tags entity class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-08-15
 */

namespace phpMyFAQ\Entity;

/**
 * Class TagEntity
 *
 * @package phpMyFAQ\Entity
 */
class TagEntity
{
    private ?int $id = null;

    private ?string $name = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): TagEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): TagEntity
    {
        $this->name = $name;
        return $this;
    }
}
