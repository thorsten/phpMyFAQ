<?php

/**
 * Init class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * Portions created by Christian Stocker are Copyright (c) 2001-2008 Liip AG.
 * All Rights Reserved.
 *
 * @package   phpMyFAQ
 * @author    Johann-Peter Hartmann <hartmann@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Stefan Esser <sesser@php.net>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Christian Stocker <chregu@bitflux.ch>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-24
 */

namespace phpMyFAQ;

use phpMyFAQ\Strings\StringBasic;

/**
 * Class Init
 *
 * @package phpMyFAQ
 */
class Init
{
    /**
     * cleanRequest.
     *
     * Cleans the request environment from:
     * - global variables,
     * - unescaped slashes,
     * - xss in the request string,
     * - uncorrect filenames when file are uploaded.
     */
    public static function cleanRequest(): void
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = urlencode((string) $_SERVER['HTTP_USER_AGENT']);
        }

        // clean external variables
        $externals = ['_REQUEST', '_GET', '_POST', '_COOKIE'];
        foreach ($externals as $external) {
            if (isset($GLOBALS[$external]) && is_array($GLOBALS[$external])) {
                // first clean XSS issues
                $newValues = $GLOBALS[$external];
                $newValues = self::removeXSSGPC($newValues);

                // clean old array and insert cleaned data
                foreach (array_keys($GLOBALS[$external]) as $key) {
                    $GLOBALS[$external][$key] = null;
                    unset($GLOBALS[$external][$key]);
                }
                foreach (array_keys($newValues) as $key) {
                    $GLOBALS[$external][$key] = $newValues[$key];
                }
            }
        }

        // clean external filenames (uploaded files)
        self::cleanFilenames();
    }

    /**
     * Removes XSS from an array.
     *
     * @param  array $data Array of data
     */
    private static function removeXSSGPC(array $data): array
    {
        static $recursionCounter = 0;
        // Avoid webserver crashes. For any detail, see: http://www.php-security.org/MOPB/MOPB-02-2007.html
        // Note: 1000 is an heuristic value, large enough to be "transparent" to phpMyFAQ.
        if ($recursionCounter > 1000) {
            die('Deep recursion attack detected.');
        }

        $cleanData = [];
        foreach ($data as $key => $val) {
            $key = self::basicXSSClean($key);
            if (is_array($val)) {
                ++$recursionCounter;
                $cleanData[$key] = self::removeXSSGPC($val);
            } else {
                $cleanData[$key] = self::basicXSSClean($val);
            }
        }

        return $cleanData;
    }

    /**
     * Cleans a html string from some xss issues.
     *
     * @param string $string String
     */
    private static function basicXSSClean(string $string): string
    {
        if (str_contains($string, '\0')) {
            return '';
        }

        if (ini_get('magic_quotes_gpc')) {
            $string = stripslashes($string);
        }

        $string = str_replace(['&amp;', '&lt;', '&gt;'], ['&amp;amp;', '&amp;lt;', '&amp;gt;'], $string);

        // fix &entitiy\n;
        $string = preg_replace('#(&\#*\w+)[\x00-\x20]+;#', '$1;', $string);
        $string = preg_replace('#(&\#x*)([0-9A-F]+);*#i', '$1$2;', $string);
        $string = html_entity_decode($string, ENT_COMPAT, 'utf-8');

        // remove any attribute starting with "on" or xmlns
        $string = preg_replace('#(<[^>]+[\x00-\x20\"\'\/])(on|xmlns)[^>]*>#iU', '$1>', $string);

        // remove javascript: and vbscript: protocol
        $string = preg_replace(
            '#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*)[\\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a' .
            '[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iU',
            '$1=$2nojavascript...',
            $string
        );
        $string = preg_replace(
            '#([a-z]*)[\x00-\x20]*=([\'\"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r' .
            '[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iU',
            '$1=$2novbscript...',
            $string
        );
        $string = preg_replace(
            '#([a-z]*)[\x00-\x20]*=([\'\"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#U',
            '$1=$2nomozbinding...',
            $string
        );
        $string = preg_replace(
            '#([a-z]*)[\x00-\x20]*=([\'\"]*)[\x00-\x20]*data[\x00-\x20]*:#U',
            '$1=$2nodata...',
            $string
        );

        //<span style="width: expression(alert('Ping!'));"></span>
        // only works in ie...
        $string = preg_replace(
            '#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*expression[\x00-\x20]*\([^>]*>#iU',
            '$1>',
            $string
        );
        $string = preg_replace(
            '#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*behaviour[\x00-\x20]*\([^>]*>#iU',
            '$1>',
            $string
        );
        $string = preg_replace(
            '#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i' .
            '[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*>#iU',
            '$1>',
            $string
        );

        //remove namespaced elements (we do not need them...)
        $string = preg_replace('#</*\w+:\w[^>]*>#i', '', $string);

        //remove really unwanted tags
        do {
            $oldString = $string;
            $string = preg_replace(
                '#</*(applet|meta|xml|blink|link|style|script|embed|object|iframe|frame|frameset|ilayer|layer|' .
                'bgsound|title|base)[^>]*>#i',
                '',
                $string
            );
        } while ($oldString != $string);

        return $string;
    }

    /**
     * Clean the filename of any uploaded file by the user and force an error
     * when calling is_uploaded_file($_FILES[key]['tmp_name']) if the cleanup goes wrong.
     */
    private static function cleanFilenames(): void
    {
        reset($_FILES);
        foreach ($_FILES as $key => $value) {
            if (is_array($_FILES[$key]['name'])) {
                reset($_FILES[$key]['name']);
                // We have a multiple upload with the same name for <input />
                foreach ($_FILES[$key]['name'] as $idx => $valu2) {
                    $_FILES[$key]['name'][$idx] = self::basicFilenameClean($_FILES[$key]['name'][$idx]);
                    if ('' == $_FILES[$key]['name'][$idx]) {
                        $_FILES[$key]['type'][$idx] = '';
                        $_FILES[$key]['tmp_name'][$idx] = '';
                        $_FILES[$key]['size'][$idx] = 0;
                        $_FILES[$key]['error'][$idx] = UPLOAD_ERR_NO_FILE;
                    }
                }
                reset($_FILES[$key]['name']);
            } else {
                $_FILES[$key]['name'] = self::basicFilenameClean($_FILES[$key]['name']);
                if ('' == $_FILES[$key]['name']) {
                    $_FILES[$key]['type'] = '';
                    $_FILES[$key]['tmp_name'] = '';
                    $_FILES[$key]['size'] = 0;
                    $_FILES[$key]['error'] = UPLOAD_ERR_NO_FILE;
                }
            }
        }
    }

    /**
     * Clean up a filename: if anything goes wrong, an empty string will be returned.
     *
     * @param string $filename Filename
     */
    private static function basicFilenameClean(string $filename): string
    {
        global $denyUploadExts;

        // Remove the magic quotes if enabled
        $filename = (ini_get('magic_quotes_gpc') ? stripslashes($filename) : $filename);

        $path_parts = pathinfo($filename);
        // We need a filename without any path info
        if ($path_parts['basename'] !== $filename) {
            return '';
        }
        //  We need a filename with at least 1 chars plus the optional extension
        if (isset($path_parts['extension']) && ($path_parts['basename'] == '.' . $path_parts['extension'])) {
            return '';
        }
        if (!isset($path_parts['extension']) && (StringBasic::strlen($path_parts['basename']) == 0)) {
            return '';
        }

        // Deny some extensions (see inc/constants.php), if any
        if (!isset($path_parts['extension'])) {
            $path_parts['extension'] = '';
        }
        if (count($denyUploadExts) > 0) {
            if (in_array(strtolower($path_parts['extension']), $denyUploadExts)) {
                return '';
            }
        }

        // Clean the file to remove some chars depending on the server OS
        // 0. main/rfc1867.c: rfc1867_post_handler removes any char before the last occurence of \/
        // 1. Besides \/ on Windows: :*?"<>|
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $reservedChars = [':', '*', '?', '"', '<', '>', "'", '|'];
            $filename = str_replace($reservedChars, '_', $filename);
        }

        return $filename;
    }
}
