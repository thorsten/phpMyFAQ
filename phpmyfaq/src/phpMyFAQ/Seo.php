<?php

/**
 * All SEO relevant stuff.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2014-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
     * @var Configuration
     */
    private $config;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * @param  string $action
     * @return mixed
     */
    public function getMetaRobots(string $action)
    {
        switch ($action) {
            case 'main':
                return $this->config->get('seo.metaTagsHome');

            case 'faq':
                return $this->config->get('seo.metaTagsFaqs');

            case 'show':
                return $this->config->get('seo.metaTagsCategories');

            default:
                return $this->config->get('seo.metaTagsPages');
        }
    }
}
