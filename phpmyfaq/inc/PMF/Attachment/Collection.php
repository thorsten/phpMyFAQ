<?php

/**
 * Attachment collection class.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-12-13
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Attachment_Collection.
 *
 * @category  phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-12-13
 */
class PMF_Attachment_Collection
{
    /**
     * Configuration.
     *
     * @var PMF_Configuration
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Get an array with minimalistic attachment meta data.
     *
     * @return array
     */
    public function getBreadcrumbs()
    {
        $retval = [];

        $query = sprintf('
            SELECT
                fa.id AS id,
                fa.record_id AS record_id,
                fa.record_lang AS record_lang,
                fa.filename AS filename,
                fa.filesize AS filesize,
                fa.mime_type AS mime_type,
                fd.thema AS thema
            FROM
                %s fa
            JOIN
                %s fd
            ON
                fa.record_id = fd.id
            GROUP BY
                fa.id,fd.thema',
            PMF_Db::getTablePrefix().'faqattachment',
            PMF_Db::getTablePrefix().'faqdata'
        );

        $result = $this->config->getDb()->query($query);

        if ($result) {
            $retval = $this->config->getDb()->fetchAll($result);
        }

        return $retval;
    }
}
