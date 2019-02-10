<?php

/**
 * All SEO relevant stuff.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2014-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-08-31
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Report.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2014-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-08-31
 */
class PMF_Seo
{
    /**
     * @var PMF_Configuration
     */
    private $config;

    /**
     * Constructor.
     *
     * @param PMF_Configuration
     *
     * @return PMF_Seo
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $action
     *
     * @return mixed
     */
    public function getMetaRobots($action)
    {
        switch ($action) {

            case 'main':
                return $this->config->get('seo.metaTagsHome');
                break;

            case 'artikel':
                return $this->config->get('seo.metaTagsFaqs');
                break;

            case 'show':
                return $this->config->get('seo.metaTagsCategories');
                break;

            default:
                return $this->config->get('seo.metaTagsPages');
                break;
        }
    }
}
