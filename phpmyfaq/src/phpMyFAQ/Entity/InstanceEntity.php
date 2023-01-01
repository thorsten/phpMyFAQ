<?php

/**
 * The Instance entity class.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Entity
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-02-22
 */

namespace phpMyFAQ\Entity;

class InstanceEntity
{
    private string $url;

    private string $instance;

    private string $comment;

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): InstanceEntity
    {
        $this->url = $url;
        return $this;
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function setInstance(string $instance): InstanceEntity
    {
        $this->instance = $instance;
        return $this;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): InstanceEntity
    {
        $this->comment = $comment;
        return $this;
    }
}
