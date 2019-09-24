#!/usr/bin/env php
<?php
$pmfBaseDir = realpath(sprintf('%s/..', dirname(__FILE__)));

function copy_files($srcDir, $destDir, $ext) {
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

function copy_rec($src, $dst) {
    if (is_dir($src)) {
        if (!is_dir($dst)) mkdir($dst, 0777, true);
        $entries = scandir($src);
        if (empty($entries)) return true;
        foreach ($entries as $entry) {
            if ($entry == '.' || $entry == '..') continue;
            $fullSrc = $src . DIRECTORY_SEPARATOR . $entry;
            $fullDst = $dst . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($fullSrc)) {
                copy_rec($fullSrc, $fullDst);
            } else {
                echo "Copying $fullSrc to $fullDst \n";
                copy($fullSrc, $fullDst);
            }
        }
        return true;
    } else if (is_file($src)) {
        return copy($src, $dest);
    } else {
        return false;
    }
}

$dirsToCreate = [
    'phpmyfaq/src/libs/abraham/twitteroauth',
    'phpmyfaq/src/libs/composer',
    'phpmyfaq/src/libs/elasticsearch/src/Elasticsearch',
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
	if (is_dir($dn)) continue;
    $rv = mkdir($dn, 0777, true);
    if (!$rv) echo "Creating $dn failed.\n";
}

$copySingle = [
    'vendor/autoload.php' => 'phpmyfaq/src/libs/autoload.php',
    'vendor/erusev/parsedown/Parsedown.php' => 'phpmyfaq/src/libs/parsedown/Parsedown.php',
    'vendor/erusev/parsedown-extra/ParsedownExtra.php' => 'phpmyfaq/src/libs/parsedown/ParsedownExtra.php',
    'vendor/phpseclib/phpseclib/phpseclib/bootstrap.php' => 'phpmyfaq/src/libs/phpseclib/phpseclib/phpseclib/bootstrap.php',
];

foreach ($copySingle as $src => $dst) {
    $src = $pmfBaseDir . DIRECTORY_SEPARATOR . $src;
    $dst = $pmfBaseDir . DIRECTORY_SEPARATOR . $dst;
    copy($src, $dst);
}

$copyDirs = [
    'vendor/abraham/twitteroauth' => 'phpmyfaq/src/libs/abraham/twitteroauth',
    'vendor/composer' => 'phpmyfaq/src/libs/composer',
    'vendor/elasticsearch/elasticsearch/src/Elasticsearch' => 'phpmyfaq/src/libs/elasticsearch/src/Elasticsearch',
    'vendor/guzzlehttp/ringphp/src' => 'phpmyfaq/src/libs/guzzlehttp/ringphp/src',
    'vendor/monolog/monolog/src/Monolog' => 'phpmyfaq/src/libs/monolog/src/Monolog',
    'vendor/phpseclib/phpseclib/phpseclib/Crypt' => 'phpmyfaq/src/libs/phpseclib/Crypt',
    'vendor/psr/log/Psr' => 'phpmyfaq/src/libs/psr/log/Psr',
    'vendor/react/promise/src' => 'phpmyfaq/src/libs/react/promise/src',
    'vendor/swiftmailer/swiftmailer/lib' => 'phpmyfaq/src/libs/swiftmailer/swiftmailer/lib',
    'vendor/myclabs/deep-copy/src' => 'phpmyfaq/src/libs/myclabs/deep-copy/src',
];

foreach ($copyDirs as $src => $dst) {
    $src = $pmfBaseDir . DIRECTORY_SEPARATOR . $src;
    $dst = $pmfBaseDir . DIRECTORY_SEPARATOR . $dst;
    copy_rec($src, $dst);
}

$copyFiles = [
    'vendor/tecnickcom/tcpdf' => 'phpmyfaq/src/libs/tcpdf',
    'vendor/tecnickcom/tcpdf/config' => 'phpmyfaq/src/libs/tcpdf/config',
    'vendor/tecnickcom/tcpdf/include' => 'phpmyfaq/src/libs/tcpdf/include',
];

foreach ($copyFiles as $src => $dst) {
    $src = $pmfBaseDir . DIRECTORY_SEPARATOR . $src;
    $dst = $pmfBaseDir . DIRECTORY_SEPARATOR . $dst;
    copy_files($src, $dst, "php");
}
