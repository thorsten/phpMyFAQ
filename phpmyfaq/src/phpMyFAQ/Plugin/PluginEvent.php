<?php

/**
 * The main PluginEvent class
 *
 * The PluginEvent class is used to pass data between plugins and the application.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-07-10
 */

namespace phpMyFAQ\Plugin;

use Symfony\Contracts\EventDispatcher\Event;

class PluginEvent extends Event
{
    private mixed $data;
    private string $output = '';

    public function __construct(mixed $data)
    {
        $this->data = $data;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function setOutput(string $output): void
    {
        $this->output .= $output;
    }

    public function getOutput(): string
    {
        return $this->output;
    }
}
