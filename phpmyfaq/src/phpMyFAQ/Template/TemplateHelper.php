<?php

namespace phpMyFAQ\Template;

/**
 * The template helper class provides methods for extended template parsing
 * like filters
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ\Template
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2018-08-17
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Meta;

/**
 * Class TemplateHelper
 * @package phpMyFAQ\Template
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2018-08-17
 */
class TemplateHelper
{

    /**
     * @var Configuration
     */
    private $config = null;

    /**
     * @var Meta
     */
    private $meta = null;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->meta = new Meta($this->config);
    }

    /**
     * Renders all {{ var | meta }} filters.
     * @param $key
     * @return string
     */
    public function renderMetaFilter($key)
    {
        $metaData = $this->meta->getByPageId($key);

        if ($metaData->getType() === 'html') {
            return html_entity_decode($metaData->getContent());
        } else {
            $metaData->getContent();
        }
    }
}