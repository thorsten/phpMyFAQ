<?php
/**
 * PMF_Search Test case
 *
 * @package    phpMyFAQ
 * @subpackage Tests
 * @license    MPL
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-08-31
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

require_once '../../inc/Search.php';
require_once '../../inc/Db.php';
require_once '../../inc/Language.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * PMF_Search test case.
 * 
 * @package    phpMyFAQ
 * @subpackage Tests
 * @license    MPL
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-08-31
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
 */
class SearchTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var PMF_Search
	 */
	private $PMF_Search;
	
	/**
	 * Prepares the environment before running a test.
	 * 
	 */
	protected function setUp()
	{
		parent::setUp ();
		
		$this->PMF_Search = new PMF_Search();
		$this->PMF_Search->setCategory(42);
	}
	
	/**
	 * Cleans up the environment after running a test.
	 * 
	 */
	protected function tearDown()
	{
		$this->PMF_Search = null;
		
		parent::tearDown ();
	}
	
	/**
	 * Constructs the test case.
	 * 
	 */
	public function __construct()
	{

	}
		
	/**
	 * Tests PMF_Search->getCategory()
	 */
	public function testGetCategory()
	{
		$categoryId = $this->PMF_Search->getCategory();
        $this->assertEquals(42, $categoryId);
	}
	
	/**
	 * Tests PMF_Search->setCategory()
	 * 
	 */
	public function testSetCategory()
	{
		$this->PMF_Search->setCategory(23);
	
        $categoryId = $this->PMF_Search->getCategory();
        $this->assertEquals(23, $categoryId);
	}

}

