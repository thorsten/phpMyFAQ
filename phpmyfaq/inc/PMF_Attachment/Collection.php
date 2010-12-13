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
 * @since     2010-12-13
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @copyright 2010 phpMyFAQ Team
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
 * @since     2009-08-21
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @copyright 2009-2010 phpMyFAQ Team
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
    public function __construct()
    {
        $this->db = PMF_Db::getInstance();
    }

    /**
     * Get an array with minimalistic attachment meta data
     * 
     * @param integer $start Listing start
     * @param integer $limit Listing end
     * 
     * @return array
     */
    public function getMeta($start = 0, $limit = 0)
    {
        $retval = array();
        
        $sql = str_replace(
            array('_t1_', '_t2_'), 
            array(
                SQLPREFIX . "faqattachment",
                SQLPREFIX . "faqdata"
            ),
			"SELECT 
                _t1_.record_id, _t1_.record_lang,
                _t1_.filename, _t1_.filesize, _t1_.mime_type,
                _t2_.thema
            FROM
                _t1_
            JOIN _t2_ ON _t1_.record_id = _t2_.id"
        );
        
        $result = $this->db->query($sql);
        
        if($result) {
            $retval = $this->db->fetchAll($result);
        }
        
        return $retval;
    }
}