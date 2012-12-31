<?php
/**
 * Bar image generation
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
 * @package   PMF_Bar
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-03-30
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Bar
 *
 * @category  phpMyFAQ
 * @package   PMF_Bar
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-03-30
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
     * Text color
     * 
     * @var integer
     */
    private $textcolor = 0;
    
    /**
     * Background color
     * 
     * @var integer
     */
    private $backgroundcolor = 0;
    
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
    public function __construct($number = null, $colored = false)
    {
        $this->number  = $number;
        $this->colored = $colored;
        
        /**
         * Initalize default quartiles
         */
        $this->quartiles = array('lower_quartile' => array('percentile' => 25,
                                                           'text_color' => array(0, 0, 0),
                                                           'bar_color'  => array(255, 0, 0)),
                                 /**
                                  * Median is currently not used
                                  */
                                 'median' => array(),
                                 'upper_quartile' => array('percentile' => 75,
                                                           'text_color' => array(0, 0, 0),
                                                           'bar_color'  => array(0, 128, 0)),
                                 /**
                                  * This is the difference between the upper and lower quartiles
                                  */
                                 'interquartile_range' => array('text_color' => array(0, 0, 0),
                                                           	    'bar_color'  => array(150, 150, 150))
        
        );
    }

    /**
     * Returns the colors
     * 
     * @return boolean
     */
    public function getColored()
    {
        return $this->colored;
    }
    
    /**
     * Returns the number
     * 
     * @return float
     */
    public function getNumber()
    {
        return $this->number;
    }
    
    /**
     * Sets the color
     * 
     * @param boolean $colored Colored?
     */
    public function setColored($colored)
    {
        $this->colored = $colored;
    }
    
    /**
     * Sets the number
     * 
     * @param float $number Number
     */
    public function setNumber($number)
    {
        $this->number = $number;
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

        $this->prepareImage() || $this->resetImage();
        
        return imagepng($this->image);
    }
    
    /**
     * Prepares the image for rendering
     *
     * @return boolean
     */
    private function prepareImage()
    {
        $retval = false;
        
        $this->resetImage();
        
        if (null !== $this->number) {
            if ($this->colored) {
                if ($this->number < $this->quartiles['lower_quartile']['percentile']) {
                    list($r1, $g1, $b1) = $this->quartiles['lower_quartile']['text_color'];
                    list($r2, $g2, $b2) = $this->quartiles['lower_quartile']['bar_color'];
                } elseif ($this->number > $this->quartiles['upper_quartile']['percentile']) {
                    list($r1, $g1, $b1) = $this->quartiles['upper_quartile']['text_color'];
                    list($r2, $g2, $b2) = $this->quartiles['upper_quartile']['bar_color'];
                } elseif ($this->number <= $this->quartiles['upper_quartile']['percentile'] &&
                          $this->number >= $this->quartiles['lower_quartile']['percentile']) {
                    list($r1, $g1, $b1) = $this->quartiles['interquartile_range']['text_color'];
                    list($r2, $g2, $b2) = $this->quartiles['interquartile_range']['bar_color'];
                }
            } else {
                list($r1, $g1, $b1) = array(0, 0, 0);
                list($r2, $g2, $b2) = array(211, 211, 211);
            }
            
            $this->textcolor = imagecolorallocate ($this->image, $r1, $g1, $b1);
            $barColor        = imagecolorallocate ($this->image, $r2, $g2, $b2);
            
            $retval = imagefilledrectangle ($this->image, 0, 0, round(($this->number/100)*50), 15, $barColor);
            $retval = $retval && imagestring($this->image, 2, 1, 1, floor($this->number) . '%', $this->textcolor);
        } else {
            $retval = imagestring($this->image, 1, 5, 5, 'n/a', $this->textcolor);
        }
        
        return $retval;
    }
    
    /**
     * Resets the image
     *
     * @return void
     */
    private function resetImage()
    {
        $this->image           = imagecreate(50, 15);
        $this->backgroundcolor = imagecolorallocate ($this->image, 255, 255, 255);
        $this->textcolor       = imagecolorallocate ($this->image, 0, 0, 0);
    }
}