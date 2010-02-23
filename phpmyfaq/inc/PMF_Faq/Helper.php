<?php
/**
 * Helper class for FAQs
 *
 * PHP Version 5.2.0
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
 * @package   PMF_Faq
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-24
 */

/**
 * PMF_Faq_Helper
 * 
 * @Faq  phpMyFAQ
 * @package   PMF_Faq
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-24
 */
class PMF_Faq_Helper extends PMF_Faq_Abstract
{
    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets the latest solution id for a FAQ record
     *
     * @return integer
     */
    public function getSolutionId()
    {
        $latestId = $nextSolutionId = 0;

        $query = sprintf("
            SELECT
                MAX(solution_id) AS solution_id
            FROM
                %sfaqdata",
            SQLPREFIX);
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }
        
        if ($row = $this->db->fetchObject($result)) {
            $latestId = $row->solution_id;
        }
        
        if ($latestId < PMF_SOLUTION_ID_START_VALUE) {
            $nextSolutionId = PMF_SOLUTION_ID_START_VALUE;
        } else {
            $nextSolutionId = $latestId + PMF_SOLUTION_ID_INCREMENT_VALUE;
        }
        
        return $nextSolutionId;
    }
}