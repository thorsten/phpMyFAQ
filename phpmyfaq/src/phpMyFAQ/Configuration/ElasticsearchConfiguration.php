<?php

/**
 * Elasticsearch's configuration class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-30
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

readonly class ElasticsearchConfiguration
{
    /** @var string[] */
    private array $hosts;

    private string $index;

    public function __construct(string $filename)
    {
        $PMF_ES = [
            'hosts' => [],
            'index' => '',
        ];

        include $filename;

        $this->hosts = $PMF_ES['hosts'];
        $this->index = $PMF_ES['index'];
    }

    /**
     * @return string[]
     */
    public function getHosts(): array
    {
        return $this->hosts;
    }

    public function getIndex(): string
    {
        return $this->index;
    }
}
