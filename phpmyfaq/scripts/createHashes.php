<?php
/**
 * This scripts iterates recursively through the whole phpMyFAQ project and
 * creates SHA-1 keys for all files
 *
 * PHP Version 5.2.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Scripts
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-04-11
 */

$path  = dirname(__DIR__);
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($path),
    RecursiveIteratorIterator::SELF_FIRST
);

$hashes = array();

foreach ($files as $file) {
    if ('php' === $file->getExtension() && ! preg_match('#/tests/#', $file->getPath())) {
        $current = str_replace($path, '', $file->getPathname());
        $hashes[$current] = sha1(file_get_contents($file->getPathname()));
    }
}

file_put_contents('hashes.json', json_encode($hashes));