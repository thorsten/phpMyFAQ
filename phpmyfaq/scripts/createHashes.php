<?php
/**
 * This scripts iterates recursively through the whole phpMyFAQ project and
 * creates SHA-1 keys for all files
 *
 * PHP Version 5.2.3
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
 * @package   Scripts
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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