<?php
/**
 * The abstract FAQ class
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Faq
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-08-15
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
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

/**
 * PMF_Faq_Abstract
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Faq
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-08-15
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
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

abstract class PMF_Faq_Abstract 
{   
    /**
     * FAQ record id
     * 
     * @var integer
     */
    protected $id = null;
    
    /**
     * FAQ record language
     * 
     * @var string
     */
    protected $language = null;
    
    /**
     * FAQ solution id
     * 
     * @var integer
     */
    protected $solutionId = null;
    
    /**
     * FAQ revision id
     * 
     * @var integer
     */
    protected $revisionId = 0;
    
    /**
     * FAQ activation state
     * 
     * @var boolean
     */
    protected $active = false;
    
    /**
     * FAQ sticky state
     * 
     * @var boolean
     */
    protected $sticky = false;
    
    /**
     * FAQ keywords
     * 
     * @var string
     */
    protected $keywords = '';
    
    /**
     * FAQ question
     * 
     * @var string
     */
    protected $question = '';
    
    /**
     * FAQ answer
     * 
     * @var $string
     */
    protected $answer = '';
    
    /**
     * Author
     * 
     * @var string
     */
    protected $author = '';
    
    /**
     * Author's email address
     * 
     * @var string 
     */
    protected $email = '';
    
    /**
     * Comment permission
     * 
     * @var boolean
     */
    protected $comment = false;
    
    /**
     * FAQ creation date
     * 
     * @var string
     */
    protected $creationDate = '';
    
    /**
     * FAQ expiration start date
     * 
     * @var string
     */
    protected $dateStart = '';
    
    /**
     * FAQ expiration end date
     * 
     * @var string
     */
    protected $dateEnd = '';
    
    /**
     * FAQ link state
     * 
     * @var string
     */
    protected $linkState = '';
    
    /**
     * FAQ link state date
     * 
     * @var integer
     */
    protected $linkStateDate = 0;
    
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {

    }
    
}