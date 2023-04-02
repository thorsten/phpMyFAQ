<?php

/**
 * The template helper class provides methods for extended template parsing
 * like filters
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Template
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-08-17
 */

namespace phpMyFAQ\Template;

use phpMyFAQ\Configuration;

/**
 * Class TemplateHelper
 *
 * @package phpMyFAQ\Template
 */
class TemplateHelper
{
    private readonly TemplateMetaData $meta;

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
        $this->meta = new TemplateMetaData($this->config);
    }

    /**
     * Renders all {{ var | meta }} filters.
     */
    public function renderMetaFilter(string $key): string
    {
        $metaData = $this->meta->getByPageId($key);

        if ($metaData->getType() === 'html') {
            return html_entity_decode($metaData->getContent());
        } else {
            return $metaData->getContent();
        }
    }
}
