<?php

/**
 * The phpMyFAQ Captcha class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thomas Zeithaml <seo@annatom.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-02-04
 */

namespace phpMyFAQ\Captcha;

use Exception;
use GdImage;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Strings;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Captcha
 *
 * @package phpMyFAQ
 */
class BuiltinCaptcha implements CaptchaInterface
{
    public int $captchaLength = 6;

    private bool $userIsLoggedIn = false;

    private readonly string $font;

    private string $code = '';

    /** @var string[] */
    private array $letters = [
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

    private int $width = 200;

    private int $height = 50;

    private int $quality = 80;

    /** @var int[] */
    private array $backgroundColor;

    private GdImage $gdImage;

    /** @var string */
    private readonly mixed $userAgent;

    /** @var int */
    private readonly mixed $timestamp;

    /** @var string */
    private readonly mixed $ip;

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $configuration)
    {
        $request = Request::createFromGlobals();
        $this->userAgent = $request->headers->get('user-agent');
        $this->ip = $request->getClientIp();
        $this->font = $this->getFont();
        $this->timestamp = $request->server->get('REQUEST_TIME');
    }

    /**
     * Get Fonts.
     */
    private function getFont(): string
    {
        return PMF_ROOT_DIR . '/assets/fonts/captcha.ttf';
    }

    public function isUserIsLoggedIn(): bool
    {
        return $this->userIsLoggedIn;
    }

    public function setUserIsLoggedIn(bool $userIsLoggedIn): BuiltinCaptcha
    {
        $this->userIsLoggedIn = $userIsLoggedIn;
        return $this;
    }

    /**
     * Gives the HTML output code for the Captcha.
     */
    public function renderCaptchaImage(): string
    {
        return sprintf(
            '<img id="captchaImage" class="rounded border" src="./api/captcha" height="%d" width="%d" alt="%s">',
            $this->height,
            $this->width,
            'Chuck Norris has counted to infinity. Twice.'
        );
    }

    /**
     * Returns the Captcha.
     *
     * @throws Exception
     */
    public function getCaptchaImage(): string
    {
        $this->createBackground();
        $this->drawLines();
        $this->generateCaptchaCode($this->captchaLength);
        $this->drawText();

        ob_start();
        imagejpeg($this->gdImage, null, $this->quality);
        $image = ob_get_clean();

        imagedestroy($this->gdImage);

        return $image;
    }

    /**
     * Create the background.
     *
     * @throws Exception
     */
    private function createBackground(): void
    {
        $this->gdImage = imagecreate($this->width, $this->height);
        $this->backgroundColor['r'] = random_int(210, 255);
        $this->backgroundColor['g'] = random_int(220, 255);
        $this->backgroundColor['b'] = random_int(210, 255);

        $colorAllocate = imagecolorallocate(
            $this->gdImage,
            $this->backgroundColor['r'],
            $this->backgroundColor['g'],
            $this->backgroundColor['b']
        );

        imagefilledrectangle($this->gdImage, 0, 0, $this->width, $this->height, $colorAllocate);
    }

    /**
     * Draw random lines.
     *
     * @throws Exception
     */
    private function drawLines(): void
    {
        $color1 = random_int(150, 185);
        $color2 = random_int(185, 225);
        $nextLine = 4;
        $w1 = 0;
        $w2 = 0;

        for ($x = 0; $x < $this->width; $x += $nextLine) {
            if ($x < $this->width) {
                imageline($this->gdImage, $x + $w1, 0, $x + $w2, $this->height - 1, random_int($color1, $color2));
            }

            if ($x < $this->height) {
                imageline($this->gdImage, 0, $x - $w2, $this->width - 1, $x - $w1, random_int($color1, $color2));
            }

            if (function_exists('imagettftext')) {
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
    }

    /**
     * Generate a Captcha Code.
     *
     * Start garbage collector for removing old (==unresolved) captcha codes
     * Note that we would like to avoid performing any garbaging of old records
     * because these data could be used as a database for collecting ip addresses,
     * eventually organizing them in subnetwork addresses, in order to use
     * them as an input for phpMyFAQ IP banning.
     *
     * This is because we always perform these three checks on the public forms
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
    private function garbageCollector(int $time = 604800): void
    {
        $delete = sprintf(
            '
            DELETE FROM 
                %sfaqcaptcha 
            WHERE 
                captcha_time < %d',
            Database::getTablePrefix(),
            Request::createFromGlobals()->server->get('REQUEST_TIME') - $time
        );

        $this->configuration->getDb()->query($delete);

        $delete = sprintf(
            "
            DELETE FROM
                %sfaqcaptcha
            WHERE
                useragent = '%s' AND language = '%s' AND ip = '%s'",
            Database::getTablePrefix(),
            $this->userAgent,
            $this->configuration->getLanguage()->getLanguage(),
            $this->ip
        );

        $this->configuration->getDb()->query($delete);
    }

    /**
     * Save the Captcha.
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

        $result = $this->configuration->getDb()->query($select);

        if ($result) {
            $num = $this->configuration->getDb()->numRows($result);
            if ($num > 0) {
                return false;
            }

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
                $this->configuration->getLanguage()->getLanguage(),
                $this->ip,
                $this->timestamp
            );
            $this->configuration->getDb()->query($insert);
            return true;
        }

        return false;
    }

    /**
     * Draw the Text.
     *
     * @throws Exception
     */
    private function drawText(): void
    {
        $codeLength = Strings::strlen($this->code);
        $w1 = 25;
        $w2 = floor($this->width / ($codeLength + 1));

        for ($p = 0; $p < $codeLength; ++$p) {
            $letter = $this->code[$p];
            $size = random_int(16, $this->height - 3);
            $rotation = random_int(-10, 10);
            $y = random_int($size, $this->height + 5);
            $x = $w1 + $w2 * $p;
            $foreColor = [];

            do {
                $foreColor['r'] = random_int(30, 199);
            } while ($foreColor['r'] === $this->backgroundColor['r']);

            do {
                $foreColor['g'] = random_int(30, 199);
            } while ($foreColor['g'] === $this->backgroundColor['g']);

            do {
                $foreColor['b'] = random_int(30, 199);
            } while ($foreColor['b'] === $this->backgroundColor['b']);

            $colorOne = imagecolorallocate($this->gdImage, $foreColor['r'], $foreColor['g'], $foreColor['b']);

            // Add the letter
            if (function_exists('imagettftext')) {
                imagettftext($this->gdImage, $size, $rotation, (int)$x + 2, $y, $colorOne, $this->font, $letter);
                imagettftext($this->gdImage, $size, $rotation, (int)$x + 1, $y + 1, $colorOne, $this->font, $letter);
                imagettftext($this->gdImage, $size, $rotation, (int)$x, $y + 2, $colorOne, $this->font, $letter);
            } else {
                $size = 5;
                $c3 = imagecolorallocate($this->gdImage, 0, 0, 255);
                $x = 20;
                $y = 12;
                $s = 30;
                imagestring($this->gdImage, $size, $x + 1 + ($s * $p), $y + 1, $letter, $c3);
                imagestring($this->gdImage, $size, $x + ($s * $p), $y, $letter, $colorOne);
            }
        }
    }

    /**
     * This function checks the provided captcha code
     * if the captcha code spam protection has been activated from the general PMF configuration.
     *
     * @param string|null $code Captcha Code
     */
    public function checkCaptchaCode(?string $code = null): bool
    {
        if ($this->isUserIsLoggedIn()) {
            return true;
        }

        if ($this->configuration->get('spam.enableCaptchaCode')) {
            return $this->validateCaptchaCode($code);
        }

        return true;
    }

    /**
     * Validate the Captcha.
     *
     * @param string $captchaCode Captcha code
     */
    public function validateCaptchaCode(string $captchaCode): bool
    {
        // Check
        if (Strings::strlen($captchaCode) !== $this->captchaLength) {
            return false;
        }

        $captchaCode = Strings::strtoupper($captchaCode);
        // Help the user: treat "0" (ASCII 48) like "O" (ASCII 79)
        //                if "0" is not in the realm of captcha code letters
        if (!in_array('0', $this->letters)) {
            $captchaCode = str_replace('0', 'O', $captchaCode);
        }

        // Check
        for ($i = 0; $i < Strings::strlen($captchaCode); ++$i) {
            if (!in_array($captchaCode[$i], $this->letters)) {
                return false;
            }
        }

        // Search for this Captcha in the db
        $query = sprintf(
            "SELECT id FROM %sfaqcaptcha WHERE id = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($captchaCode)
        );

        if ($result = $this->configuration->getDb()->query($query)) {
            $num = $this->configuration->getDb()->numRows($result);
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
     * @param string|null $captchaCode Captcha code
     */
    private function removeCaptcha(?string $captchaCode = null): void
    {
        if ($captchaCode == null) {
            $captchaCode = $this->code;
        }

        $query = sprintf("DELETE FROM %sfaqcaptcha WHERE id = '%s'", Database::getTablePrefix(), $captchaCode);
        $this->configuration->getDb()->query($query);
    }
}
