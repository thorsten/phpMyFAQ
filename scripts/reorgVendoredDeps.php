#!/usr/bin/env php
<?php
/**
 * This scripts copies all 3rd party dependencies from vendor/ to
 * phpmyfaq/src/libs/
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Florian Anderiasch <florian@phpmyfaq.de>
 * @copyright 2019-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2019-09-24
 */

$pmfBaseDir = realpath(sprintf('%s/..', dirname(__FILE__)));

/**
 * Copy given files to given destination.
 *
 * @param string $srcDir
 * @param string $destDir
 * @param string $ext
 * @return bool
 */
function copyFiles(string $srcDir, string $destDir, string $ext): bool {
   if (strlen($ext) == 0) {
       return false;
   }

    $d = dir($srcDir);
    while (false !== ($entry = $d->read())) {
        if ($entry == '.' || $entry == '..') continue;
        if (substr($entry, -1 - strlen($ext)) == sprintf('.%s', $ext)) {
            $src = $srcDir  . DIRECTORY_SEPARATOR . $entry;
            $dst = $destDir . DIRECTORY_SEPARATOR . $entry;
            echo "Copying $src to $dst\n";
            copy($src, $dst);
        }
    }
    $d->close();
    return true;
}

/**
 * Recursive copy of given source to given destination
 *
 * @param string $source
 * @param string $destination
 * @return bool
 */
function copyRecursive(string $source, string $destination): bool {
    if (is_dir($source)) {
        if (!is_dir($destination)) {
          mkdir($destination, 0777, true);
        }
        $entries = scandir($source);
        if (empty($entries)) {
          return true;
        }
        foreach ($entries as $entry) {
            if ($entry == '.' || $entry == '..') continue;
            $fullSrc = $source . DIRECTORY_SEPARATOR . $entry;
            $fullDst = $destination . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($fullSrc)) {
                copyRecursive($fullSrc, $fullDst);
            } else {
                echo "Copying $fullSrc to $fullDst \n";
                copy($fullSrc, $fullDst);
            }
        }
        return true;
    } else if (is_file($source)) {
        return copy($source, $destination);
    } else {
        return false;
    }
}

$dirsToCreate = [
    'phpmyfaq/src/libs/abraham/twitteroauth',
    'phpmyfaq/src/libs/composer',
    'phpmyfaq/src/libs/elasticsearch/elasticsearch/src',
    'phpmyfaq/src/libs/guzzlehttp/ringphp/src',
    'phpmyfaq/src/libs/monolog/src/Monolog',
    'phpmyfaq/src/libs/myclabs/deep-copy/src',
    'phpmyfaq/src/libs/parsedown',
    'phpmyfaq/src/libs/phpseclib/phpseclib/phpseclib/Crypt',
    'phpmyfaq/src/libs/psr/log/Psr',
    'phpmyfaq/src/libs/react/promise/src',
    'phpmyfaq/src/libs/swiftmailer/swiftmailer/lib',
    'phpmyfaq/src/libs/tcpdf/config',
    'phpmyfaq/src/libs/tcpdf/include',
];

foreach ($dirsToCreate as $dir) {
    $dn = $pmfBaseDir . DIRECTORY_SEPARATOR . $dir;
    if (is_dir($dn)) {
      continue;
    }
    $rv = mkdir($dn, 0777, true);
    if (!$rv) {
      echo "Creating $dn failed.\n";
    }
}

$copySingle = [
    'vendor/autoload.php' => 'phpmyfaq/src/libs/autoload.php',
    'vendor/erusev/parsedown/Parsedown.php' => 'phpmyfaq/src/libs/parsedown/Parsedown.php',
    'vendor/erusev/parsedown-extra/ParsedownExtra.php' => 'phpmyfaq/src/libs/parsedown/ParsedownExtra.php',
    'vendor/phpseclib/phpseclib/phpseclib/bootstrap.php' => 'phpmyfaq/src/libs/phpseclib/phpseclib/phpseclib/bootstrap.php',
];

foreach ($copySingle as $source => $destination) {
    $source = $pmfBaseDir . DIRECTORY_SEPARATOR . $source;
    $destination = $pmfBaseDir . DIRECTORY_SEPARATOR . $destination;
    copy($source, $destination);
}

$copyDirs = [
    'vendor/abraham/twitteroauth' => 'phpmyfaq/src/libs/abraham/twitteroauth',
    'vendor/composer' => 'phpmyfaq/src/libs/composer',
    'vendor/elasticsearch/elasticsearch/src' => 'phpmyfaq/src/libs/elasticsearch/elasticsearch/src',
    'vendor/guzzlehttp/ringphp/src' => 'phpmyfaq/src/libs/guzzlehttp/ringphp/src',
    'vendor/monolog/monolog/src/Monolog' => 'phpmyfaq/src/libs/monolog/src/Monolog',
    'vendor/phpseclib/phpseclib/phpseclib/Crypt' => 'phpmyfaq/src/libs/phpseclib/Crypt',
    'vendor/psr/log/Psr' => 'phpmyfaq/src/libs/psr/log/Psr',
    'vendor/react/promise/src' => 'phpmyfaq/src/libs/react/promise/src',
    'vendor/swiftmailer/swiftmailer/lib' => 'phpmyfaq/src/libs/swiftmailer/swiftmailer/lib',
    'vendor/myclabs/deep-copy/src' => 'phpmyfaq/src/libs/myclabs/deep-copy/src',
    'vendor/symfony/polyfill-ctype' => 'phpmyfaq/src/libs/symfony/polyfill-ctype',
    'vendor/symfony/polyfill-iconv' => 'phpmyfaq/src/libs/symfony/polyfill-iconv',
    'vendor/symfony/polyfill-intl-idn' => 'phpmyfaq/src/libs/symfony/polyfill-intl-idn',
    'vendor/symfony/polyfill-mbstring' => 'phpmyfaq/src/libs/symfony/polyfill-mbstring',
    'vendor/symfony/polyfill-php72' => 'phpmyfaq/src/libs/symfony/polyfill-php72',
    'vendor/symfony/yaml' => 'phpmyfaq/src/libs/symfony/yaml',
];

foreach ($copyDirs as $source => $destination) {
    $source = $pmfBaseDir . DIRECTORY_SEPARATOR . $source;
    $destination = $pmfBaseDir . DIRECTORY_SEPARATOR . $destination;
    copyRecursive($source, $destination);
}

$copyFiles = [
    'vendor/tecnickcom/tcpdf' => 'phpmyfaq/src/libs/tcpdf',
    'vendor/tecnickcom/tcpdf/config' => 'phpmyfaq/src/libs/tcpdf/config',
    'vendor/tecnickcom/tcpdf/include' => 'phpmyfaq/src/libs/tcpdf/include',
];

foreach ($copyFiles as $source => $destination) {
    $source = $pmfBaseDir . DIRECTORY_SEPARATOR . $source;
    $destination = $pmfBaseDir . DIRECTORY_SEPARATOR . $destination;
    copyFiles($source, $destination, "php");
}
