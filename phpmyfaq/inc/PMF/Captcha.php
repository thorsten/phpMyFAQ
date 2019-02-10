<?php

/**
 * The phpMyFAQ Captcha class.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thomas Zeithaml <seo@annatom.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2006-02-04
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Captcha.
 *
 * @category  phpMyFAQ
 *
 * @author    Thomas Zeithaml <seo@annatom.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2006-02-04
 */
class PMF_Captcha
{
    /**
     * @var PMF_Configuration
     */
    private $_config = null;

    /**
     * The phpMyFAQ session id.
     *
     * @var string
     */
    private $sids;

    /**
     * Array of fonts.
     *
     * @var array
     */
    private $fonts = [];

    /**
     * The captcha code.
     *
     * @var string
     */
    private $code = '';

    /**
     * Array of characters.
     *
     * @var array
     */
    private $letters = array(
        '1', '2', '3', '4', '5', '6', '7', '8', '9',
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I',
        'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
        'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
    );

    /**
     * Length of the captcha code.
     *
     * @var int
     */
    public $caplength = 6;

    /**
     * Width of the image.
     *
     * @var int
     */
    private $width = 165;

    /**
     * Height of the image.
     *
     * @var int
     */
    private $height = 40;

    /**
     * JPEG quality in percents.
     *
     * @var int
     */
    private $quality = 60;

    /**
     * Random background color RGB components.
     *
     * @var array
     */
    private $_backgroundColor;

    /**
     * Generated image.
     *
     * @var resource
     */
    private $img;

    /**
     * The user agent string.
     *
     * @var string
     */
    private $userAgent;

    /**
     * Timestamp.
     *
     * @var int
     */
    private $timestamp;

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Captcha
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'];
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->fonts = $this->getFonts();
        $this->timestamp = $_SERVER['REQUEST_TIME'];
    }

    //
    // public functions
    //

    /**
     * Setter for session id.
     *
     * @param int $sid session id
     */
    public function setSessionId($sid)
    {
        $this->sids = $sid;
    }

    /**
     * Setter for the captcha code length.
     *
     * @param int $length Length of captch code
     */
    public function setCodeLength($length = 6)
    {
        $this->caplength = $length;
    }

    /**
     * Gives the HTML output code for the Captcha.
     *
     * @param string $action The action parameter
     *
     * @return string
     */
    public function printCaptcha($action)
    {
        $output = sprintf(
            '<img id="captchaImage" src="%s?%saction=%s&amp;gen=img&amp;ck=%s" height="%d" width="%d" alt="%s" title="%s">',
            $_SERVER['SCRIPT_NAME'],
            $this->sids,
            $action,
            $_SERVER['REQUEST_TIME'],
            $this->height,
            $this->width,
            'Chuck Norris has counted to infinity. Twice.',
            'click to refresh'
        );

        return $output;
    }

    /**
     * Draw the Captcha.
     */
    public function showCaptchaImg()
    {
        $this->createBackground();
        $this->drawlines();
        $this->generateCaptchaCode($this->caplength);
        $this->drawText();
        if (function_exists('imagejpeg')) {
            header('Content-Type: image/jpeg');
            imagejpeg($this->img, null, (int) $this->quality);
        } elseif (function_exists('imagegif')) {
            header('Content-Type: image/gif');
            imagegif($this->img);
        }
        imagedestroy($this->img);
    }

    /**
     * Gets the Captcha from the DB.
     *
     * @return string
     */
    public function getCaptchaCode()
    {
        $query = sprintf('SELECT id FROM %sfaqcaptcha', PMF_Db::getTablePrefix());
        $result = $this->_config->getDb()->query($query);
        while ($row = $this->_config->fetchArray($result)) {
            $this->code = $row['id'];
        }

        return $this->code;
    }

    /**
     * Validate the Captcha.
     *
     * @param string $captchaCode Captcha code
     *
     * @return bool
     */
    public function validateCaptchaCode($captchaCode)
    {
        // Sanity check
        if (0 == PMF_String::strlen($captchaCode)) {
            return false;
        }

        $captchaCode = PMF_String::strtoupper($captchaCode);
        // Help the user: treat "0" (ASCII 48) like "O" (ASCII 79)
        //                if "0" is not in the realm of captcha code letters
        if (!in_array('0', $this->letters)) {
            $captchaCode = str_replace('0', 'O', $captchaCode);
        }
        // Sanity check
        for ($i = 0; $i < PMF_String::strlen($captchaCode); ++$i) {
            if (!in_array($captchaCode[$i], $this->letters)) {
                return false;
            }
        }
        // Search for this Captcha in the db
        $query = sprintf("
            SELECT
                id
            FROM
                %sfaqcaptcha
            WHERE
                id = '%s'",
            PMF_Db::getTablePrefix(),
            $this->_config->getDb()->escape($captchaCode));

        if ($result = $this->_config->getDb()->query($query)) {
            $num = $this->_config->getDb()->numRows($result);
            if ($num > 0) {
                $this->code = $captchaCode;
                $this->removeCaptcha($captchaCode);

                return true;
            }
        }

        return false;
    }

    /**
     * This function checks the provided captcha code
     * if the captcha code spam protection has been activated from the general PMF configuration.
     *
     * @param string $code Captcha Code
     *
     * @return bool
     */
    public function checkCaptchaCode($code)
    {
        if ($this->_config->get('spam.enableCaptchaCode')) {
            return $this->validateCaptchaCode($code);
        } else {
            return true;
        }
    }

    //
    // private functions
    //

    /**
     * Draw random lines.
     *
     * @return resource
     */
    private function drawlines()
    {
        $color1 = rand(150, 185);
        $color2 = rand(185, 225);
        $nextline = 4;
        $w1 = 0;
        $w2 = 0;

        for ($x = 0; $x < $this->width; $x += (int) $nextline) {
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
            } else {
                $nextline += rand(1, 7);
            }
            $w1 += rand(-4, 4);
            $w2 += rand(-4, 4);
        }

        return $this->img;
    }

    /**
     * Draw the Text.
     *
     * @return resource
     */
    private function drawText()
    {
        $len = PMF_String::strlen($this->code);
        $w1 = 15;
        $w2 = $this->width / ($len + 1);

        for ($p = 0; $p < $len; ++$p) {
            $letter = $this->code[$p];
            if (count($this->fonts) > 0) {
                $font = $this->fonts[rand(0, count($this->fonts) - 1)];
            }
            $size = rand(20, $this->height / 2.2);
            $rotation = rand(-23, 23);
            $y = rand($size + 3, $this->height - 5);
            // $w1 += rand(- $this->width / 90, $this->width / 40 );
            $x = $w1 + $w2 * $p;
            $c1 = []; // fore char color
            $c2 = []; // back char color
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
                imagettftext($this->img, $size, $rotation, $x,     $y - 2,   $c1, $font, $letter);
            } else {
                $size = 5;
                $c3 = imagecolorallocate($this->img, 0, 0, 255);
                $x = 20;
                $y = 12;
                $s = 30;
                imagestring($this->img, $size, $x + 1 + ($s * $p), $y + 1, $letter, $c3);
                imagestring($this->img, $size, $x + ($s * $p),     $y,   $letter, $c1);
            }
        }

        return $this->img;
    }

    /**
     * Create the background.
     *
     * @return resource
     */
    private function createBackground()
    {
        $this->img = imagecreate($this->width, $this->height);
        $this->_backgroundColor['r'] = rand(220, 255);
        $this->_backgroundColor['g'] = rand(220, 255);
        $this->_backgroundColor['b'] = rand(220, 255);

        $colorallocate = imagecolorallocate(
            $this->img,
            $this->_backgroundColor['r'],
            $this->_backgroundColor['g'],
            $this->_backgroundColor['b']
        );

        imagefilledrectangle($this->img, 0, 0, $this->width, $this->height, $colorallocate);

        return $this->img;
    }

    /**
     * Generate a Captcha Code.
     *
     * @param int $caplength Length of captch code
     *
     * @return string
     */
    private function generateCaptchaCode($caplength)
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
        for ($i = 1; $i <= $caplength; ++$i) {
            $j = floor(rand(0, 34));
            $this->code .= $this->letters[$j];
        }
        if (!$this->saveCaptcha()) {
            return $this->generateCaptchaCode($caplength);
        }

        return $this->code;
    }

    /**
     * Save the Captcha.
     *
     * @return bool
     */
    private function saveCaptcha()
    {
        $select = sprintf("
           SELECT 
               id 
           FROM 
               %sfaqcaptcha 
           WHERE 
               id = '%s'",
           PMF_Db::getTablePrefix(),
           $this->code
        );

        $result = $this->_config->getDb()->query($select);

        if ($result) {
            $num = $this->_config->getDb()->numRows($result);
            if ($num > 0) {
                return false;
            } else {
                $insert = sprintf("
                    INSERT INTO 
                        %sfaqcaptcha 
                    (id, useragent, language, ip, captcha_time) 
                        VALUES 
                    ('%s', '%s', '%s', '%s', %d)",
                    PMF_Db::getTablePrefix(),
                    $this->code,
                    $this->userAgent,
                    $this->_config->getLanguage()->getLanguage(),
                    $this->ip,
                    $this->timestamp
                );

                $this->_config->getDb()->query($insert);

                return true;
            }
        }

        return false;
    }

    /**
     * Remove the Captcha.
     *
     * @param string $captchaCode Captch code
     */
    private function removeCaptcha($captchaCode = null)
    {
        if ($captchaCode == null) {
            $captchaCode = $this->code;
        }
        $query = sprintf("DELETE FROM %sfaqcaptcha WHERE id = '%s'", PMF_Db::getTablePrefix(), $captchaCode);
        $this->_config->getDb()->query($query);
    }

    /**
     * Delete old captcha records.
     *
     * During normal use the <b>faqcaptcha</b> table would be empty, on average:
     * each record is created when a captcha image is showed to the user
     * and deleted upon a successful matching, so, on average, a record
     * in this table is probably related to a spam attack.
     *
     * @param int $time The time (sec) to define a captcha code old and ready 
     *                  to be deleted (default: 1 week)
     */
    private function garbageCollector($time = 604800)
    {
        $delete = sprintf('
            DELETE FROM 
                %sfaqcaptcha 
            WHERE 
                captcha_time < %d',
            PMF_Db::getTablePrefix(),
            $_SERVER['REQUEST_TIME'] - $time
        );

        $this->_config->getDb()->query($delete);

        $delete = sprintf("
            DELETE FROM
                %sfaqcaptcha
            WHERE
                useragent = '%s' AND language = '%s' AND ip = '%s'",
            PMF_Db::getTablePrefix(),
            $this->userAgent,
            $this->_config->getLanguage()->getLanguage(),
            $this->ip
        );

        $this->_config->getDb()->query($delete);
    }

    /**
     * Get Fonts.
     *
     * @return array
     */
    private function getFonts()
    {
        return glob(PMF_INCLUDE_DIR.'/fonts/*.ttf');
    }
}
