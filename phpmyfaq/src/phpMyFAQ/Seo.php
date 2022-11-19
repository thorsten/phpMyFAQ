<?php

/**
 * All SEO relevant stuff.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2014-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-08-31
 */

namespace phpMyFAQ;

/**
 * Class Seo
 *
 * @package phpMyFAQ
 */
class Seo
{
    /**
     * Constructor.
     */
    public function __construct(private Configuration $config)
    {
    }

    /**
     * @return mixed
     */
    public function getMetaRobots(string $action)
    {
        return match ($action) {
            'main' => $this->config->get('seo.metaTagsHome'),
            'faq' => $this->config->get('seo.metaTagsFaqs'),
            'show' => $this->config->get('seo.metaTagsCategories'),
            default => $this->config->get('seo.metaTagsPages'),
        };
    }
}
