<?php
/**
* $Id: Tags.php,v 1.1 2006-08-10 19:27:34 thorstenr Exp $
*
* The main Tags class
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @package      phpMyFAQ
* @since        2006-08-10
* @copyright    (c) 2006 phpMyFAQ Team
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
*/

class PMF_Tags
{
    /**
     * DB handle
     *
     * @var object
     */
    var $db;

    /**
     * Language
     *
     * @var string
     */
    var $language;
    
    /**
     * Constructor
     *
     * @param   object  PMF_Db
     * @param   string  $language
     * @since   2006-08-10
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function PMF_Tags(&$db, $language)
    {
        $this->db = &$db;
        $this->language = $language;
    }
    
    
    
}