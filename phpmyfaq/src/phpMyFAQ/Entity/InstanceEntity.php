<?php

/**
 * The Instance entity class.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Entity
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-02-22
 */

namespace phpMyFAQ\Entity;

class InstanceEntity
{
    /** @var string */
    private string $url;

    /** @var string */
    private string $instance;

    /** @var string */
    private string $comment;

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return InstanceEntity
     */
    public function setUrl(string $url): InstanceEntity
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getInstance(): string
    {
        return $this->instance;
    }

    /**
     * @param string $instance
     * @return InstanceEntity
     */
    public function setInstance(string $instance): InstanceEntity
    {
        $this->instance = $instance;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return InstanceEntity
     */
    public function setComment(string $comment): InstanceEntity
    {
        $this->comment = $comment;
        return $this;
    }
}
