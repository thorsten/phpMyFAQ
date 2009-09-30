<?php
/**
 * Pagination handler class
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Pagination
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-09-27
 * @copyright  2004-2009 phpMyFAQ Team
 * @version    SVN: $Id: Pagination.php,v 1.56 2008-01-26 01:02:56 thorstenr Exp $
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
 * PMF_Pagination
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Pagination
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2007-09-27
 * @copyright  2004-2009 phpMyFAQ Team
 * @version    SVN: $Id: Pagination.php,v 1.56 2008-01-26 01:02:56 thorstenr Exp $
 */
class PMF_Pagination
{

    /**
     * Base url used for links
     * 
     * @var string
     */
    protected $baseUrl = '';
    
    /**
     * Total items count
     * 
     * @var integer
     */
    protected $total = 0;
    
    /**
     * Items per page count
     * 
     * @var integer
     */
    protected $perPage = 0;
    
    /**
     * Default link template in printf format
     * 
     * @var string
     */
    protected $linkTpl = '<a href="%s">%s</a>';
    
    /**
     * Current page link template in printf format
     * 
     * @var string
     */
    protected $currentPageLinkTpl = '';
    
    /**
     * Next page link template in printf format
     * 
     * @var string
     */
    protected $nextPageLinkTpl = '';
    
    /**
     * Previous page link template in printf format
     * 
     * @var string
     */
    protected $prevPageLinkTpl = '';
    
    /**
     * First page link template in printf format
     * 
     * @var string
     */
    protected $firstPageLinkTpl = '';
    
    /**
     * Last page link template in printf format
     * 
     * @var string
     */
    protected $lastPageLinkTpl = '';
    
    /**
     * Constructor
     *
     * @param array $options initialization options,
     * possible options:
     * - baseUrl
     * - total
     * - perPage
     * - linkTpl
     * - currentPageLinkTpl
     * - nextPageLinkTpl
     * - prevPageLinkTpl
     * - firstPageLinkTpl
     * - lastPageLinkTpl
     * 
     * @return null
     */
	public function __construct($options = null)
	{
	 
	}
	
	/**
	 * Render full pagination string
	 * 
	 * @return string
	 */
	public function render()
	{
	 
	}
}
 