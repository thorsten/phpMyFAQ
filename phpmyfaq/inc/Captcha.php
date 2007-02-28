<?php
/**
 * $Id: Captcha.php,v 1.10 2007-02-28 20:03:48 thorstenr Exp $
 *
 * The phpMyFAQ Captcha class
 *
 * @author      Thomas Zeithaml <seo@annatom.de>
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since       2006-02-04
 * @copyright   (c) 2006-2007 phpMyFAQ Team
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

class PMF_Captcha
{
    /**
     * The database handle
     *
     * @var mixed
     */
    var $db;

    /**
     * The phpMyFAQ session id
     *
     * @var string
     */
    var $sids;

    /**
     * Array of fonts
     *
     * @var array
     */
    var $fonts = array();

    /**
     * The captcha code
     *
     * @var string
     */
    var $code;

    /**
     * Array of characters
     *
     * @var array
     */
    var $letters;

    /**
     * Length of the captcha code
     *
     * @var integer
     */
    var $caplength;

    /**
     * Width of the image
     *
     * @var integer
     */
    var $width;

    /**
     * Height of the image
     *
     * @var integer
     */
    var $height;

    /**
     * JPEG quality in percents
     *
     * @var integer
     */
    var $quality;

    /**
     * Random background color RGB components
     *
     * @var array
     */
    var $_backgroundColor;

    /**
     * Generated image
     *
     * @var resource
     */
    var $img;

    /**
     * The user agent language
     *
     * @var string
     */
    var $language;

    /**
     * The user agent string
     *
     * @var string
     */
    var $userAgent;

    /**
     * Timestamp
     *
     * @var integer
     */
    var $timestamp;

    /**
     * Constructor
     *
     * @param   object  $db
     * @param   string  $sids
     * @param   string  $language
     * @param   integer $caplength
     * @return  void
     * @author  Thomas Zeithaml <seo@annatom.de>
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function PMF_Captcha($db, $sids, $language, $caplength = 6)
    {
        $this->db           =& $db;
        if ($sids > 0) {
            $this->sids     = $sids;
        } else {
            $this->sids     = '';
        }
        $this->language     = $language;
        $this->userAgent    = $_SERVER['HTTP_USER_AGENT'];
        $this->ip           = $_SERVER['REMOTE_ADDR'];
        $this->caplength    = $caplength;
        $this->letters      = array('1', '2', '3', '4', '5', '6', '7', '8', '9',
                                    'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I',
                                    'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
                                    'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' );
        $this->code          = '';
        $this->quality       = 60;
        $this->fonts         = $this->getFonts();
        $this->width         = 200;
        $this->height         = 40;
        $this->timestamp     = time();
    }

    //
    // public functions
    //

    /**
     * printCaptcha()
     *
     * Gives the HTML output code for the Captcha
     *
     * @param   string  The action parameter
     * @return  string
     * @access  public
     * @since   2006-02-02
     * @author  Thomas Zeithaml <info@spider-trap.de>
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function printCaptcha($action)
    {
        $output = sprintf(
            '<img src="%s?%saction=%s&amp;gen=img&amp;ck=%s" height="%d" width="%d" border="0" alt="Chuck Norris has counted to infinity. Twice." title="Chuck Norris has counted to infinity. Twice." />',
            $_SERVER['PHP_SELF'],
            $this->sids,
            $action,
            time(),
            $this->height,
            $this->width);
        return $output;
    }

    /**
     * showCaptchaImg()
     *
     * Draw the Captcha
     *
     * @return  void
     * @access  public
     * @since   2006-02-02
     * @author  Thomas Zeithaml <info@spider-trap.de>
     */
    function showCaptchaImg()
    {
        $this->createBackground();
        $this->drawlines();
        $this->generateCaptchaCode($this->caplength);
        $this->drawText();
        if (function_exists('imagepng')) {
            header('Content-Type: image/png');
            imagepng($this->img);
        } elseif (function_exists('imagejpeg')) {
            header('Content-Type: image/jpeg');
            imagejpeg($this->img, '', ( int )$this->quality);
        } elseif (function_exists('imagegif')) {
            header('Content-Type: image/gif');
            imagegif($this->img);
        }
        imagedestroy($this->img);
    }

    /**
     * getCaptchaCode()
     *
     * Gets the Captcha from the DB
     *
     * @return  string
     * @access  public
     * @since   2006-02-02
     * @author  Thomas Zeithaml <info@spider-trap.de>
     */
    function getCaptchaCode()
    {
        $query = "SELECT id FROM ".SQLPREFIX."faqcaptcha";
        $result = $this->db->query($query);
        while ($row = $this->db->fetch_assoc($result)) {
            $this->code = $row['id'];
        }

        return $this->code;
    }

    //
    // private functions
    //

    /**
    * drawlines()
    *
    * Draw random lines
    *
    * @return   img
    * @access   private
    * @since    2006-02-02
    * @author   Thomas Zeithaml <info@spider-trap.de>
    */
    function drawlines()
    {
        $color1   = rand(150, 185);
        $color2   = rand(185, 225);
        $nextline = 4;
        $w1       = 0;
        $w2       = 0;

        for ($x = 0; $x < $this->width; $x += (int)$nextline) {
            if ($x < $this->width) {
                imageline($this->img, $x + $w1, 0, $x + $w2, $this->height - 1, rand($color1, $color2));
            }
            if ($x < $this->height) {
                imageline($this->img, 0, $x - $w2, $this->width - 1, $x - $w1, rand($color1, $color2));
            }
            if (function_exists('imagettftext') && (count($this->fonts) > 0)) {
                $nextline += rand(-5, 7);
                if ($nextline < 1) {
                    $nextline = 2;
                }
            }
            else {
                $nextline += rand(1, 7);
            }
            $w1 += rand(-4, 4);
            $w2 += rand(-4, 4);
        }

        return $this->img;
    }

    /**
     * drawText()
     *
     * Draw the Text
     *
     * @return  img
     * @access  private
     * @since   2006-02-02
     * @author  Thomas Zeithaml <info@spider-trap.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function drawText()
    {
        $len = strlen($this->code);
        $w1  = 15;
        $w2  = $this->width / ($len + 1);

        for ($p = 0; $p < $len; $p++) {
            $letter = $this->code[$p];
            if (count($this->fonts) > 0) {
                $font = $this->fonts[rand(0, count($this->fonts) - 1)];
            }
            $size = rand(20, $this->height / 2.2);
            $rotation = rand(-23, 23);
            $y   = rand($size + 3, $this->height-5);
            // $w1 += rand(- $this->width / 90, $this->width / 40 );
            $x   = $w1 + $w2*$p;
            $c1 = array(); // fore char color
            $c2 = array(); // back char color
            do {
                $c1['r'] = mt_rand(30, 199);
            } while ($c1['r'] == $this->_backgroundColor['r']);
            do {
                $c1['g'] = mt_rand(30, 199);
            } while ($c1['g'] == $this->_backgroundColor['g']);
            do {
                $c1['b'] = mt_rand(30, 199);
            } while ($c1['b'] == $this->_backgroundColor['b']);
            $c1 = imagecolorallocate($this->img, $c1['r'], $c1['g'], $c1['b']);
            do {
                $c2['r'] = ($c1['r'] < 100 ? $c1['r'] * 2 : mt_rand(30, 199));
            } while (($c2['r'] == $this->_backgroundColor['r']) && ($c2['r'] == $c1['r']));
            do {
                $c2['g'] = ($c1['g'] < 100 ? $c1['g'] * 2 : mt_rand(30, 199));
            } while (($c2['g'] == $this->_backgroundColor['g']) && ($c2['g'] == $c1['g']));
            do {
                $c2['b'] = ($c1['b'] < 100 ? $c1['b'] * 2 : mt_rand(30, 199));
            } while (($c2['b'] == $this->_backgroundColor['b']) && ($c2['b'] == $c1['b']));
            $c2 = imagecolorallocate($this->img, $c2['r'], $c2['g'], $c2['b']);
            // Add the letter
            if (function_exists('imagettftext') && (count($this->fonts) > 0)) {
                imagettftext($this->img, $size, $rotation, $x + 2, $y,     $c2, $font, $letter);
                imagettftext($this->img, $size, $rotation, $x + 1, $y + 1, $c2, $font, $letter);
                imagettftext($this->img, $size, $rotation, $x,     $y-2,   $c1, $font, $letter);
            } else {
                $size = 5;
                $c3 = imagecolorallocate($this->img, 0, 0, 255);
                $x = 20;
                $y = 12;
                $s = 30;
                imagestring($this->img, $size, $x + 1 + ($s * $p), $y+1, $letter, $c3);
                imagestring($this->img, $size, $x + ($s * $p),     $y,   $letter, $c1);
            }
        }

        return $this->img;
    }

    /**
     * createBackground()
     *
     * Create the background
     *
     * @return  resource
     * @access  private
     * @since   2006-02-02
     * @author  Thomas Zeithaml <info@spider-trap.de>
     */
    function createBackground()
    {
        $this->img = imagecreate($this->width, $this->height);
        $this->_backgroundColor['r'] = rand(220, 255);
        $this->_backgroundColor['g'] = rand(220, 255);
        $this->_backgroundColor['b'] = rand(220, 255);
        $colorallocate = imagecolorallocate($this->img, $this->_backgroundColor['r'], $this->_backgroundColor['g'], $this->_backgroundColor['b']);
        imagefilledrectangle($this->img, 0, 0, $this->width, $this->height, $colorallocate);
        return $this->img;
    }

    /**
     * generateCaptchaCode()
     *
     * Generate a Captcha Code
     *
     * @param   int $caplength
     * @return  string
     * @access  private
     * @since   2006-02-02
     * @author  Thomas Zeithaml <info@spider-trap.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function generateCaptchaCode($caplength)
    {
        // Start garbage collector for removing old (==unresolved) captcha codes
        // Note that we would like to avoid performing any garbaging of old records
        // because these data could be used as a database for collecting ip addresses,
        // eventually organizing them in subnetwork addresses, in order to use
        // them as an input for PMF IP banning.
        // This because we always perform these 3 checks on the public forms
        // in which captcha code feature is attached:
        //   1. Check against IP/Network address
        //   2. Check against banned words
        //   3. Check against the captcha code
        // so you could ban those "users" at the address level (1.).
        // If you want to look over your current data you could use this SQL query below:
        //   SELECT DISTINCT ip, useragent, COUNT(ip) AS times
        //   FROM faqcaptcha
        //   GROUP BY ip
        //   ORDER BY times DESC
        // to find out *bots and human attempts
        $this->garbageCollector();

        // Create the captcha code
        for ($i = 1; $i <= $caplength; $i++) {
            $j = floor(rand(0,34));
            $this->code .= $this->letters[$j];
        }
        if (!$this->saveCaptcha()) {
            return $this->generateCaptchaCode($caplength);
        }

        return $this->code;
    }

    /**
    * saveCaptcha()
    *
    * Save the Captcha
    *
    * @return   bool
    * @access   private
    * @since    2006-02-02
    * @author   Thomas Zeithaml <info@spider-trap.de>
    * @author   Matteo Scaramuccia <matteo@scaramuccia.com>
    */
    function saveCaptcha()
    {
        if ($result = $this->db->query("SELECT id FROM ".SQLPREFIX."faqcaptcha WHERE id = '".$this->code."'")) {
            $num = $this->db->num_rows($result);
            if ($num > 0) {
                return false;
            } else {
                $query = sprintf("INSERT INTO %sfaqcaptcha (id, useragent, language, ip, captcha_time) VALUES ('%s', '%s', '%s', '%s', %d)", SQLPREFIX, $this->code, $this->userAgent, $this->language, $this->ip, $this->timestamp);
                $this->db->query( $query );
                return true;
            }
        }

        return false;
    }

    /**
     * removeCaptcha()
     *
     * Remove the Captcha
     *
     * @return  void
     * @access  private
     * @since   2006-02-18
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function removeCaptcha($captchaCode = null)
    {
        if ($captchaCode == null) {
            $captchaCode = $this->code;
        }
        $query = sprintf("DELETE FROM %sfaqcaptcha WHERE id = '%s'", SQLPREFIX, $captchaCode);
        $this->db->query($query);
    }

    /**
     * validateCaptchaCode()
     *
     * Validate the Captcha
     *
     * @param   string $captchaCode
     * @return  void
     * @access  private
     * @since   2006-02-18
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function validateCaptchaCode($captchaCode)
    {
        $captchaCode = strtoupper($captchaCode);
        // Help the user: treat "0" (ASCII 48) like "O" (ASCII 79)
        //                if "0" is not in the realm of captcha code letters
        if (!in_array("0", $this->letters)) {
            $captchaCode = str_replace("0", "O", $captchaCode);
        }
        // Sanity check
        for ($i = 0; $i < strlen( $captchaCode ); $i++) {
            if (!in_array($captchaCode[$i], $this->letters)) {
                return false;
            }
        }
        // Search for this Captcha in the db
        if ($result = $this->db->query("SELECT id FROM ".SQLPREFIX."faqcaptcha WHERE id = '".$captchaCode."'")) {
            $num = $this->db->num_rows($result);
            if ($num > 0) {
                $this->code = $captchaCode;
                $this->removeCaptcha($captchaCode);
                return true;
            }
        }

        return false;
    }

    /**
     * garbageCollector()
     *
     * Delete old captcha records.
     *
     * During normal use the <b>faqcaptcha</b> table would be empty, on average:
     * each record is created when a captcha image is showed to the user
     * and deleted upon a successful matching, so, on average, a record
     * in this table is probably related to a spam attack.
     *
     * @param   int the time (sec) to define a captcha code old and ready to be deleted (default: 1 week)
     * @return  void
     * @access  private
     * @since   2006-03-25
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function garbageCollector($time = 604800)
    {
        $query = sprintf("DELETE FROM %sfaqcaptcha WHERE captcha_time < %d", SQLPREFIX, time() - $time);
        $this->db->query($query);
    }

    /**
     * getFonts()
     *
     * Get Fonts
     *
     * @return  array
     * @access  private
     * @since   2006-02-02
     * @author  Thomas Zeithaml <info@spider-trap.de>
     */
    function getFonts()
    {
        return glob(dirname(dirname(__FILE__)).'/font/*.ttf');
    }

    /**
     * Destructor
     *
     *
     */
    function __destruct()
    {
    }
}
