<?php
/**
 * Bar image generation
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2009-03-30
 * @version   SVN: $Id$
 * @copyright 2009 phpMyFAQ Team
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
 * PMF_Bar
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2009-03-30
 * @version   SVN: $Id$
 * @copyright 2009 phpMyFAQ Team
 */
class PMF_Bar
{
	/**
	 * Image resource
	 * 
	 * @var resource
	 */
	private $image = null;
	
	/**
	 * Number
	 * 
	 * @var float
	 */
	private $number = 0;
	
	/**
	 * Colored image
	 * 
	 * @var boolean
	 */
	private $colored = false;
	
	/**
	 * Quartiles
	 * 
	 * @var array
	 */
	private $quartiles = array(25, 50, 75);
	
	/**
	 * Constructor
	 *
	 * @param  float   $number  Number for the bar
	 * @param  boolean $colored Image colored? default: false
	 * @return void
	 */
	public function __construct($number, $colored = false)
	{
		$this->number  = $number;
		$this->colored = $colored;
	}
	
	/**
	 * Sets the quartiles
	 * 
	 * @param  array $quartiles Quartiles
	 * @return void 
	 */
	public function setQuartiles(Array $quartiles)
	{
		$this->quartiles = $quartiles;
	}
	
	/**
	 * Returns the quartiles
	 * 
	 * @return array
	 */
	public function getQuartiles()
	{
		return $this->quartiles;
	}
	
	/**
	 * Rendering of the image
	 * 
	 * @return boolean
	 */
	public function renderImage()
	{
		header ('Content-type: image/png');
        imagepng($this->image);
	}
}