<?php
/**
 * Test case for PMF_Category
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
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @copyright 2010 phpMyFAQ Team
 * @since     2010-01-26
 */

require_once '../../inc/Category.php';

/**
 * PMF_Category test case
 * 
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @copyright 2010 phpMyFAQ Team
 * @since     2010-01-26
 */
class PMF_CategoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * 
     * @var PMF_Category
     */
    private $PMF_Category;
    
    /**
     * 
     * @var array
     */
    protected $data = array(
            'id'        => 1,
            'lang'      => 'de',
            'name'      => 'PHP4',
            'children'  => 0,
            'parent_id' => 0);
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
        $this->PMF_Category = new PMF_Category($this->data);
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->PMF_Category = null;
        parent::tearDown();
    }
    
    /**
     * Tests PMF_Category->__toString()
     */
    public function test__toString ()
    {
        $name = $this->PMF_Category->__toString();
        $this->assertSame('PHP4', $name);
        $this->assertFalse(empty($name));
    }
    
    /**
     * Tests PMF_Category->getLanguage()
     */
    public function testGetLanguage ()
    {
        $language = $this->PMF_Category->getLanguage();
        $this->assertSame('de', $language);
        $this->assertFalse(empty($language));
    }
    
    /**
     * Tests PMF_Category->getParentId()
     */
    public function testGetParentId ()
    {
        $parent_id = $this->PMF_Category->getParentId();
        $this->assertEquals(0, $parent_id);
    }
    /**
     * Tests PMF_Category->getId()
     */
    public function testGetId ()
    {
        $id = $this->PMF_Category->getId();
        $this->assertEquals(1, $id);
        $this->assertFalse(empty($id));
    }
    
    /**
     * Tests PMF_Category->getParent()
     */
    public function testGetParent ()
    {
        $parent = $this->PMF_Category->getParent();
        $this->assertNull($parent);
    }
    
    /**
     * Tests PMF_Category->setParent()
     */
    public function testSetParent ()
    {
        $parent = new PMF_Category($this->data, $this->PMF_Category);
        $this->PMF_Category->setParent($parent);
        $this->assertTrue($this->PMF_Category->getParent() instanceof PMF_Category);
    }
    
    /**
     * Tests PMF_Category->setChildcount()
     */
    public function testSetChildcount ()
    {
        $this->PMF_Category->setChildcount(42);
        $hasChildren = $this->PMF_Category->hasChildren();
        $this->assertTrue($hasChildren);
    }
    
    /**
     * Tests PMF_Category->hasChildren()
     */
    public function testHasChildren ()
    {
        $this->PMF_Category->setChildcount(0);
        $hasChildren = $this->PMF_Category->hasChildren();
        $this->assertFalse($hasChildren);
    }
}