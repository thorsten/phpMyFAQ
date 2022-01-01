<?php

/**
 * The phpMyFAQ Captcha class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thomas Zeithaml <seo@annatom.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-02-04
 */

namespace phpMyFAQ;

use Exception;

/**
 * Class Captcha
 *
 * @package phpMyFAQ
 */
class Captcha
{
    /**
     * Length of the captcha code.
     *
     * @var int
     */
    public $captchaLength = 6;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * The phpMyFAQ session id.
     *
     * @var string
     */
    private $sids;

    /** @var bool */
    private $userIsLoggedIn = false;

    /**
     * Array of fonts.
     * @var array
     */
    private $fonts;

    /**
     * The captcha code.
     * @var string
     */
    private $code = '';

    /**
     * Array of characters.
     * @var array
     */
    private $letters = [
        '1',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
        'A',
        'B',
        'C',
        'D',
        'E',
        'F',
        'G',
        'H',
        'I',
        'J',
        'K',
        'L',
        'M',
        'N',
        'O',
        'P',
        'Q',
        'R',
        'S',
        'T',
        'U',
        'V',
        'W',
        'X',
        'Y',
        'Z',
    ];

    /**
     * Width of the image.
     * @var int
     */
    private $width = 165;

    /**
     * Height of the image.
     * @var int
     */
    private $height = 40;

    /**
     * JPEG quality in percents.
     * @var int
     */
    private $quality = 60;

    /** @var array */
    private $backgroundColor;

    /** @var resource */
    private $img;

    /** @var string */
    private $userAgent;

    /** @var int */
    private $timestamp;

    /** @var string */
    private $ip;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'];
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->fonts = $this->getFonts();
        $this->timestamp = $_SERVER['REQUEST_TIME'];
    }

    /**
     * Get Fonts.
     *
     * @return array
     */
    private function getFonts(): array
    {
        return glob(PMF_ROOT_DIR . '/assets/fonts/*.ttf');
    }

    /**
     * Setter for session id.
     *
     * @param string $sid session id
     * @return Captcha
     */
    public function setSessionId(string $sid): Captcha
    {
        $this->sids = $sid;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUserIsLoggedIn(): bool
    {
        return $this->userIsLoggedIn;
    }

    /**
     * @param bool $userIsLoggedIn
     * @return Captcha
     */
    public function setUserIsLoggedIn(bool $userIsLoggedIn): Captcha
    {
        $this->userIsLoggedIn = $userIsLoggedIn;
        return $this;
    }

    /**
     * Gives the HTML output code for the Captcha.
     *
     * @param string $action The action parameter
     *
     * @return string
     */
    public function renderCaptchaImage(string $action): string
    {
        return sprintf(
            '<img id="captchaImage" src="%s?%saction=%s&amp;gen=img&amp;ck=%s" height="%d" width="%d" alt="%s">',
            $_SERVER['SCRIPT_NAME'],
            $this->sids,
            $action,
            $_SERVER['REQUEST_TIME'],
            $this->height,
            $this->width,
            'Chuck Norris has counted to infinity. Twice.'
        );
    }

    /**
     * Draw the Captcha.
     *
     * @throws Exception
     */
    public function drawCaptchaImage()
    {
        $this->createBackground();
        $this->drawLines();
        $this->generateCaptchaCode($this->captchaLength);
        $this->drawText();
        if (function_exists('imagejpeg')) {
            header('Content-Type: image/jpeg');
            imagejpeg($this->img, null, (int)$this->quality);
        } elseif (function_exists('imagegif')) {
            header('Content-Type: image/gif');
            imagegif($this->img);
        }
        imagedestroy($this->img);
    }

    /**
     * Create the background.
     *
     * @return resource
     * @throws Exception
     */
    private function createBackground()
    {
        $this->img = imagecreate($this->width, $this->height);
        $this->backgroundColor['r'] = random_int(222, 255);
        $this->backgroundColor['g'] = random_int(222, 255);
        $this->backgroundColor['b'] = random_int(222, 255);

        $colorAllocate = imagecolorallocate(
            $this->img,
            $this->backgroundColor['r'],
            $this->backgroundColor['g'],
            $this->backgroundColor['b']
        );

        imagefilledrectangle($this->img, 0, 0, $this->width, $this->height, $colorAllocate);

        return $this->img;
    }

    /**
     * Draw random lines.
     *
     * @return resource
     * @throws Exception
     */
    private function drawLines()
    {
        $color1 = random_int(150, 185);
        $color2 = random_int(185, 225);
        $nextLine = 4;
        $w1 = 0;
        $w2 = 0;

        for ($x = 0; $x < $this->width; $x += $nextLine) {
            if ($x < $this->width) {
                imageline($this->img, $x + $w1, 0, $x + $w2, $this->height - 1, random_int($color1, $color2));
            }
            if ($x < $this->height) {
                imageline($this->img, 0, $x - $w2, $this->width - 1, $x - $w1, random_int($color1, $color2));
            }
            if (function_exists('imagettftext') && (count($this->fonts) > 0)) {
                $nextLine += random_int(-5, 7);
                if ($nextLine < 1) {
                    $nextLine = 2;
                }
            } else {
                $nextLine += random_int(1, 7);
            }
            $w1 += random_int(-4, 4);
            $w2 += random_int(-4, 4);
        }

        return $this->img;
    }

    /**
     * Generate a Captcha Code.
     * Start garbage collector for removing old (==unresolved) captcha codes
     * Note that we would like to avoid performing any garbaging of old records
     * because these data could be used as a database for collecting ip addresses,
     * eventually organizing them in subnetwork addresses, in order to use
     * them as an input for PMF IP banning.
     * This because we always perform these 3 checks on the public forms
     * in which captcha code feature is attached:
     *   1. Check against IP/Network address
     *   2. Check against banned words
     *   3. Check against the captcha code
     * so you could ban those "users" at the address level (1.).
     * If you want to look over your current data you could use this SQL query below:
     *   SELECT DISTINCT ip, useragent, COUNT(ip) AS times
     *   FROM faqcaptcha
     *   GROUP BY ip
     *   ORDER BY times DESC
     * to find out *bots and human attempts
     *
     * @param int $capLength Length of captcha code
     * @return string
     * @throws Exception
     */
    private function generateCaptchaCode(int $capLength): string
    {
        $this->garbageCollector();

        // Create the captcha code
        for ($i = 1; $i <= $capLength; ++$i) {
            $this->code .= $this->letters[random_int(0, 34)];
        }
        if (!$this->saveCaptcha()) {
            return $this->generateCaptchaCode($capLength);
        }

        return $this->code;
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
    private function garbageCollector(int $time = 604800)
    {
        $delete = sprintf(
            '
            DELETE FROM 
                %sfaqcaptcha 
            WHERE 
                captcha_time < %d',
            Database::getTablePrefix(),
            $_SERVER['REQUEST_TIME'] - $time
        );

        $this->config->getDb()->query($delete);

        $delete = sprintf(
            "
            DELETE FROM
                %sfaqcaptcha
            WHERE
                useragent = '%s' AND language = '%s' AND ip = '%s'",
            Database::getTablePrefix(),
            $this->userAgent,
            $this->config->getLanguage()->getLanguage(),
            $this->ip
        );

        $this->config->getDb()->query($delete);
    }

    /**
     * Save the Captcha.
     *
     * @return bool
     */
    private function saveCaptcha(): bool
    {
        $select = sprintf(
            "
           SELECT 
               id 
           FROM 
               %sfaqcaptcha 
           WHERE 
               id = '%s'",
            Database::getTablePrefix(),
            $this->code
        );

        $result = $this->config->getDb()->query($select);

        if ($result) {
            $num = $this->config->getDb()->numRows($result);
            if ($num > 0) {
                return false;
            } else {
                $insert = sprintf(
                    "
                    INSERT INTO 
                        %sfaqcaptcha 
                    (id, useragent, language, ip, captcha_time) 
                        VALUES 
                    ('%s', '%s', '%s', '%s', %d)",
                    Database::getTablePrefix(),
                    $this->code,
                    $this->userAgent,
                    $this->config->getLanguage()->getLanguage(),
                    $this->ip,
                    $this->timestamp
                );

                $this->config->getDb()->query($insert);

                return true;
            }
        }

        return false;
    }

    /**
     * Draw the Text.
     *
     * @return resource
     * @throws Exception
     */
    private function drawText()
    {
        $codeLength = Strings::strlen($this->code);
        $numFonts = count($this->fonts);
        $w1 = 15;
        $w2 = $this->width / ($codeLength + 1);

        for ($p = 0; $p < $codeLength; ++$p) {
            $letter = $this->code[$p];
            $size = random_int(18, $this->height - 5);
            $rotation = random_int(-23, 23);
            $y = random_int($size, $this->height + 5);
            $x = $w1 + $w2 * $p;
            $foreColor = [];
            $backColor = [];

            do {
                $foreColor['r'] = random_int(30, 199);
            } while ($foreColor['r'] === $this->backgroundColor['r']);
            do {
                $foreColor['g'] = random_int(30, 199);
            } while ($foreColor['g'] === $this->backgroundColor['g']);
            do {
                $foreColor['b'] = random_int(30, 199);
            } while ($foreColor['b'] === $this->backgroundColor['b']);
            $colorOne = imagecolorallocate($this->img, $foreColor['r'], $foreColor['g'], $foreColor['b']);

            do {
                $backColor['r'] = ((int)$foreColor['r'] < 100 ? (int)$foreColor['r'] * 2 : random_int(30, 99));
            } while (($backColor['r'] === $this->backgroundColor['r']) && ($backColor['r'] === $foreColor['r']));
            do {
                $backColor['g'] = ((int)$foreColor['g'] < 100 ? (int)$foreColor['g'] * 2 : random_int(30, 199));
            } while (($backColor['g'] === $this->backgroundColor['g']) && ($backColor['g'] === $foreColor['g']));
            do {
                $backColor['b'] = ((int)$foreColor['b'] < 100 ? (int)$foreColor['b'] * 2 : random_int(90, 199));
            } while (($backColor['b'] === $this->backgroundColor['b']) && ($backColor['b'] === $foreColor['b']));
            $colorTwo = imagecolorallocate($this->img, $backColor['r'], $backColor['g'], $backColor['b']);

            // Add the letter
            if (function_exists('imagettftext') && ($numFonts > 0)) {
                $font = $this->fonts[random_int(0, $numFonts - 1)];
                imagettftext($this->img, $size, $rotation, $x + 2, $y, $colorOne, $font, $letter);
                imagettftext($this->img, $size, $rotation, $x + 1, $y + 1, $colorOne, $font, $letter);
                imagettftext($this->img, $size, $rotation, $x, $y - 2, $colorTwo, $font, $letter);
            } else {
                $size = 5;
                $c3 = imagecolorallocate($this->img, 0, 0, 255);
                $x = 20;
                $y = 12;
                $s = 30;
                imagestring($this->img, $size, $x + 1 + ($s * $p), $y + 1, $letter, $c3);
                imagestring($this->img, $size, $x + ($s * $p), $y, $letter, $colorOne);
            }
        }

        return $this->img;
    }

    /**
     * This function checks the provided captcha code
     * if the captcha code spam protection has been activated from the general PMF configuration.
     *
     * @param string $code Captcha Code
     * @return bool
     */
    public function checkCaptchaCode(string $code): bool
    {
        if ($this->isUserIsLoggedIn()) {
            return true;
        }
        if ($this->config->get('spam.enableCaptchaCode')) {
            return $this->validateCaptchaCode($code);
        } else {
            return true;
        }
    }

    /**
     * Validate the Captcha.
     *
     * @param string $captchaCode Captcha code
     * @return bool
     */
    public function validateCaptchaCode(string $captchaCode): bool
    {
        // Sanity check
        if (0 === Strings::strlen($captchaCode)) {
            return false;
        }

        $captchaCode = Strings::strtoupper($captchaCode);
        // Help the user: treat "0" (ASCII 48) like "O" (ASCII 79)
        //                if "0" is not in the realm of captcha code letters
        if (!in_array('0', $this->letters)) {
            $captchaCode = str_replace('0', 'O', $captchaCode);
        }
        // Sanity check
        for ($i = 0; $i < Strings::strlen($captchaCode); ++$i) {
            if (!in_array($captchaCode[$i], $this->letters)) {
                return false;
            }
        }
        // Search for this Captcha in the db
        $query = sprintf(
            "
            SELECT
                id
            FROM
                %sfaqcaptcha
            WHERE
                id = '%s'",
            Database::getTablePrefix(),
            $this->config->getDb()->escape($captchaCode)
        );

        if ($result = $this->config->getDb()->query($query)) {
            $num = $this->config->getDb()->numRows($result);
            if ($num > 0) {
                $this->code = $captchaCode;
                $this->removeCaptcha($captchaCode);

                return true;
            }
        }

        return false;
    }

    /**
     * Remove the Captcha.
     *
     * @param string $captchaCode Captcha code
     */
    private function removeCaptcha($captchaCode = null)
    {
        if ($captchaCode == null) {
            $captchaCode = $this->code;
        }
        $query = sprintf("DELETE FROM %sfaqcaptcha WHERE id = '%s'", Database::getTablePrefix(), $captchaCode);
        $this->config->getDb()->query($query);
    }
}
