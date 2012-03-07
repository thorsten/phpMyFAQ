<?php
/**
 * Attachment collection class 
 *
 * PHP version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Attachment
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-12-13
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}
                 
/**
 * PMF_Atachment_Collection
 *
 * @category  phpMyFAQ
 * @package   PMF_Attachment
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-12-13
 */
class PMF_Attachment_Collection
{
    /**
     * Database instance
     *
     * @var PMF_Db_Driver
     */
    protected $db;

    /**
     * Constructor
     *
     * @return null
     */
    public function __construct(PMF_DB_Driver $database)
    {
        $this->db = $database;
    }

    /**
     * Get an array with minimalistic attachment meta data
     *
     * @return array
     */
    public function getBreadcrumbs()
    {
        $retval = array();

        $query = sprintf("
            SELECT
                fa.id,
                fa.record_id,
                fa.record_lang,
                fa.filename,
                fa.filesize,
                fa.mime_type,
                fd.thema
            FROM
                %s fa
            JOIN
                %s fd
            ON
                fa.record_id = fd.id
            GROUP BY
                fa.id",
            SQLPREFIX . 'faqattachment',
            SQLPREFIX . 'faqdata'
        );

        $result = $this->db->query($query);
        
        if ($result) {
            $retval = $this->db->fetchAll($result);
        }
        
        return $retval;
    }
}