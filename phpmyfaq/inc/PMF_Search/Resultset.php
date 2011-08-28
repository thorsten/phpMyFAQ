<?php
/**
 * Implements resultsets for phpMyFAQ search classes
 *
 * PHP Version 5.2
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
 * @package   PMF_Search
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Search_Resultset
 *
 * @category  phpMyFAQ
 * @package   PMF_Search
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */
class PMF_Search_Resultset
{
    /**
     * "Raw" search resultset without permission checks and with possible
     * duplicates
     * 
     * @var array
     */
    protected $rawResultset = array();
    
    /**
     * "Reviewed" search resultset with checked permissions and without 
     * duplicates
     * 
     * @var array
     */
    protected $reviewedResultset = array();
    
    /**
     * Ordering of resultset
     * 
     * @var string
     */
    protected $ordering;
    
    /**
     * Number of search results
     * 
     * @var integer
     */
    protected $numberOfResults = 0;
    
    /**
     * PMF_User object
     * 
     * @var PMF_User
     */
    protected $user = null;
    
    /**
     * PMF_Faq object
     * 
     * @var PMF_Faq
     */
    protected $faq = null;
    
    /**
     * Constructor
     * 
     * @param PMF_User $user PMF_User object
     * @param PMF_Faq  $faq  PMF_Faq object
     * 
     * @return PMF_Search_Resultset
     */
    public function __construct(PMF_User $user, PMF_Faq $faq)
    {
        $this->user = $user;
        $this->faq  = $faq;
    }
    
    /**
     * Check on user and group permissions and on duplicate FAQs
     * 
     * @param array $resultset Array with search results
     * 
     * @return void
     */
    public function reviewResultset(Array $resultset)
    {
        $this->setResultset($resultset);
        
        $duplicateResults = array();
        $currentUserId    = $this->user->getUserId();
        if ('medium' == PMF_Configuration::getInstance()->get('security.permLevel')) {
            $currentGroupIds = $this->user->perm->getUserGroups($currentUserId);
        }

        foreach ($this->rawResultset as $index => $result) {
            
            $permission = false;
            // check permissions for groups
            if ('medium' == PMF_Configuration::getInstance()->get('security.permLevel')) {
                $groupPermission = $this->faq->getPermission('group', $result->id);
                if (count($groupPermission) && in_array($groupPermission[0], $currentGroupIds)) {
                    $permission = true;
                }
            }
            // check permission for user
            if ($permission || 'basic' == PMF_Configuration::getInstance()->get('security.permLevel')) {
                $userPermission = $this->faq->getPermission('user', $result->id);
                if (in_array(-1, $userPermission) || in_array($this->user->getUserId(), $userPermission)) {
                    $permission = true;
                } else {
                    $permission = false;
                }
            }
            
            // check on duplicates
            if (!isset($duplicateResults[$result->id])) {
                $duplicateResults[$result->id] = 1;
            } else {
                ++$duplicateResults[$result->id];
                continue;
            }
            
            if ($permission) {
                $this->reviewedResultset[] = $result;
            }
        }
        
        $this->setNumberOfResults($this->reviewedResultset);
    }
    
    /**
     * Sets the "raw" search results
     * 
     * @param array $resultset Array with search results
     * 
     * @return void
     */
    public function setResultset(Array $resultset)
    {
        $this->rawResultset = $resultset;
    }
    
    /**
     * Returns the "reviewd" search results
     * 
     * @param array $resultset Array with search results
     * 
     * @return void
     */
    public function getResultset()
    {
        return $this->reviewedResultset;
    }
    
    /**
     * Sets the number of search results
     * 
     * @param array $resultset Array with search results
     * 
     * @return void
     */
    public function setNumberOfResults(Array $resultset)
    {
        $this->numberOfResults = count($resultset);
    }
    
    /**
     * Returns the number search results
     * 
     * @return integer
     */
    public function getNumberOfResults()
    {
        return $this->numberOfResults;
    }
}