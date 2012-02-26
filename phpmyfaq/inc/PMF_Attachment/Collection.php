<?php
/**
 * Attachment collection class 
 * 
 * PHP version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   PMF_Attachment
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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